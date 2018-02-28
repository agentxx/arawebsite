<?php

namespace Ait\Updater;



class ThemeDirnameFixer
{


	public static function run()
	{
		$result = array();

		$r = Detector::checkIfActiveThemeIsRenamed();

		if($r['valid']) return $result;

		$themesDir = dirname($r['path']);
		$correctDirname = "{$themesDir}/{$r['codename']}";

		if(!file_exists($correctDirname)){

			if(@rename($r['path'], $correctDirname) === true){

				$fw2OptionsFixed = self::fixThemeOptionsFw2($r);
				$fw1OptionsFixed = self::fixThemeOptionsFw1($r);

				if($fw2OptionsFixed or $fw1OptionsFixed){

					self::fixWpOptions($r);

					$result = array(
						'msg' => sprintf(__('Fix was applied successfuly. You will able to get updates for your %1$s theme', 'ait-updater'), $r['type'] == 'parent' ? $r['parent_theme_name'] : $r['theme_name']),
						'type' => 'success',
					);
				}else{
					$result = array(
						'msg' => sprintf(__('Renaming was not successful. Failed to rename option keys in the database for the Theme Options and Page Builder.', 'ait-updater'), $r['dir_name'], $r['codename']),
						'type' => 'error',
					);
					if(file_exists($correctDirname)){ // rename back the dir
						@rename($correctDirname, $r['path']);
					}
				}
			}else{
				$result = array(
					'msg' => sprintf(__('There was problem with renaming folder %1$s. Most probably it was due to low write permissions. Try to change permissions on the theme folder and run this fix again. Or you can rename it manualy from %1$s to %2$s and run the fix again.', 'ait-updater'), $r['dir_name'], $r['codename']),
					'type' => 'error',
				);
			}
		}else{
			$result = array(
				'msg' => sprintf(__('There is folder with name %1$s already. So fix can not continue, because we can not detect what folder it is. Maybe it is some old / empty unused folder. Try rename it to something different then %1$s a run fix again.', 'ait-updater'), $r['codename']),
				'type' => 'warning',
			);
		}

		return $result;
	}



	protected static function fixThemeOptionsFw2($r)
	{
		global $wpdb;

		$escDirname  = esc_sql($r['dir_name']);
		$escCodename = esc_sql($r['codename']);

		$wrongNameLike   = "\\_ait\\_{$escDirname}\\_%\\_opts%";
		$correctNameLike = "\\_ait\\_{$escCodename}\\_%\\_opts%";
		$wrongName       = "_ait_{$escDirname}_";
		$correctName     = "_ait_{$escCodename}_";
		$backupName      = "_ait_{$escCodename}@backup_";

		$existenceTestSql = $wpdb->prepare("SELECT `option_id` from {$wpdb->options} WHERE `option_name` LIKE %s", $wrongNameLike);
		$backupSql = $wpdb->prepare("UPDATE {$wpdb->options} SET `option_name` = REPLACE(`option_name`, %s, %s) WHERE `option_name` LIKE %s", $correctName, $backupName, $correctNameLike);
		$renameSql = $wpdb->prepare("UPDATE {$wpdb->options} SET `option_name` = REPLACE(`option_name`, %s, %s) WHERE `option_name` LIKE %s", $wrongName, $correctName, $wrongNameLike);

		$toBeRenamed = $wpdb->query($existenceTestSql);
		if($toBeRenamed > 0){
			$backup = $wpdb->query($backupSql);
			if($backup !== false){
				$renamed = $wpdb->query($renameSql);
				return ($renamed === $toBeRenamed);
			}else{
				return false;
			}
		}else{
			return true; // nothing to rename
		}
	}



	protected static function fixThemeOptionsFw1($r)
	{
		global $wpdb;

		$escDirname  = esc_sql($r['dir_name']);
		$escCodename = esc_sql($r['codename']);

		$wrongNameLike   = "ait\\_{$escDirname}\\_%";
		$correctNameLike = "ait\\_{$escCodename}\\_%";
		$wrongName       = "ait_{$escDirname}_";
		$correctName     = "ait_{$escCodename}_";
		$backupName      = "ait_{$escCodename}@backup_";

		$existenceTestSql = $wpdb->prepare("SELECT `option_id` from {$wpdb->options} WHERE `option_name` LIKE %s", $wrongNameLike);
		$backupSql = $wpdb->prepare("UPDATE {$wpdb->options} SET `option_name` = REPLACE(`option_name`, %s, %s) WHERE `option_name` LIKE %s", $correctName, $backupName, $correctNameLike);
		$renameSql = $wpdb->prepare("UPDATE {$wpdb->options} SET `option_name` = REPLACE(`option_name`, %s, %s) WHERE `option_name` LIKE %s", $wrongName, $correctName, $wrongNameLike);

		$toBeRenamed = $wpdb->query($existenceTestSql);
		if($toBeRenamed > 0){
			$backup = $wpdb->query($backupSql);
			if($backup !== false){
				$renamed = $wpdb->query($renameSql);
				return ($renamed === $toBeRenamed);
			}else{
				return false;
			}
		}else{
			return true; // nothing to rename
		}
	}



	protected static function fixWpOptions($r)
	{
		self::fixTemplateAndStylesheet($r);

		delete_option("_ait_{$r['dir_name']}_skeleton_version"); // AitUpgrader will add this with correct name
		delete_option("_ait_{$r['dir_name']}_parent_theme_version"); // AitUpgrader will add this with correct name
		delete_option("_ait_{$r['dir_name']}_theme_version"); // AitUpgrader will add this with correct name

		$themeMods = get_option('theme_mods_' . $r['dir_name']);

		update_option('theme_mods_' . $r['dir_name'] . '@backup', $themeMods);
		update_option('theme_mods_' . $r['codename'], $themeMods);

		update_option('current_theme', $r['type'] == 'normal' ? $r['theme_name'] : $r['parent_theme_name']);

		self::fixPolylang($r);
	}



	protected static function fixTemplateAndStylesheet($r)
	{
		if($r['type'] === 'parent'){
			update_option('template', $r['codename']);
			$style = $r['path_child'] . '/style.css';
			$content = @file_get_contents($style);
			$content = str_replace($r['dir_name'], $r['codename'], $content);
			@file_put_contents($style, $content);
		}else{
			update_option('template', $r['codename']);
			update_option('stylesheet', $r['codename']);
		}
	}



	protected static function fixPolylang($r)
	{
		$polylang = get_option('polylang', array());
		update_option('polylang@backup', $polylang);
		if(!empty($polylang) and isset($polylang['nav_menus'][$r['dir_name']])){
			$polylang['nav_menus'][$r['codename']] = $polylang['nav_menus'][$r['dir_name']];
			unset($polylang['nav_menus'][$r['dir_name']]);
			update_option('polylang', $polylang);
		}
	}
}