<?php

namespace Ait\Updater;



class Plugins extends Base
{


	public function init()
	{
		add_filter('pre_set_site_transient_update_plugins', array($this, 'checkUpdates'));
		add_filter('upgrader_pre_download', array($this, 'downloadPackage'), 10, 3);
		add_filter('plugins_api', array($this, 'getPluginInfo'), 10, 3);

		$this->hackForWp42ShinyUpdates();
	}



	public function checkUpdates($transient)
	{
		$aitPlugins = Detector::getAitPlugins();

		$requestArgs = array(
			'body' => array(
				'plugins' => wp_json_encode($aitPlugins),
			),
		);


		if($this->updater->credentials()){
			$requestArgs['body']['credentials'] = $this->updater->credentialsForRequest();
		}


		$apiResponse = $this->updater->api->plugins->checkUpdates($requestArgs);

		if($apiResponse->isSuccessful()){
			$data = $apiResponse->data();
			foreach($data as $pluginBasename => $value){
				$transient->response[$pluginBasename] = $value;
			}
		}else{
			$this->addError(sprintf(__('%s could not check updates for plugins. Reason: %s', 'ait-updater'), 'AIT Updater', '<strong>' . $apiResponse->error()->get_error_message() . '</strong>'));
		}

		return $transient;
	}



	public function downloadPackage($false, $codename, $upgrader)
	{
		$return = $false;

		$aitPlugins = Detector::getAitPlugins();

		$isFromInstaller = (substr($codename, 0, 4) === 'ait-' and !isset($aitPlugins[$codename]));

		if((substr($codename, 0, 4) === 'ait-' or $codename === 'revslider' or isset($aitPlugins[$codename])) and $upgrader instanceof \Plugin_Upgrader){

			if(is_wp_error($tmpfname = $this->createTempFilePlaceholder($codename))){
				return $tmpfname; // wp_error
			}

			$args = array(
				'timeout' => 300,
				'stream' => true,
				'filename' => $tmpfname,
				'body' => array(
					'credentials' => $this->updater->credentialsForRequest(),
					'installed-ait-themes' => wp_json_encode(array_keys(Detector::getAllAitThemes())),
				),
			);

			$apiResponse = $this->updater->api->plugins->download($codename, $args);

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
							__('You did not purchased this plugin. Get access to all plugins, themes and graphics by <a href="%s" target="_blank">purchasing %s</a>.', 'ait-updater'),
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
						$upgrader->result = $md5Check;
						return $md5Check; // wp_error
					}
				}
				$return = $tmpfname;  // this is what we want, path to successfully downloaded package
			}

			if(Settings::get('do_backup')){
				$this->updater->doBackup('plugin', $codename);
			}
		}

		return $return;
	}



	public function getPluginInfo($false, $action, $arg)
	{
		$aitPlugins = Detector::getAitPlugins();

		if(isset($arg->slug) and (substr($arg->slug, 0, 4) === 'ait-' or isset($aitPlugins[$arg->slug]))){

			$codename = $arg->slug;
			if($codename === 'ait-revslider'){
				$codename = 'revslider';
			}

			$apiResponse = $this->updater->api->plugins->get($codename);

			if($apiResponse->isSuccessful()){
				$data = $apiResponse->data();

				$changelog = '';

				// latest version
				$d = date(get_option('date_format'), strtotime($data->latestVersion->releasedAt));
				$changelog .= "<h4>v{$data->latestVersion->version} ($d)</h4>";
				if(!empty($data->latestVersion->changelog)){
					$changelog .= "<ul>";
					$items = explode("\n", $data->latestVersion->changelog);
					foreach($items as $i){
						$i = str_replace('- ', '', $i);
						$i = esc_html($i);
						$changelog .= "<li>{$i}</li>";
					}
					$changelog .= "</ul><hr />";
				}

				// all previous versions
				foreach($data->previousVersions as $v){
					$d = date(get_option('date_format'), strtotime($v->releasedAt));
					$changelog .= "<h4>v{$v->version} ($d)</h4>";
					if(!empty($v->changelog)){
						$changelog .= "<ul>";
						$items = explode("\n", $v->changelog);
						foreach($items as $i){
							$i = str_replace('- ', '', $i);
							$i = esc_html($i);
							$changelog .= "<li>{$i}</li>";
						}
						$changelog .= "</ul>";
					}
				}


				$result = (object) array(
					'name' => $data->name,
					'slug' => $data->codename,
					'version' => $data->latestVersion->version,
					'author' => '<a href="https://ait-themes.club">AitThemes.club</a>',
					'banners' => (array) $data->banners,
					'requires' => $data->minRequiredWpVersion,
					'tested' => $data->testedUpToWpVersion,
					// 'compatibility' => array(),
					// 'rating' => '',
					// 'num_ratings' => "",
					// 'ratings' => array(),
					// 'downloaded' => '',
					'last_updated' => $data->latestVersion->releasedAt,
					// 'added' => "2011-09-27",
					'homepage' => $data->pluginUrl,
					'sections' => array(
						// 'description' => '',
						// 'installation' => '',
						// 'screenshots' => '',
						'changelog' => $changelog,
						// 'faq' => 'fuck',
					),
					'download_link' => $data->codename,
					// 'tags' => array(),
					// 'donate_link' => '',

				);

				return $result;
			}else{
				return $false;
			}
		}

		return $false;
	}



	protected function hackForWp42ShinyUpdates()
	{
		global $wp_version, $pagenow;
		if(version_compare($wp_version, '4.2', '>=') and version_compare($wp_version, '4.6', '<' )){
			add_action('wp_ajax_update-plugin', array($this, 'overrideDefaultAjaxUpdatePluginActionCallback'), 0); // hack
			if($pagenow == 'plugins.php'){
				add_action('admin_print_footer_scripts', array($this, 'overrideErrorJsMessageFromAjaxUpdatePlugin'));
			}
		}
	}



	public function overrideDefaultAjaxUpdatePluginActionCallback()
	{
		global $wp_filter;
		// nasty hack
		$wp_filter['wp_ajax_update-plugin'][1]['wp_ajax_update_plugin']['function'] = array($this, 'wpAjaxUpdatePlugin');
	}



	public function wpAjaxUpdatePlugin()
	{
		// content of wp_ajax_update_plugin()

		$plugin = urldecode( $_POST['plugin'] );

		$status = array(
			'update'     => 'plugin',
			'plugin'     => $plugin,
			'slug'       => sanitize_key( $_POST['slug'] ),
			'oldVersion' => '',
			'newVersion' => '',
		);
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
		if ( $plugin_data['Version'] ) {
			$status['oldVersion'] = sprintf( __( 'Version %s', 'ait-updater' ), $plugin_data['Version'] );
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			$status['error'] = __( 'You do not have sufficient permissions to update plugins on this site.', 'ait-updater' );
	 		wp_send_json_error( $status );
		}

		check_ajax_referer( 'updates' );

		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

		$current = get_site_transient( 'update_plugins' );
		if ( empty( $current ) ) {
			wp_update_plugins();
		}

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result = $upgrader->bulk_upgrade( array( $plugin ) );

		if ( is_array( $result ) ) {
			$plugin_update_data = current( $result );

			/*
			 * If the `update_plugins` site transient is empty (e.g. when you update
			 * two plugins in quick succession before the transient repopulates),
			 * this may be the return.
			 *
			 * Preferably something can be done to ensure `update_plugins` isn't empty.
			 * For now, surface some sort of error here.
			 */
			if ( $plugin_update_data === true ) {
	 			wp_send_json_error( $status );
			} else if ( is_wp_error( $plugin_update_data ) ) { // AIT: added this condition
				$status['error'] = $plugin_update_data->get_error_message();
		 		wp_send_json_error( $status );
		 	}

			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

			if ( $plugin_data['Version'] ) {
				$status['newVersion'] = sprintf( __( 'Version %s', 'ait-updater' ), $plugin_data['Version'] );
			}

			wp_send_json_success( $status );
		} else if ( is_wp_error( $result ) ) {
			$status['error'] = $result->get_error_message();
	 		wp_send_json_error( $status );
		} else if ( is_bool( $result ) && ! $result ) {
			$status['errorCode'] = 'unable_to_connect_to_filesystem';
			$status['error'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'ait-updater' );
			wp_send_json_error( $status );
		}
	}



	public function overrideErrorJsMessageFromAjaxUpdatePlugin()
	{
		?>
		<script>
			(function($, document){
				$(document).on('wp-plugin-update-error', function(e, response){
					var $message = $('[data-slug="' + response.slug + '"]').next().find('.update-message');
					$message.text(response.error);
				});
			})(jQuery, document);
		</script>
		<?php
	}
}
