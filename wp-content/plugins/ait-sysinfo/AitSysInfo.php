<?php


class AitSysInfo
{
	const VERSION_KEY = '_ait_sysinfo_version';
	const SLUG = 'ait-sysinfo';

	public $pluginDir;
	public $pluginUrl;

	private static $instance;



	protected function __construct()
	{
		$this->pluginDir = dirname(__FILE__);
		$this->pluginUrl = plugins_url('', __FILE__);
	}



	public function run()
	{
		register_activation_hook(__FILE__, array($this, 'onActivation'));
		register_deactivation_hook(__FILE__, array($this, 'onDeactivation'));

		add_action('plugins_loaded', array($this, 'loadTextdomain'));
		add_action('admin_menu', array($this, 'onAdminMenu'), 12);
		add_filter('plugin_action_links', array($this, 'addPluginActionLinks'), 10, 2);
	}



	protected function activate()
	{
		update_option(self::VERSION_KEY, AIT_SYSINFO_VERSION);
	}



	protected function deactivate()
	{
		delete_option(self::VERSION_KEY);
	}



	public function onActivation($networkWide)
	{
		$this->doForEachSite('activate', $networkWide);
	}



	public function onDeactivation($networkWide)
	{
		$this->doForEachSite('deactivate', $networkWide);
	}



	protected function doForEachSite($action, $networkWide)
	{
		if($networkWide and is_multisite()){
			global $wpdb;

			foreach($wpdb->get_col("SELECT blog_id FROM $wpdb->blogs") as $blogId) {
				switch_to_blog($blogId);
				$this->{$action}();
			}
			restore_current_blog();
		}else{
			$this->{$action}();
		}
	}



	public function loadTextdomain()
	{
		load_plugin_textdomain('ait-sysinfo', false, basename($this->pluginDir) . '/languages/');
	}



	public function onAdminMenu()
	{
		if( ! defined('AIT_SKELETON_VERSION')){
			$pageHookname = add_menu_page(
				/* translators: %s - the plugin name */
				sprintf(__('%s - System Information', 'ait-sysinfo'), 'AIT SysInfo'),
				'AIT SysInfo',
				'manage_options',
				self::SLUG,
				array($this, 'addSimpleAdminPage'),
				'dashicons-clipboard'
			);

			add_action("admin_print_styles-{$pageHookname}", array($this, 'printSimpleAdminPageCss'));
			add_action("admin_head-{$pageHookname}", array($this, 'printSimpleAdminPageJs'));
		}else{
            $pageHookname = add_submenu_page(
                'ait-theme-options',
				/* translators: %s - the plugin name */
				sprintf(__('%s - System Information', 'ait-sysinfo'), 'AIT SysInfo'),
                'AIT SysInfo',
                'manage_options',
				self::SLUG,
				array($this, 'addAitBrandingAdminPage')
            );

			add_action("admin_print_scripts-{$pageHookname}", array($this, 'enqueueAitAdminDesign'));
			add_action("admin_print_styles-{$pageHookname}", array($this, 'printAitBrandingAdminPageCss'), 2017);
			add_action("admin_head-{$pageHookname}", array($this, 'printAitBrandingAdminPageJs'));
        }

		add_action("admin_print_styles-{$pageHookname}", array($this, 'printGeneralAdminCss'));
		add_action("admin_head-{$pageHookname}", array($this, 'printGeneralAdminJs'));

	}



	public function addAitBrandingAdminPage()
	{
		?>
			<div class="wrap">
				<div id="ait-sysinfo-page" class="ait-admin-page ait-options-layout">
					<div class="ait-admin-page-wrap">
						<?php /* Hack for WP notifications, all will be placed right after this h2 */ ?>
						<h2 style="display: none;"></h2>

						<div class="ait-options-page-header">
							<h3 class="ait-options-header-title">AIT SysInfo</h3>
							<div class="ait-options-header-tools">
								<a class="ait-scroll-to-top"><i class="dashicons dashicons-arrow-up-alt2" style="line-height: 14px;height: auto;width: auto; font-weight: 700;vertical-align: initial;"></i></a>
							</div>

							<div class="ait-sticky-header">
								<h4 class="ait-sticky-header-title">AIT SysInfo<i class="fa fa-circle"></i><span class="subtitle"></span></h4>
							</div>
						</div>

						<div class="ait-options-page">

							<div class="ait-options-page-content">
								<div class="ait-options-sidebar">
									<div class="ait-options-sidebar-content">
										<ul id="ait-sysinfo-tabs" class="ait-options-tabs">
											<li id="ait-sysinfo-panel-tab" class=""><a href="#ait-sysinfo-panel"><?php _e('System Information', 'ait-sysinfo')?></a></li>
											<li id="ait-sysinfo-requirements-panel-tab" class=""><a href="#ait-sysinfo-requirements-panel"><?php _e('Requirements &amp; Troubleshooting', 'ait-sysinfo')?></a></li>
											<li id="ait-sysinfo-phpinfo-panel-tab" class=""><a href="#ait-sysinfo-phpinfo-panel"><?php _e('PHP Information', 'ait-sysinfo')?></a></li>
										</ul>
									</div>
								</div>
								<div class="ait-options-content">
									<div class="ait-options-controls-container">
										<div id="ait-sysinfo-panels" class="ait-options-controls ait-options-panels">
											<form action="#" method="post" name="">
												<div id="ait-sysinfo-panel" class="ait-options-group ait-options-panel ait-sysinfo-tabs-panel">
													<div id="ait-options-basic" class="ait-controls-tabs-panel ait-options-basic">
														<div class="ait-options-section ">
															<?php AitSysInfoReporter::report(); ?>
														</div>
													</div>
												</div>
												<div id="ait-sysinfo-requirements-panel" class="ait-options-group ait-options-panel ait-sysinfo-tabs-panel">
													<div id="ait-options-basic-requirements" class="ait-controls-tabs-panel ait-options-basic">
														<div class="ait-options-section ">
															<?php AitRequirementsInfoReporter::report(); ?>
														</div>
													</div>
												</div>

												<div id="ait-sysinfo-phpinfo-panel" class="ait-options-group ait-options-panel ait-sysinfo-tabs-panel">
													<div id="ait-options-basic-phpinfo" class="ait-controls-tabs-panel ait-options-basic">
														<div class="ait-options-section ">
															<?php AitPhpInfoReporter::report(); ?>
														</div>
													</div>
												</div>
											</form>
										</div>
									</div>
								</div>

							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}



	public function addSimpleAdminPage()
	{
		?>
		<div class="wrap">
			<div id="ait-sysinfo-page">
				<?php /* translators: %s - the plugin name */ ?>
				<h1><?php printf(__('%s - System Information', 'ait-sysinfo'), 'AIT SysInfo <small>v' . AIT_SYSINFO_VERSION  . '</small>') ?></h1>
				<nav class="nav-tab-wrapper">
					<a href="#system-info" class="nav-tab nav-tab-active">System Info</a>
					<a href="#php-info" class="nav-tab">PHP Info</a>
					<a href="#troubleshooting" class="nav-tab ">Requirements &amp; Troubleshooting</a>
				</nav>
				<div id="ait-sysinfo-page-tabs-content">
					<div id="system-info" class="nav-tab-content">
						<?php AitSysInfoReporter::report(); ?>
					</div>
					<div id="php-info"" class="nav-tab-content">
						<?php AitPhpInfoReporter::report(); ?>
					</div>
					<div id="troubleshooting" class="nav-tab-content">
						<?php AitRequirementsInfoReporter::report(); ?>
					</div>
				</div>
			</div>
		</div>

		<?php
	}



	public function enqueueAitAdminDesign()
	{
		$assetsUrl = aitPaths()->url->admin . '/assets';

		wp_enqueue_style('ait-wp-admin-style', "{$assetsUrl}/css/wp-admin.css", array(), AIT_THEME_VERSION);
		wp_enqueue_style('ait-admin-style', "{$assetsUrl}/css/style.css", array(), AIT_THEME_VERSION);
		wp_enqueue_style('ait-admin-options-controls', "{$assetsUrl}/css/options-controls.css", array('ait-admin-style'), AIT_THEME_VERSION);

		wp_enqueue_script('ait.admin', "{$assetsUrl}/js/ait.admin.js", array(), AIT_THEME_VERSION, TRUE);
		wp_enqueue_script('ait.admin.Tabs', "{$assetsUrl}/js/ait.admin.tabs.js", array('ait.admin', 'jquery'), AIT_THEME_VERSION, TRUE);
	}



	public function printAitBrandingAdminPageCss()
	{
		?>
		<style>
			.ait-options-header-title:before{
				content: "\f481" !important;
				font-family: dashicons !important;
			}
			.ait-opt-container .ait-opt-wrapper {
				background: none;
			}
			#ait-sysinfo-page textarea {
				height: 400px;
			}
			#ait-options-basic-phpinfo h1,
			#ait-options-basic-phpinfo h2 {
				color: #666;
			}

			#ait-sysinfo-page #ait-sysinfo-phpinfo-panel table {
				table-layout: fixed;
				width: 100%;
				color: #888;
			}
			#ait-sysinfo-page #ait-sysinfo-phpinfo-panel tr {
				border-bottom: 1px solid #f8f8f8;
			}
			#ait-sysinfo-page #ait-sysinfo-phpinfo-panel tr:hover {
				background: #f8f8f8;
			}
			#ait-sysinfo-page #ait-sysinfo-phpinfo-panel th {
				background: #f8f8f8;
			}
			#ait-sysinfo-page #ait-sysinfo-phpinfo-panel td {
				word-wrap: break-word;
			}
			#ait-sysinfo-page #ait-sysinfo-phpinfo-panel td.e {
				font-weight: 600;
			}
			#ait-sysinfo-page #ait-sysinfo-phpinfo-panel table img {
				display: none
			}
		</style>
		<?php
	}



	public function printAitBrandingAdminPageJs()
	{
		?>
		<script>
			jQuery(function($){
				if(typeof ait !== "undefined"){
					new ait.admin.Tabs(jQuery('#ait-sysinfo' + '-tabs'), jQuery('#ait-sysinfo' + '-panels'), 'ait-admin-' + "sysinfo" + '-page');

					var $window = $(window);

					function isResponsive(width)
					{
						var w=window,
							d=document,
							e=d.documentElement,
							g=d.getElementsByTagName('body')[0],
							x=w.innerWidth||e.clientWidth||g.clientWidth;
						return x <= parseInt(width);
					};

					var $stickyHeader = $('.ait-sticky-header');

					if($stickyHeader.length) {
						var wpAdminBarHeight = ($("#wpadminbar").length) ? parseInt($('#wpadminbar').height()) : 28;
						var scrollOfset = $('.ait-options-page-content').offset().top + wpAdminBarHeight;
						$stickyHeader.css('width', $('.ait-options-page-content').width());

						$window.on('resize scroll', function() {
							if ($window.scrollTop() > scrollOfset) {
								$('body').addClass('sticky');
								$stickyHeader.css('width', $('.ait-options-page-content').width());
								if(isResponsive(480)) {
									$('.ait-options-page').css('margin-top', $('.ait-header-save').outerHeight() + 10);
								}
							} else {
								$('body').removeClass('sticky');
								$('.ait-options-page').css('margin-top', 0);
							}
						});
					}

					$(".ait-scroll-to-top").on('click', function(e){
						e.preventDefault();
						$(this).blur();
						$('html, body').animate({ scrollTop: 0 }, "slow");
					});
				}
			});
		</script>
		<?php
	}



	public function printGeneralAdminCss()
	{
		?>
		<style>
			#ait-requirements-info-report table {padding: 0; margin: 0; border-collapse: collapse; width: 100%; }
			#ait-requirements-info-report table td, table th {text-align: left; padding: 10px; vertical-align: top; border-style: solid; border-width: 1px 0 0; border-color: inherit; background: white none no-repeat 12px 8px; background-color: inherit; }
			#ait-requirements-info-report table th {font-size: 105%; font-weight: 600; padding-left: 50px; }
			#ait-requirements-info-report table tr:first-child td, table tr:first-child th { border: none; }
			#ait-requirements-info-report .passed, .info { background-color: none; border-color: #efefef; }
			#ait-requirements-info-report .passed th, .info th { border-color: #efefef; }
			#ait-requirements-info-report .passed th { background-position: 12px center; background-image:  url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAErSURBVHjazJTBTcNAEEVfEAW4hJy4EiqI6cDpwGkAkgocKgDRgF1CqABTAebKKSWYCszlr/RxdnGQcmCklUee2TezszM7G4aBc8oFZ5bLoOw/71I+cy2Ag9aRFFfPP4ERqYDSYEE64GYyQ5MMeAUWBthLz4Gt+S5kTwId1mlza/YH00ugVrBVClgZ7BboEycLMICX1C1nwEb69kTYGmhSwNxq1irA/C+wMfA6dJDV8t0uZxI21didgasELJe9jgG/rJF725gBu0RmS0EPMWBoj0KQAGh/OWap70cM2ClSBtzb/5UmYwzb2Gn2qRqu9d1Z9H48DbI9jvZEga051Cp4MWqt2i6h8exSs9woq1qAPOLTawyfTnkcQi+2ym5pDd4Dbxb0SGb//sX+HgAjsVEOniajOwAAAABJRU5ErkJggg=='); }
			#ait-requirements-info-report .info th { background-position: 12px center; background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAEpSURBVHjazJTfTcMwEMZ/rVjAK2QFkHhEQmWEZoSsEI/QjNCOACMQIfGIREZoVvAI4eULOq62EVIf+KSTLfvu8/31ZlkWroktV8bNunl4+/B3t0AnsXgx8o33x/ufhA5HQzQCk/YNsJeMwFPRQ4NnY9ACqaLz6kl9DnspDlJMhQhaIAI74FAj7ORZNGcBWICz0x2Ak2xCjnCvHEVnmIyxRzSOXBA2wGwKYNOwCpnHJtleEIYM2VrludJ6Uylk7IUzSBXCUCpKUjP/FTsbwdZNQMjkqtdDwbeIKeSYa+xZpL3W2eRwMOHbUA86G0uTEoFPTUAr5SlTrEbT0vw2KbMUgoiPLq+NvDrrvLXelWZ5Au7MB9EVWinm2qz021hPfZFOtb7c/Psf+2sAboxNpVZoBT4AAAAASUVORK5CYII='); }
			#ait-requirements-info-report .warning {background-color: #F9F8EE; border-color: #EFEFDB; }
			#ait-requirements-info-report div.warning {background-color: #daaf65; }
			#ait-requirements-info-report .warning th { background-position: 12px center; background-image:  url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAFHSURBVHjazJSxkcIwEEWfmWvAV4JThbQAiUYplOAWoARcApSAU42TcwnnUKlbcAWMSZabRUhAQHB/RmOPrf379Xe1xTzPfBILPoyv3I/QuSVQy9JogdZY36biitSRQ+eOiqgHBnmvgM3tu7F+/ZIwdO4sQT2wNdZPiYR/e2LSO8LQuR1wABpj/V4d/ReYgG9jfXZvqii1ZN2/Mt9Y3wANUIfOlQ+EoXMb8SgmG6OnRqOEPCisgNFYP0RBk6wxoXKSglUpwlJVkwxpCoPEJvuwzAS1T5KVuZsyAcsnCleZhCttxyJSUUo73ONSHLgUdZxQFbJ/IDTWj0K6C52rov6HuUAHSqvsgMFY3+cau1JNvE1UXO87i+J1llDdjB/x6wScbsRCVIsyJGn7znCogKMYnkIP7FMnKJ4NWOWTxkn85u3x9a8m9nUAJvmWXBJn5OQAAAAASUVORK5CYII='); }
			#ait-requirements-info-report .failed {background-color: #FCF7F7; border-color: #F5E8E8; }
			#ait-requirements-info-report div.failed { background-color: #da6b6b; }
			#ait-requirements-info-report .failed th { background-position: 12px center; background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAE7SURBVHjazFS7ccMwDH3OZQGuoBWUEZQRrBGcyjU1glS7skeIRrBGMEcIV+AIcvOYewYpXwoXwR1PFAg8PHzI3bqueKW84cXynjfxeLRnLYADl8os61ea0+kR0MhZgBYAIfsB2HMtAD43GYp8i0MPID2xuVpQW0NPw4mGaSODHsAAoAMwPmN4ILOBe0dwGxTUO9pNObgy3LNGg+hGpqf/ymgSIgXDBkCUBlwY9UzQSHZfPAPPA30LQCdgOiKRxXes3WxsHgBtU1ylAZ76VJnJwkcBE4e5Nh49u96SrUrHLArAmdG8gHWSZiBoA+BmGrnUahjp6PkdKnXNoE6CBwW0Ncwjc91oUg6caNOaMSsAozC4cWRaM1ojgB/qe2W3dZcDgA95IGqdzbcp/OVxsEy9ObtoV63s/v2LfR8AoB1X/qMqR68AAAAASUVORK5CYII='); }
			#ait-requirements-info-report .description td {border-top: none !important; padding: 0 10px 15px 50px; color: #888; font-size: 13px; }
			#ait-requirements-info-report .passed.description {display: none; }
		</style>
		<?php
	}



	public function printGeneralAdminJs()
	{
		?>
		<script>
			jQuery(function($){
				var $btn = $('#ait-sysinfo-copy');
				var $msg = $('<span></span>').css({'color': 'green', 'padding-left': '10px', 'line-height': '26px'}).hide().text('<?php _e('Copied!', 'ait-sysinfo') ?>');
				$btn.after($msg);

				$btn.on('click', function(event){
					var copyTextarea = document.querySelector('#ait-sysinfo-report');
					copyTextarea.select();
					try{
						var successful = document.execCommand('copy');
						if(successful){
							$msg.show().delay(1000).fadeOut(800);
						}
					}catch(err){
						$msg.text('<?php _e('Press Ctrl+C to copy', 'ait-sysinfo') ?>'); // Safari
						$msg.show().delay(1500).fadeOut(800);
					}
				});
			});
		</script>
		<?php
	}



	public function printSimpleAdminPageCss()
	{
		?>
		<style>
			#ait-sysinfo-page {
				margin: 0px 20px 20px 0px;
			}
			#ait-sys-info-report textarea{
				font-family: Consolas, 'Courier New', Courier, monospace;
				font-size: 14px;
				width: 100%;
				height: 600px;
				background-color: #f9f9f9;
				margin-top: 15px;
			}
			#ait-sysinfo-page .clear {
				clear: both;
			}
			#ait-sysinfo-page .wrap {
				margin-right: 0px;
			}

			#ait-sysinfo-page-tabs-content {
				margin-top: 33px;
			}

			#ait-sysinfo-page-tabs-content .nav-tab-content:not(:first-child) {
				display: none;
			}
			a.nav-tab:focus, a.nav-tab:active { outline: none; }

			#ait-php-info-report {font-family: Consolas, 'Courier New', Courier, monospace;font-size: 14px;height: auto;}
			#ait-php-info-report table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}
			#ait-php-info-report .center {text-align: center;}
			#ait-php-info-report .center table {margin: 1em auto; text-align: left;}
			#ait-php-info-report .center th {text-align: center !important;}
			#ait-php-info-report td, th {border: 1px solid #666; vertical-align: baseline; padding: 4px 5px;}
			#ait-php-info-report .p {text-align: left;}
			#ait-php-info-report .e {background-color: #ccf; width: 300px; font-weight: bold;}
			#ait-php-info-report .h {background-color: #99c; font-weight: bold;}
			#ait-php-info-report .v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
			#ait-php-info-report .v i {color: #999;}
			#ait-php-info-report img {float: right; border: 0;}
			#ait-php-info-report hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}


			.alert {padding: 15px;margin-bottom: 20px;border: 1px solid transparent;border-radius: 2px;line-height: 18px;}
			.alert h4 {margin-top: 0;color: inherit;}
			.alert .alert-link {font-weight: bold;}

			.alert > p,
			.alert > ul,
			.alert > ul li:last-child {margin-bottom: 0;}
			.alert > ul {margin-top: 0;}
			.alert > p + p {margin-top: 5px;}
			.alert-success {color: #468847;background-color: #dff0d8;border-color: #d6e9c6;}
			.alert-success hr {border-top-color: #c9e2b3;}
			.alert-success .alert-link {color: #356635;}
			.alert-info {color: #3a87ad;background-color: #d9edf7;border-color: #bce8f1;}
			.alert-info hr {border-top-color: #a6e1ec;}
			.alert-info .alert-link {color: #2d6987;}
			.alert-warning {color: #c09853;background-color: #fcf8e3;border-color: #fbeed5;}
			.alert-warning hr {border-top-color: #f8e5be;}
			.alert-warning .alert-link {color: #a47e3c;}
			.alert-danger {color: #b94a48;background-color: #f2dede;border-color: #eed3d7;}
			.alert-danger hr {border-top-color: #e6c1c7;}
			.alert-danger .alert-link {color: #953b39;}
		</style>
		<?php
	}



	public function printSimpleAdminPageJs()
	{
		?>
		<script>
			jQuery(function($){
				var $tabsContainer = $('#ait-sysinfo-page-tabs-content');
				$('#ait-sysinfo-page .nav-tab-wrapper > a').on('click', function(e){
					e.preventDefault();
					var $a = $(this);
					$a.siblings().removeClass('nav-tab-active');
					$a.addClass('nav-tab-active');
					var target = $(this).attr('href');
					$tabsContainer.find('.nav-tab-content').hide();
					$tabsContainer.find(target).show();
				});

			});
		</script>
		<?php
	}



	public function addPluginActionLinks($links, $file)
	{
		static $_thisPlugin;

		if(!$_thisPlugin){
			$_thisPlugin = plugin_basename(__FILE__);
		}

		if($file == $_thisPlugin){
			array_unshift($links, '<a href="' . admin_url('tools.php?page=' . self::SLUG) . '">' . __('View', 'ait-sysinfo') . '</a>');
		}

		return $links;
	}



	public static function getInstance()
	{
		if(!self::$instance){
			self::$instance = new self;
		}

		return self::$instance;
	}

}
