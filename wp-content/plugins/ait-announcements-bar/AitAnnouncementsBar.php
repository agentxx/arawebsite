<?php



class AitAnnouncementsBar
{

	protected static $file;
	protected static $baseDir;
	protected static $baseUrl;



	public static function run($file, $baseDir, $baseUrl)
	{
		self::$file = $file;
		self::$baseDir = $baseDir;
		self::$baseUrl = $baseUrl;

		add_action('plugins_loaded', array(__CLASS__, 'onPluginsLoaded'));
	}



	public static function onPluginsLoaded()
	{
		if(is_admin()){
			add_action('admin_menu', array(__CLASS__, 'addOptionsPage'));
			add_action('admin_init', array(__CLASS__, 'settingsInit'));

			add_filter( 'admin_body_class', array(__CLASS__, 'adminBodyClass'), 10, 1);
			add_action( 'admin_enqueue_scripts', array(__CLASS__, 'enqueueAdminDesign') );

			load_plugin_textdomain('ait-announcements-bar', false, basename(self::$baseDir) . '/languages');
		}

		add_action('wp_head', array(__CLASS__, 'addStyles'), 33);
		add_action('template_redirect', array(__CLASS__, 'onTemplateRedirect'));
	}



	public static function onTemplateRedirect()
	{
		add_action('ait-html-body-begin', array(__CLASS__, 'renderAnnouncementsBar'));
		add_filter('body_class', array(__CLASS__, 'bodyHtmlClass'), 10, 2);
	}



	public static function bodyHtmlClass($classes, $class)
	{
		if(!self::canBeDisplayed()) return $classes;

		$classes[] = 'ait-announcements-bar-plugin';

		return $classes;
	}

	public static function adminBodyClass($classes) {
		if(!empty($_REQUEST['page'])){
			if($_REQUEST['page'] == 'ait_announcements_bar_options'){
				$classes = explode(" ", $classes);
				array_push($classes, 'ait_announcements_bar_options');
				$classes = implode(" ", $classes);
			}
		}
		return $classes;
	}

	public static function enqueueAdminDesign($hook){
		$page = "";
		if(!empty($_REQUEST['page'])){
			$page = $_REQUEST['page'];
		}

		if(class_exists("AitTheme") && $page == "ait_announcements_bar_options"){
			$assetsUrl = aitPaths()->url->admin . '/assets';
			$min = ((defined('SCRIPT_DEBUG') and SCRIPT_DEBUG) or AIT_DEV) ? '' : '.min';

			wp_enqueue_style('ait-colorpicker', "{$assetsUrl}/libs/colorpicker/colorpicker.css", array(), '2.2.1');
			wp_enqueue_style('ait-jquery-chosen', "{$assetsUrl}/libs/chosen/chosen.css", array(), '0.9.10');
			wp_enqueue_style('jquery-ui', "{$assetsUrl}/libs/jquery-ui/jquery-ui.css", array('media-views'), AIT_THEME_VERSION);
			wp_enqueue_style('jquery-switch', "{$assetsUrl}/libs/jquery-switch/jquery.switch.css", array(), '0.4.1');
			wp_enqueue_style('ait-admin-style', "{$assetsUrl}/css/style.css", array('media-views'), AIT_THEME_VERSION);
			wp_enqueue_style('ait-admin-options-controls', "{$assetsUrl}/css/options-controls" . "" . ".css", array('ait-admin-style', 'ait-jquery-chosen'), AIT_THEME_VERSION);
			$fontCssFile = aitUrl('css', '/libs/font-awesome.min.css');
			if($fontCssFile){
				wp_enqueue_style('ait-font-awesome-select', $fontCssFile, array(), '4.2.0');
			}

			wp_enqueue_script('ait.admin', "{$assetsUrl}/js/ait.admin.js", array('media-editor'), AIT_THEME_VERSION, TRUE);

			AitAdmin::adminGlobalJsSettings();

			wp_enqueue_script('ait-jquery-filedownload', "{$assetsUrl}/libs/file-download/jquery.fileDownload{$min}.js", array('jquery', 'ait.admin'), '1.3.3', TRUE);

			wp_enqueue_script('ait-colorpicker', "{$assetsUrl}/libs/colorpicker/colorpicker{$min}.js", array('jquery'), '2.2.1', TRUE);
			wp_enqueue_script('ait-jquery-chosen', "{$assetsUrl}/libs/chosen/chosen.jquery{$min}.js", array('jquery'), '1.0.0', TRUE);
			wp_enqueue_script('ait-jquery-sheepit', "{$assetsUrl}/libs/sheepit/jquery.sheepItPlugin{$min}.js", array('jquery', 'ait.admin'), '1.1.1-ait-1', TRUE);
			wp_enqueue_script('ait-jquery-deparam', "{$assetsUrl}/libs/jquery-deparam/jquery-deparam{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			wp_enqueue_script('ait-jquery-rangeinput', "{$assetsUrl}/libs/rangeinput/rangeinput.min.js", array('jquery', 'ait.admin'), '1.2.7', TRUE);
			wp_enqueue_script('ait-jquery-numberinput', "{$assetsUrl}/libs/numberinput/numberinput{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			if(class_exists('AitLangs') and AitLangs::getCurrentLanguageCode() !== 'en'){
				wp_enqueue_script('ait-jquery-datepicker-translation', "{$assetsUrl}/libs/datepicker/jquery-ui-i18n{$min}.js", array('jquery', 'ait.admin', 'jquery-ui-datepicker'), FALSE, TRUE);
			}
			wp_enqueue_script('ait-jquery-switch', "{$assetsUrl}/libs/jquery-switch/jquery.switch{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			wp_enqueue_script('ait-bootstrap-dropdowns', "{$assetsUrl}/libs/bootstrap-dropdowns/bootstrap-dropdowns{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);

			//wp_enqueue_media();

			wp_enqueue_script('ait.admin.Tabs', "{$assetsUrl}/js/ait.admin.tabs.js", array('ait.admin', 'jquery'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.options', "{$assetsUrl}/js/ait.admin.options.js", array('ait.admin', 'jquery', 'jquery-ui-tabs', 'ait-jquery-chosen', 'jquery-ui-datepicker'), AIT_THEME_VERSION, TRUE);
			//wp_enqueue_script('ait.admin.backup', "{$assetsUrl}/js/ait.admin.backup.js", array('ait.admin', 'jquery', 'ait-jquery-filedownload'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.options.elements', "{$assetsUrl}/js/ait.admin.options.elements.js", array('ait.admin', 'ait.admin.options', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'), AIT_THEME_VERSION, TRUE);
			//wp_enqueue_script('ait.admin.nav-menus', "{$assetsUrl}/js/ait.admin.nav-menus.js", array('ait.admin', 'ait.admin.options', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'), AIT_THEME_VERSION, TRUE);

			// custom ui init
			wp_enqueue_script( 'ait-announcements-bar-admin', plugin_dir_url( __FILE__ )."design/js/admin.js", array("ait.admin.options"), '1.0', true );
			wp_enqueue_script( 'ait-announcements-bar-aitadmin', plugin_dir_url( __FILE__ )."design/js/ait-admin.js", array("ait.admin.options"), '1.0', true );
		}
	}

	public static function addOptionsPage()
	{
		$parent = class_exists('AitTheme') ? "ait-theme-options" : "options-general.php";
		$caps = class_exists('AitTheme') ? "edit_theme_options" : "manage_options";
		add_submenu_page(
			$parent,
			__('Announcements Bar', 'ait-announcements-bar'),	// Name of page
			__('Announcements Bar', 'ait-announcements-bar'),	// Name of page
			apply_filters('ait-announcements-bar-menu-permission', $caps),												// Capability required
			'ait_announcements_bar_options',					// Menu slug, used to uniquely identify the page
			array(__CLASS__, 'renderOptionsPage')				// Function that renders the options page
		);
	}



	public static function renderOptionsPage()
	{
		if(class_exists('AitTheme')){
	?>
		<div class="wrap">
			<div id="ait-announcements-bar-page" class="ait-admin-page ait-options-layout">
				<div class="ait-admin-page-wrap">

					<div class="ait-options-page-header">
						<h3 class="ait-options-header-title"><?php _e('Announcements Bar', 'ait-announcements-bar')?></h3>
						<div class="ait-options-header-tools">
							<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>
							<div class="ait-header-save">
								<button type="submit" name="submit" id="announcements-bar-save-options" class="ait-save-announcements-bar">
									<?php _e('Save Options', 'ait-announcements-bar')?>
								</button>

								<div id="action-indicator-save" class="action-indicator action-save"></div>
							</div>
						</div>

						<div class="ait-sticky-header">
							<h4 class="ait-sticky-header-title"><?php _e('Announcements Bar', 'ait-announcements-bar')?><i class="fa fa-circle"></i><span class="subtitle"></span></h4>
						</div>
					</div>

					<div class="ait-options-page">

						<div class="ait-options-page-content">
							<div class="ait-options-sidebar">
								<div class="ait-options-sidebar-content">
									<ul id="ait-announcements-bar-tabs" class="ait-options-tabs">
										<li id="ait-announcements-bar-general-panel-tab" class=""><a href="#ait-announcements-bar-general-panel"><?php _e('General', 'ait-announcements-bar')?></a></li>
									</ul>
								</div>
							</div>

							<div class="ait-options-content">
								<div class="ait-options-controls-container">
									<div id="ait-announcements-bar-panels" class="ait-options-controls ait-options-panels">
										<form action="options.php" method="post" name="">
										<?php settings_fields('ait_announcements_bar_options'); ?>
											<div id="ait-announcements-bar-general-panel" class="ait-options-group ait-options-panel ait-announcements-bar-tabs-panel">
												<div id="ait-options-basic-general" class="ait-controls-tabs-panel ait-options-basic">
													<div class="ait-options-section ">
														<?php do_settings_fields( 'ait_announcements_bar_options', 'general' ); ?>
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
		} else {
	?>
		<div class="wrap">
			<?php screen_icon() ?>
			<h2><?php _e('Announcements Bar', 'ait-announcements-bar') ?></h2>

			<form method="post" action="options.php">
				<?php
					settings_fields('ait_announcements_bar_options');
					do_settings_sections('ait_announcements_bar_options');
					submit_button();
				?>
			</form>
		</div>
	<?php
		}
	}



	public static function settingsInit()
	{
		register_setting(
			'ait_announcements_bar_options',  // Options group, see settings_fields() call in ait_theme_options_render_page()
			'ait_announcements_bar_options', // Database option, see ait_get_theme_options()
			array(__CLASS__, 'validateOptions') // The sanitization callback, see ait_theme_options_validate()
		);

		// Register our settings field group
		add_settings_section(
			'general', // Unique identifier for the settings section
			'', // Section title (we don't want one)
			'__return_false', // Section callback (we don't want anything)
			'ait_announcements_bar_options' // Menu slug, used to uniquely identify the page; see ait_theme_options_add_page()
		);

		// Register our individual settings fields
		$labelHTML = class_exists("AitTheme") ? "" : 'HTML';
		add_settings_field('html', $labelHTML, array(__CLASS__, 'renderFieldHtml'), 'ait_announcements_bar_options', 'general');
		$labelCSS = class_exists("AitTheme") ? "" : 'CSS';
		add_settings_field('css', $labelCSS, array(__CLASS__, 'renderFieldCss'), 'ait_announcements_bar_options', 'general');
		$labelDateStart = class_exists("AitTheme") ? "" : __('Start Date', 'ait-announcements-bar');
		add_settings_field('start_date', $labelDateStart, array(__CLASS__, 'renderFieldStartDate'), 'ait_announcements_bar_options', 'general');
		$labelDateEnd = class_exists("AitTheme") ? "" : __('End Date', 'ait-announcements-bar');
		add_settings_field('end_date', $labelDateEnd, array(__CLASS__, 'renderFieldEndDate'), 'ait_announcements_bar_options', 'general');
	}



	public static function validateOptions($input)
	{
		return wp_parse_args($input, self::getOptions());
	}



	public static function getDefaultOptions()
	{
		return array(
			'css'        => "#ait-announcements-bar { background-color: ivory; }\n#ait-announcements-bar p { text-align:center; }",
			'html'       => '<p>Something to announce!</p>',
			'start_date' => date('Y-m-d H:i:s', current_time('timestamp') + HOUR_IN_SECONDS),
			'end_date'   => date('Y-m-d H:i:s', current_time('timestamp') + DAY_IN_SECONDS),
		);
	}



	public static function getOptions($key = '')
	{
		// https://make.wordpress.org/themes/2014/07/09/using-sane-defaults-in-themes/
		$options = wp_parse_args(
			get_option('ait_announcements_bar_options', array()),
			self::getDefaultOptions()
		);

		if($key and isset($options[$key])){
			return $options[$key];
		}

		return $options;
	}



	public static function renderFieldCss()
	{
		if(class_exists('AitTheme')){
		?>
			<div class='ait-opt-container ait-opt-multiline-code-main'>
				<div class='ait-opt-wrap'>
					<div class='ait-opt-label'>
						<div class='ait-label-wrapper'>
							<span class='ait-label'><?php _e("CSS", 'ait-announcements-bar') ?></span>
						</div>
						<div class='ait-opt-help'>
							<span class='ait-help'><?php _e("Styles for your announcement", 'ait-announcements-bar') ?></span>
						</div>
					</div>
					<div class='ait-opt ait-opt-multiline-code'>
						<div class='ait-opt-wrapper'>
							<textarea name="ait_announcements_bar_options[css]" class="code" rows="10" cols="80"><?php echo esc_attr(self::getOptions('css')) ?></textarea>
						</div>
					</div>
				</div>
			</div>
		<?php
		} else {
		?>
		<textarea name="ait_announcements_bar_options[css]" class="code" rows="10" cols="80"><?php echo esc_textarea(self::getOptions('css')) ?></textarea>
		<?php
		}
	}



	public static function renderFieldHtml()
	{
		if(class_exists('AitTheme')){
		?>
			<div class='ait-opt-container ait-opt-multiline-code-main'>
				<div class='ait-opt-wrap'>
					<div class='ait-opt-label'>
						<div class='ait-label-wrapper'>
							<span class='ait-label'><?php _e("HTML", 'ait-announcements-bar') ?></span>
						</div>
						<div class='ait-opt-help'>
							<span class='ait-help'><?php _e("Layout for your announcement", 'ait-announcements-bar') ?></span>
						</div>
					</div>
					<div class='ait-opt ait-opt-multiline-code'>
						<div class='ait-opt-wrapper'>
							<textarea name="ait_announcements_bar_options[html]" class="code" rows="10" cols="80"><?php echo esc_attr(self::getOptions('html')) ?></textarea>
						</div>
					</div>
				</div>
			</div>
		<?php
		} else {
		?>
		<textarea name="ait_announcements_bar_options[html]" class="code" rows="10" cols="80"><?php echo esc_textarea(self::getOptions('html')) ?></textarea>
		<?php
		}
	}



	public static function renderFieldStartDate()
	{
		if(class_exists('AitTheme')){
		?>
			<div class="ait-opt-container ait-opt-code-main">
				<div class="ait-opt-wrap">

					<div class="ait-opt-label">
						<div class="ait-label-wrapper">
							<label class="ait-label"><?php _e('Start Date', 'ait-announcements-bar') ?></label>
						</div>
					</div>

					<div class="ait-opt ait-opt-code">
						<div class="ait-opt-wrapper">
							<input type="text" name="ait_announcements_bar_options[start_date]" value="<?php echo esc_attr(self::getOptions('start_date')) ?>">
						</div>
					</div>

				</div>
			</div>
		<?php
		} else {
		?>
		<input type="text" name="ait_announcements_bar_options[start_date]" value="<?php echo esc_attr(self::getOptions('start_date')) ?>">
		<?php
		}
	}



	public static function renderFieldEndDate()
	{
		if(class_exists('AitTheme')){
		?>
			<div class="ait-opt-container ait-opt-code-main">
				<div class="ait-opt-wrap">

					<div class="ait-opt-label">
						<div class="ait-label-wrapper">
							<label class="ait-label"><?php _e('End Date', 'ait-announcements-bar') ?></label>
						</div>
					</div>

					<div class="ait-opt ait-opt-code">
						<div class="ait-opt-wrapper">
							<input type="text" name="ait_announcements_bar_options[end_date]" value="<?php echo esc_attr(self::getOptions('end_date')) ?>">
						</div>
					</div>

				</div>
			</div>

			<?php
				$ts = strtotime(self::getOptions('end_date'));
				if($ts < current_time('timestamp')){
					?>
					<div class="alert alert-warning">
					<?php _e('Expired. Bar is no longer displayed.', 'ait-announcements-bar'); ?>
					</div>
					<?php
				}
			?>
		<?php
		} else {
		?>
		<input type="text" name="ait_announcements_bar_options[end_date]" value="<?php echo esc_attr(self::getOptions('end_date')) ?>">
		<?php
			$ts = strtotime(self::getOptions('end_date'));
			if($ts < current_time('timestamp')){
				_e('Expired. Bar is no longer displayed.', 'ait-announcements-bar');
			}
		?>
		<?php
		}
	}



	public static function canBeDisplayed()
	{
		$start_ts = strtotime(self::getOptions('start_date'));
		$end_ts = strtotime(self::getOptions('end_date'));
		$current_ts = current_time('timestamp'); // time with timezone offset if is set in General Settings

		return ($current_ts > $start_ts and $current_ts < $end_ts);
	}



	public static function renderAnnouncementsBar()
	{
		if(!self::canBeDisplayed()) return;
		?>
		<div id="ait-announcements-bar-wrapper">
			<div id="ait-announcements-bar">
				<?php echo apply_filters("ait-announcements-bar-render", self::getOptions('html')) ?>
			</div>
		</div>
	<?php
	}



	public static function addStyles()
	{
		if(!self::canBeDisplayed()) return;
		?><style><?php echo self::getOptions('css') ?></style><?php
	}

}