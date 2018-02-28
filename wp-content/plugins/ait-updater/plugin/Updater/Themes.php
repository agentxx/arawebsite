<?php

namespace Ait\Updater;



class Themes extends Base
{
	private static $needsToCheck = false;

	public function init()
	{
		add_filter('pre_set_site_transient_update_themes', array($this, 'checkUpdates'));
		add_filter('upgrader_pre_download', array($this, 'downloadPackage'), 10, 3);
		add_filter('http_request_args', array($this, 'excludeAitThemesFromWpOrgUpdateCheck'), 10, 2);
	}



	public function checkUpdates($transient)
	{
		if(empty($transient->checked)){
			return $transient;
		}

		if(!self::$needsToCheck){
			self::$needsToCheck = true;
			return $transient;
		}

		$aitThemes = Detector::getAllAitThemes();

		$requestArgs = array(
			'body' => array(
				'themes' => wp_json_encode($aitThemes),
			),
		);

		if($this->updater->credentials()){
			$requestArgs['body']['credentials'] = $this->updater->credentialsForRequest();
		}

		if(!is_multisite()){
			$requestArgs['body']['active_theme'] = get_option('stylesheet');
		}

		$apiResponse = $this->updater->api->themes->checkUpdates($requestArgs);

		if($apiResponse->isSuccessful()){
			$data = $apiResponse->data();

			foreach($data as $themeCodename => $value){
				$transient->response[$themeCodename] = (array) $value;
				$transient->response[$themeCodename]['url'] = $this->updater->api->baseUrl() . $this->updater->api->themes->changelogEndpoint($value->theme);
			}

		}else{
			$this->addError(sprintf(__('%s could not check updates for themes. Reason: %s', 'ait-updater'), 'AIT Updater', '<strong>' . $apiResponse->error()->get_error_message() . '</strong>'));
		}

		self::$needsToCheck = false;
		return $transient;
	}



	public function downloadPackage($false, $codename, $upgrader)
	{
		$return = $false;

		$apiResponse = $this->updater->api->themes->all();

		$allAitThemes = array();
		if($apiResponse->isSuccessful()){
			$allAitThemes = $apiResponse->data();
		}

		$aitThemes = Detector::getAllAitThemes(); // detected already installed themes

		if((isset($aitThemes[$codename]) or isset($allAitThemes[$codename])) and $upgrader instanceof \Theme_Upgrader){

			$themeType = Detector::getTypeOfTheme($codename);

			if($allAitThemes[$codename]){
				$themeType = 'club';
			}

			if(is_wp_error($tmpfname = $this->createTempFilePlaceholder($codename))){
				return $tmpfname; // wp_error
			}

			$return = $this->downloadTheme($codename, $upgrader, $tmpfname, isset($allAitThemes[$codename]));

			if(is_wp_error($return)){
				return $return;
			}

			if(Settings::get('do_backup')){
				$this->updater->doBackup('theme', $codename);
			}
		}

		return $return;
	}



	protected function downloadTheme($codename, $upgrader, $tmpfname, $isFromInstaller)
	{
		$args = array(
			'timeout' => 300,
			'stream' => true,
			'filename' => $tmpfname,
			'body' => array(
				'credentials' => $this->updater->credentialsForRequest(),
			),
		);

		$apiResponse = $this->updater->api->themes->download($codename, $args);

		if(!$apiResponse->isSuccessful()){
			unlink($tmpfname);

			// custom errors instead server one's
			if($apiResponse->code(400)){ // better UX
				$error = new \WP_Error('download_failed', $upgrader->strings['download_failed'], $apiResponse->error()->get_error_message());
			}elseif($apiResponse->code(403) and $isFromInstaller){
				$h1 = version_compare($GLOBALS['wp_version'], '4.6', '<' ) ? '<div class="ait-updater-purchase-message notice notice-warning">' : '';
				$h2 = version_compare($GLOBALS['wp_version'], '4.6', '<' ) ? '</div>' : '';

				$error = new \WP_Error('download_failed', $h1 .
					sprintf(
						__('You don not have active membership. Please <a href="%s" target="_blank">purchase %s</a> to get access to all themes, plugins and graphics.', 'ait-updater'),
						'https://system.ait-themes.club/join/membership/directory',
						'Full Membership'
					) . $h2
				);
			}else{
				$error = $apiResponse->error(); // wp_error
			}
			$upgrader->result = $error;

			return $error;
		}else{
			$contentMd5 = $apiResponse->header('content-md5');
			if($contentMd5){
				$md5Check = verify_file_md5($tmpfname, $contentMd5);
				if(is_wp_error($md5Check)){
					unlink($tmpfname);
					return $md5Check; // wp_error
				}
			}
			return $tmpfname; // this is what we want, path to successfully downloaded package
		}
	}



	public function excludeAitThemesFromWpOrgUpdateCheck($args, $url)
	{
		if($url === 'https://api.wordpress.org/themes/update-check/1.1/'){

			$body = json_decode($args['body']['themes']);
			$themes = (array) $body->themes;
			$aitThemes = Detector::getAllAitThemes();
			foreach($themes as $slug => $theme){
				if(isset($aitThemes[$slug])){
					unset($themes[$slug]);
				}
			}

			$body->themes = $themes;
			$args['body']['themes'] = wp_json_encode($body);

			return $args;
		}
		return $args;
	}

}
