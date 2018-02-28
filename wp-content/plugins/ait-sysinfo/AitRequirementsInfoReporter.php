<?php


class AitRequirementsInfoReporter
{

	public static function report()
	{
		$reporter = new self;
		?>
		<div id="ait-requirements-info-report">
			<?php echo $reporter->getReport(); ?>
		</div>
		<?php
	}



	public function getReport()
	{
		$report = (object) null;
		$report->errors = false;
		$report->warnings = false;

		foreach(array('version_compare', 'extension_loaded', 'ini_get') as $function){
			if(!function_exists($function)){
				$report->errors = false;
				break;
			}
		}

		$requirements = $this->getRequirements();

		foreach($requirements as $id => $requirement){
			$requirements[$id] = $requirement = (object) $requirement;
			if(isset($requirement->passed) && !$requirement->passed){
				if($requirement->required){
					$report->errors = true;
				}else{
					$report->warnings = true;
				}
			}
		}


		ob_start();
		if($report->errors || $report->warnings){
			?>
			<table>
			<?php foreach ($requirements as $index => $requirement): ?>

				<?php if(!empty($requirement->passed)) continue; ?>

				<?php $class = isset($requirement->passed) ? ($requirement->passed ? 'passed' : ($requirement->required ? 'failed' : 'warning')) : 'info' ?>

				<tr index="res<?php echo($index) ?>" class="<?php echo($class) ?>">
					<th><?php echo htmlSpecialChars($requirement->title) ?></th>
				</tr>

				<?php if (isset($requirement->description)): ?>
				<tr id="desc<?php echo($index) ?>" class="<?php echo ( $class) ?> description">
					<td colspan="2"><?php echo( htmlSpecialChars(strip_tags($requirement->description))) ?></td>
				</tr>
				<?php endif ?>

				<tr class="<?php echo($class) ?> description">
					<?php if (empty($requirement->passed) && isset($requirement->errorMessage)): ?>

						<td><?php printf(__('Current value: %s', 'ait-sysinfo'), '<strong>' . htmlSpecialChars($requirement->errorMessage)) . '</strong>' ?></td>

					<?php elseif (isset($requirement->message)): ?>

						<td><?php echo htmlSpecialChars($requirement->message) ?></td>

					<?php elseif (isset($requirement->passed)): ?>

						<td><?php echo ($requirement->passed ? __('Enabled', 'ait-sysinfo') : __('Disabled', 'ait-sysinfo')) ?></td>

					<?php else: ?>

						<td><?php _e('Not tested', 'ait-sysinfo') ?></td>

					<?php endif ?>
				</tr>

			<?php endforeach ?>
			</table>
			<?php
		}else{
			?>
			<div class="alert alert-success">
				<strong><?php _e('Your hosting meets the requirements, there is no issue.', 'ait-sysinfo') ?></strong>
			</div>
			<?php
		}

		return ob_get_clean();
	}



	public function getRequirements()
	{
		global $wp_version;
		$requirements = array();

		$requirements[] = array(
			'title'        => __('WordPress version', 'ait-sysinfo'),
			'required'     => true,
			'passed'       => version_compare("4.0", $wp_version, '<='),
			'errorMessage' => $wp_version,
			'description'  => __('This AIT theme requires WordPress 4.0 or newer.', 'ait-sysinfo'),
		);


		$requirements[] = array(
			'title'        => __('PHP Version', 'ait-sysinfo'),
			'required'     => true,
			'passed'       => version_compare(PHP_VERSION, '5.3.1', '>='),
			'errorMessage' => PHP_VERSION,
			'description'  => __('PHP version must be at least 5.3.1. But we recommend you to switch to the most recent versions of PHP 7, because 5.3 branch reached end of life years ago and is no longer maintained, and you are risking serious security and performance issues.', 'ait-sysinfo'),
		);

		$mb = 96;
		$limitPassed = (intval(ini_get('memory_limit')) == -1 or intval(ini_get('memory_limit')) >= $mb);

		$requirements[] = array(
			'title'        => __('Memory limit', 'ait-sysinfo'),
			'required'     => FALSE,
			'passed'       => $limitPassed,
			'errorMessage' => ini_get('memory_limit'),
			'description'  => sprintf(__('Memory limit is too low. At least <b>%sMB</b> are needed for the AIT themes and plugins to work properly.', 'ait-sysinfo'), $mb),
		);

		$requirements[] = array(
			// translators: "wp-content/uploads" is exact path to folder
			'title'        => __('Read/Write permissions for wp-content/uploads folder', 'ait-sysinfo'),
			'required'     => true,
			'passed'       => $this->canWriteToUploadDir(),
			'message'      => __('Writeable', 'ait-sysinfo'),
			'errorMessage' => __('Unwritable', 'ait-sysinfo'),
			'description'  => __('PHP is not allowed to write to "wp-content/uploads" directory', 'ait-sysinfo'),
		);

		$requirements[] = array(
			'title'       => __('Function flock()', 'ait-sysinfo'),
			'required'    => FALSE,
			'passed'      => flock(fopen(__FILE__, 'r'), LOCK_SH),
			'description' => __('Function <code>flock()</code> is not supported on this filesystem. The AIT theme requires this to process atomic file operations.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			'title'       => __('Function gzcompress()', 'ait-sysinfo'),
			'required'    => FALSE,
			'passed'      => function_exists('gzcompress'),
			'description' => __('Function <code>gzcompress()</code> is not enabled. The AIT theme require it for data backuping process.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			'title'       => __('Function gzuncompress()', 'ait-sysinfo'),
			'required'    => FALSE,
			'passed'      => function_exists('gzuncompress'),
			'description' => __('Function <code>gzuncompress()</code> is not enabled. The AIT theme require it for data backuping process.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			'title'        => 'register_globals',
			'required'     => FALSE,
			'passed'       => !$this->iniFlag('register_globals'),
			'message'      => __('Disabled', 'ait-sysinfo'),
			'errorMessage' => __('Enabled', 'ait-sysinfo'),
			'description'  => __('Configuration directive <code>register_globals</code> is enabled. We recommend this to be disabled for security reasons.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			// translators: "Reflection" is the name of the extension
			'title'       => __('Reflection extension', 'ait-sysinfo'),
			'required'    => true,
			'passed'      => class_exists('ReflectionFunction'),
			'description' => __('Reflection extension is required.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			// translators: "SPL" is the name of the extension
			'title'       => __('SPL extension', 'ait-sysinfo'),
			'required'    => true,
			'passed'      => extension_loaded('SPL'),
			'description' => __('SPL extension is required.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			// translators: "PCRE" is the name of the extension
			'title'        => __('PCRE extension', 'ait-sysinfo'),
			'required'     => true,
			'passed'       => extension_loaded('pcre') && @preg_match('/pcre/u', 'pcre'),
			'message'      => __('Enabled and works properly', 'ait-sysinfo'),
			'errorMessage' => __('Disabled or without UTF-8 support', 'ait-sysinfo'),
			'description'  => __('PCRE extension is required and must support UTF-8.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			// translators: "ICONV" is the name of the extension
			'title'        => __('ICONV extension', 'ait-sysinfo'),
			'required'     => true,
			'passed'       => extension_loaded('iconv') && (ICONV_IMPL !== 'unknown') && @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', 'test')) === 'test',
			'message'      => __('Enabled and works properly', 'ait-sysinfo'),
			'errorMessage' => __('Disabled or does not work properly', 'ait-sysinfo'),
			'description'  => __('ICONV extension is required and must work properly.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			'title'       => 'PHP tokenizer',
			'required'    => true,
			'passed'      => extension_loaded('tokenizer'),
			'description' => __('PHP tokenizer is required.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			// translators: "Multibyte String" is the name of the extension
			'title'       => __('Multibyte String extension', 'ait-sysinfo'),
			'required'    => FALSE,
			'passed'      => extension_loaded('mbstring'),
			'description' => __('Multibyte String extension is absent. Some internationalization components may not work properly.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			// translators: "Multibyte String" is the name of the extension
			'title'        => __('Multibyte String function overloading', 'ait-sysinfo'),
			'required'     => FALSE,
			'passed'       => (!extension_loaded('mbstring') || !(mb_get_info('func_overload') & 2)),
			'message'      => __('Disabled', 'ait-sysinfo'),
			'errorMessage' => __('Enabled', 'ait-sysinfo'),
			'description'  => __('Multibyte String function overloading is enabled. The AIT theme requires this to be disabled. If it is enabled, some string function may not work properly.', 'ait-sysinfo'),
		);

		$requirements[] = array(
			// translators: "Multibyte String" is the name of the extension
			'title'       => __('GD/ImageMagick extension', 'ait-sysinfo'),
			'required'    => true,
			'passed'      => (extension_loaded('gd') || extension_loaded('imagick')),
			'description' => __('GD and ImageMagick extensions are absent. WordPress requires any of them for image resizing.', 'ait-sysinfo'),
		);

		if(defined('AIT_LANGUAGES_ENABLED')){
			$requirements[] = array(
				'title'        => __('Max input variables', 'ait-sysinfo'),
				'required'     => FALSE,
				'passed'       => (intval(ini_get('max_input_vars')) >= 3000),
				'errorMessage' => ini_get('max_input_vars'),
				'description'  => __('Due to multilingual support in the theme, it is recommended that max_input_vars PHP option in php.ini config file is at least <b>3000</b>.', 'ait-sysinfo'),
			);
		}

		return $requirements;
	}



	private function canWriteToUploadDir()
	{
		$uploadDir = wp_upload_dir();
		return is_writable($uploadDir['basedir']);
	}



	protected function iniFlag($var)
	{
		$status = strtolower(ini_get($var));
		return $status === 'on' || $status === 'true' || $status === 'yes' || (int) $status;
	}
}