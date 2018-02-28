<?php

namespace Ait\Updater\Screens;

use Ait\Updater\Detector;


class BrandedSettings extends BaseSettingsScreen
{


	public function enqueueAssets()
	{
		$assetsUrl = aitPaths()->url->admin . '/assets';

		wp_enqueue_style('jquery-switch', "{$assetsUrl}/libs/jquery-switch/jquery.switch.css", array(), '0.4.1');
		wp_enqueue_style('ait-admin-style', "{$assetsUrl}/css/style.css", array(), AIT_THEME_VERSION);
		wp_enqueue_style('ait-admin-options-controls', "{$assetsUrl}/css/options-controls.css", array('ait-admin-style'), AIT_THEME_VERSION);

		wp_enqueue_style('ait-updater-branded-settings-screen-css', $this->updater->url('assets') . '/branded-screen.css', array('ait-updater-base-settings-screen-css'), AIT_UPDATER_VERSION);

		wp_enqueue_script('ait.admin', "{$assetsUrl}/js/ait.admin.js", array('underscore'), AIT_THEME_VERSION, TRUE);
		wp_enqueue_script('ait-jquery-switch', "{$assetsUrl}/libs/jquery-switch/jquery.switch.min.js", array('jquery', 'ait.admin'), FALSE, TRUE);
		wp_enqueue_script('ait.admin.Tabs', "{$assetsUrl}/js/ait.admin.tabs.js", array('ait.admin', 'jquery'), AIT_THEME_VERSION, TRUE);
	}



	public function render()
	{
		?>
		<div class="wrap" id="vue-root" v-cloak>

			<?php $this->renderFixDirnameNotice(); ?>

			<div id="ait-updater-page" class="ait-admin-page ait-options-layout">
				<div class="ait-admin-page-wrap">

					<div class="ait-options-page-header">
						<h3 class="ait-options-header-title"><?php printf(_x('%s Settings', 'AIT Updater', 'ait-updater'), 'AIT Updater') ?></h3>
						<div class="ait-options-header-tools">
							<a class="ait-scroll-to-top"><i class="fa dashicons dashicons-arrow-up-alt2" style="line-height: 14px;height: auto;width: auto; font-weight: 700;vertical-align: initial;"></i></a>
							<div class="ait-header-save">
								<button class="ait-save-updater-settings" @click.prevent="onSubmit">
									<?php esc_html_e('Save Settings', 'ait-admin') ?>
								</button>

								<div id="action-indicator-save" :class="['action-indicator action-save', savingState]" ref="action-indicator-save"></div>
							</div>
						</div>

						<div class="ait-sticky-header">
							<h4 class="ait-sticky-header-title"><?php printf(_x('%s Settings', 'AIT Updater', 'ait-updater'), 'AIT Updater') ?><i class="fa fa-circle"></i><span class="subtitle"></span></h4>
						</div>
					</div>

					<?php $this->wpNotice() ?>


					<div class="ait-options-page">

						<div class="ait-options-page-content" id="vue-root">

							<div class="ait-options-sidebar">
								<div class="ait-options-sidebar-content">
									<ul id="ait-updater-tabs" class="ait-options-tabs">
										<li id="ait-updater-credentials-panel-tab">
											<a href="#ait-updater-credentials-panel"><?php _e('AitThemes.Club API Credentials', 'ait-updater') ?></a>
										</li>
										<li id="ait-updater-backup-panel-tab">
											<a href="#ait-updater-backup-panel"><?php _e('Themes &amp; Plugins Backup', 'ait-updater')?></a>
										</li>
									</ul>
								</div>
							</div>

							<div class="ait-options-content">
								<div class="ait-options-controls-container">
									<div id="ait-updater-panels" class="ait-options-controls ait-options-panels">

										<form action="#" method="post" id="ait-updater-branding-page-form">

											<div id="ait-updater-credentials-panel" class="ait-options-group ait-options-panel ait-updater-tabs-panel">
												<div id="ait-updater-credentials-options-basic" class="ait-controls-tabs-panel ait-options-basic">
													<div class="ait-options-section ">
														<?php $this->renderCredentialsFormFields() ?>
													</div>
												</div>
											</div>

											<div id="ait-updater-backup-panel" class="ait-options-group ait-options-panel ait-updater-tabs-panel">
												<div id="ait-options-basic-requirements" class="ait-controls-tabs-panel ait-options-basic">
													<div class="ait-options-section ">
														<?php $this->renderBackupFormFields() ?>
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
		<script>
			jQuery(function(){
				new ait.admin.Tabs(jQuery('#ait-updater-tabs'), jQuery('#ait-updater-panels'), 'ait-updater-settings-page');
			})
		</script>
		<?php
	}



	protected function renderCredentialsFormFields()
	{
		?>
		<div class="ait-opt-container ait-opt-code-main">
			<div class="ait-opt-wrap">

				<div class="ait-opt-label">
					<div class="ait-label-wrapper">
						<?php $this->label('username', __('Username', 'ait-updater'), array('class' => 'ait-label')) ?>
					</div>
				</div>

				<div class="ait-opt ait-opt-code">
					<div class="ait-opt-wrapper">
						<?php $this->input('username') ?>
					</div>
					<?php $this->inputErrors('username') ?>
					<div class="ait-help">
						<?php _e('Username which you are using to login to AitThemes.club.', 'ait-updater') ?>
					</div>
				</div>

			</div>
		</div>


		<div class="ait-opt-container ait-opt-code-main">
			<div class="ait-opt-wrap">

				<div class="ait-opt-label">
					<div class="ait-label-wrapper">
						<?php $this->label('api_key', __('API Key', 'ait-updater'), array('class' => 'ait-label')) ?>
					</div>
				</div>

				<div class="ait-opt ait-opt-code">
					<div class="ait-opt-wrapper">
						<?php $this->input('api_key') ?>
					</div>
					<?php $this->inputErrors('api_key') ?>
					<div class="ait-help">
						<?php
						/* translators: Those %s are opening and closing HTML tags, wrapping that text between them */
						printf(__('You can get your API key in your %saccount on AitThemes.club%s.', 'ait-updater'), '<strong><a href="https://system.ait-themes.club/account/api" target="_blank">', '</strong></a>')
						?>
					</div>
				</div>

			</div>
		</div>

		<?php
	}



	protected function renderBackupFormFields()
	{
		if( ! class_exists('\ZipArchive')): ?>
			<div class="notice notice-error inline">
				<p>
					<?php _e('PHP ZipArchive class for making zip files from PHP is not available, therefore backup option and backuping old versions is not available for you. Please contact server admin.', 'ait-updater') ?>
				</p>
			</div>

		<?php else: ?>

		<div class="ait-opt-container ait-opt-on-off-main">
			<div class="ait-opt-wrap">

				<div class="ait-opt-label">
					<div class="ait-label-wrapper">
						<span class="ait-label"><?php _e('Do backup', 'ait-updater') ?></span>
					</div>
				</div>

				<div class="ait-opt ait-opt-on-off">
					<div class="ait-opt-wrapper">
						<div class="ait-opt-switch">
							<select v-model="fields.do_backup" id="do_backup_field">
								<option value="1">On</option>
								<option value="0">Off</option>
							</select>
						</div>
					</div>
				</div>

				<div class="ait-opt-help">
					<div class="ait-help">
						<?php printf(__('Whether to do backup of old version plugin or theme before update. Backups will be stored in %s.', 'ait-updater'), '' . str_replace(ABSPATH, '', $this->updater->path('backups')) . '</small>' ) ?>
					</div>
				</div>
			</div>
		</div>

		<div class="ait-opt-container ait-opt-background-main">
			<div class="ait-opt-wrap">

				<div class="ait-opt-label">
					<div class="ait-label-wrapper">
						<span class="ait-label"><?php _e('List of the old backups', 'ait-updater') ?></span>
					</div>
				</div>

				<div class="ait-opt ait-opt-background">
					<div class="ait-opt-wrapper">
						<?php
							$oldBackups = glob($this->updater->path('backups') . '/ait-*.zip' );
							if(!empty($oldBackups)){
								echo '<pre>';
								foreach($oldBackups as $file){
									echo '<a href="' . esc_url($this->updater->url('backups') . '/' . basename($file)) . '">' . basename($file) . '</a><br>';
								}
								echo '</pre>';
							}else{
								echo "<span class='ait-help'>";
								_e('No backups yet.', 'ait-updater');
								echo "</span>";
							}
						?>
					</div>
				</div>
			</div>
		</div>

		<?php endif;
	}
}
