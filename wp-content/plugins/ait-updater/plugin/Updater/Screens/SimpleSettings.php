<?php

namespace Ait\Updater\Screens;

use Ait\Updater\Detector;


class SimpleSettings extends BaseSettingsScreen
{


	protected function enqueueAssets()
	{
		wp_enqueue_style('ait-updater-simple-settings-screen-css', $this->updater->url('assets') . '/simple-screen.css', array('ait-updater-base-settings-screen-css'), AIT_UPDATER_VERSION);
	}



	public function render()
	{
		?>
		<div class="wrap" id="vue-root" v-cloak>
			<h2><?php printf(_x('%s Settings', 'AIT Updater', 'ait-updater'), 'AIT Updater') ?></h2>

			<?php $this->renderFixDirnameNotice(); ?>

			<?php $this->wpNotice() ?>

			<form action="#" method="post" id="ait-updater-simple-page-form" @submit.prevent="onSubmit">
				<h2>
					<img style="vertical-align:bottom;" height="24px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAABnRSTlMAAAAAAABupgeRAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAEKUlEQVR42uVWXUxbZRj+3q/nrH8ra0s7KTAFQg3/7VaQLcroqjO6CnEwyS68MpFde4WJXOIdMfHKGAl6AZq4MJK5RaRxP2YxK5EMNlKGdMhfQ0+7cmoLbek553u9wAAuK8Iy4oXf3ck57/PkPc/zPu8HFovFaDQCAHneBxHj8Tg9IHRCCAAYjUZ6QOhbHJQc8PlfEyAqhCgEANkuX3F7gFEYxxMkVJFAxW3Jp3vtnLvlPY7jbl2/krh1lTBl/wSIauerFSfd9ooqWZYfzUxP37wmBx8AAelEs8Vx6teBL5Ap5tMtf8qM3h7Gp1lGlZ+fnwudNrfWvfGuzVaoZNYR8YjVpi6rjkgKCT1ab27L/HI1M31vI7oirizKJ9x06i5B3GsHQEimsr6gqiESDk18+XU2ugIAfMGxly5cyjve9HhhBoGilP17hqQsAiAQ2IfIjKVLqhji0nA/iwkcpSoAJiz98eN3lMBaRYNuPlB49gJ3tIiz2GxvXTwUnqeytA8NkOezeeZ0Ik7EyM7GcP5hOr2eMb1guvZVxnK09ZPPeF5148You3mF5phZLpcABBFUT9YgoYRSggwURfzhmyHfZQSgqTVK6f7mAGTpUCKm0x8BSyEhuMXK2Wu0aq02HiGKTIBqi0vzyiqQ53eJsxwuAkplSW+vLa57ZW01urEaYZTqqlxlb19MZiX282WQstr2S+WOelNxCTqbk4FxyKEB2O32nDZtPFv3essxs4mnSIDKCgkK0YBvCCbupG0lGk87DvQCU/jWD+LLc4fv38Gn+YjbJWqZ3ze5HEy436mudciyHJiaWPhpiKxGkFIEQERCkBBgjGHuVM7dwbZlFRkoQeQY4160F1bWLd3zs3hEd/7DUmMelTYE3iB8+zlNr+1Hgyf0AKAAit6gO/d+dvaBytOeDfymTPlTmcx6LBob/Z5mM88cdjs60egT4UW4f5dW1CsaPd1IrwfGN8f++cQ1L0YsOt2pj3t18Sgff7zXrfnvGvzDWkwBqmKMUHowCweoipC9o/9HK1OtVu98NJvNHMc9M8F2ZVtbW0dHR2dnp9frTSaToih2dXUpiiIIQk9Pz/Ly8u5AlW+eP2wtWIuGk9EVW9VxRZIe+oZTYmyb4MyZMyqVqra21ul0ulyugYEBn88XCoUaGxv7+vpGRkY8Hg8hJBgM2u12k8k0ODjY3t7u8/m8Xm93d7eu4XRs/vf80peLnSc31pNqvUFrzN8m4DiupqZGEISmpiZCyPj4+OLiYnV19ebb/v5+j8dTVFTk9/t5np+ZmSGEOBwOURQFQQgEAgaD4fqnH+32i0wmU29v7+TkpMvlSqVS0WjUarWOjY0lEolkMhkKhURRHB0dtVqtc3NzmyXhcNjtdi8sLBgMhtnZ2Zy+Ky8vP7jrKSKqKKUajebgru9/AVc83I8ViiPzAAAAAElFTkSuQmCC">
					<?php _e('AitThemes.Club API Credentials', 'ait-updater') ?>
				</h2>

				<?php
					$this->renderCredentialsFormFields();
					$this->renderBackupFormFields();
				?>
				<p class="submit">
					<input type="submit" name="submit" id="submit" value="<?php esc_html_e('Save Settings', 'ait-admin') ?>" class="button button-primary">
				</p>
			</form>

		</div>
		<?php
	}



	protected function renderCredentialsFormFields()
	{
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<?php $this->label('username', __('Username', 'ait-updater')) ?>
					</th>
					<td>
						<?php $this->input('username', array('class' => 'large-text code')) ?>
						<?php $this->inputErrors('username') ?>
						<p class="description"><?php _e('Username which you are using to login to AitThemes.club.', 'ait-updater') ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php $this->label('api_key', __('API Key', 'ait-updater')) ?>
					</th>
					<td>
						<?php $this->input('api_key', array('class' => 'large-text code')) ?>
						<?php $this->inputErrors('api_key') ?>
						<p class="description">
							<?php
							/* translators: Those %s are opening and closing HTML tags, wrapping that text between them */
							printf(__('You can get your API key in your %saccount on AitThemes.club%s.', 'ait-updater'), '<strong><a href="https://system.ait-themes.club/account/api" target="_blank">', '</strong></a>')
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}



	protected function renderBackupFormFields()
	{
		?>
		<h2><?php _e('Backup', 'ait-updater') ?></h2>
		<table class="form-table">
			<tbody>
				<?php if( ! class_exists('\ZipArchive')): ?>
				<tr>
					<td>
						<div class="notice notice-error inline">
							<p>
								<?php _e('PHP ZipArchive class for making zip files from PHP is not available, therefore backup option and backuping old versions is not available for you. Please contact server admin.', 'ait-updater') ?>
							</p>
						</div>
					</td>
				</tr>
				<?php else: ?>
				<tr>
					<th scope="row">
						<label for="do_backup"><?php _e('Do backup', 'ait-updater') ?></label>
					</th>
					<td>
						<p class="description">
							<input type="checkbox" v-model="fields.do_backup" :true-value="1" :false-value="0" id="do_backup">
							<?php printf(__('Whether to do backup of old version plugin or theme before update. Backups will be stored in %s.', 'ait-updater'), '' . str_replace(ABSPATH, '', $this->updater->path('backups')) . '</small>' ) ?>
						</p>
						<?php
							$oldBackups = glob($this->updater->path('backups') . '/ait-*.zip' );
							echo '<h4>' . __('List of the old backups', 'ait-updater'). '</h4>';
							if(!empty($oldBackups)){
								echo '<pre>';
								foreach($oldBackups as $file){
									echo '<a href="' . esc_url($this->updater->url('backups') . '/' . basename($file)) . '">' . basename($file) . '</a><br>';
								}
								echo '</pre>';
							}else{
								_e('No backups yet.', 'ait-updater');
							}
						?>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}
