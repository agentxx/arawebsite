<?php

namespace Ait\Updater\Screens;

use Ait\Updater\Detector;
use Ait\Updater\Settings;


abstract class BaseSettingsScreen
{
	protected $updater;

	public $pageHookname;



	public function __construct($updater)
	{
		$this->updater = $updater;

		add_action('admin_enqueue_scripts', array($this, 'enqueueBaseAssets'));
		add_action('admin_enqueue_scripts', array($this, 'enqueueScriptsOnPageHookname'));
	}



	public function enqueueBaseAssets($hookName)
	{
		if($this->pageHookname === $hookName){

			wp_enqueue_style('ait-updater-base-settings-screen-css', $this->updater->url('assets') . '/base-settings-screen.css', array(), AIT_UPDATER_VERSION);

			wp_enqueue_script('ait-updater-vuejs', $this->updater->url('assets') . '/vue.min.js', array(), '2.1.10', true);
			wp_enqueue_script('ait-updater-settings', $this->updater->url('assets') . '/settings.js', array(), AIT_UPDATER_VERSION, true);

			wp_localize_script('ait-updater-settings', '_AitUpdaterSettings', array(
				'fields' => Settings::all(),
				'validationErrors' => Settings::getValidationErrorMessages(),
				'wpNotice' => array(
					'type' => '',
					'msg' => ''
				),
			));
		}
	}



	public function enqueueScriptsOnPageHookname($hookName)
	{
		if($this->pageHookname === $hookName){
			$this->enqueueAssets();
		}
	}



	protected function enqueueAssets()
	{
		// child class extends this
	}



	protected function renderFixDirnameNotice()
	{
		$theme = Detector::checkIfActiveThemeIsRenamed();

		if( ! $theme['valid']):
			?>
			<div class="notice notice-warning inline" v-if="showFixDirnameNotice">
			<?php
				$url = wp_nonce_url(add_query_arg(array('action' => 'fix-dirname')), 'actionfixdirname');

				if($theme['type'] == 'parent'){
					$text = __('We detected that you probably renamed folder name of the %1$s theme, which you are using as settings theme for the currently active theme. This will cause problems with settings theme updates. The current name of the theme folder is %2$s and has to be %3$s.', 'ait-updater');
				}else{
					$text = __('We detected that you probably renamed folder name of the currently active <strong>%1$s</strong> theme and this will cause problems with theme updates. The current name of the theme folder is %2$s and has to be %3$s.', 'ait-updater');
				}

				$themeName = $theme['type'] === 'parent' ? 'parent_theme_name' : 'theme_name';
				$msg = sprintf($text, "<strong>{$theme[$themeName]}</strong>", "<code><strong>{$theme['dir_name']}</strong></code>", "<code><strong>{$theme['codename']}</strong></code>");

				echo "<p>{$msg}</p>";

				?>
				<p><a href="<?php echo $url ?>" class="button button-primary" @click.prevent="fixDirname"><?php _e('Fix it', 'ait-admin') ?></a></p>
				<?php
			?>
			</div>
			<?php
		endif;
	}



	protected function wpNotice()
	{
		?>
		<div v-if="wpNotice.type" :class="['notice-' + wpNotice.type, 'notice', 'inline']">
			<p><strong>{{ wpNotice.msg }}</strong></p>
		</div>
		<?php
	}



	protected function attrs($attrs)
	{
		return array_reduce(array_keys($attrs), function($carry, $key) use ($attrs) {
				return $carry . ' ' . $key . '="' . $attrs[$key] . '"';
			}, ''
		);
	}



	protected function label($for, $text, $attrs = array())
	{
		$strAttrs = $this->attrs($attrs);
		echo "<label $strAttrs for=\"field-$for\">$text</label>";
	}



	protected function input($field, $attrs = array())
	{
		$attrs = array_merge_recursive(array(
			'id'      => 'field-' . $field,
			'v-model' => "fields.{$field}",
			':class'  => "{'ait-field-error': hasErrors.{$field}}",
		), $attrs);
		?>
		<input type="text" <?php echo $this->attrs($attrs) ?>>
		<?php
	}



	protected function inputErrors($field)
	{
		$vIf = "hasErrors.{$field}";
		$vFor = "errorMsg in inputErrors.{$field}";
		?>
		<div v-if="<?php echo $vIf ?>" class="ait-updater-error-msg">
			<p v-for="<?php echo $vFor ?>">{{ errorMsg }}</p>
		</div>
		<?php
	}
}
