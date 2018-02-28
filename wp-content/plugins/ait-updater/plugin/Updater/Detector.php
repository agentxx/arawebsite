<?php

namespace Ait\Updater;


class Detector
{
    protected static $detectedAitThemes;

    protected static $detectedAitPlugins;

    protected static $themePhpFile = array('codename' => '', 'package' => '');



    protected static function searchForAitThemes()
    {
        if(self::$detectedAitThemes === null){
            $themes = wp_get_themes();

            self::$detectedAitThemes = array('club' => array(), 'themeforest' => array());

            foreach($themes as $theme){
                self::loadThemePhpFile($theme);

                $found = self::searchByCodenameAndPackage($theme); // all fw2 themes and fw1 themes with AIT/@theme.php file

                if(!$found){
                    self::searchByAuthorHeader($theme); // all fw1 themes without AIT/@theme.php file
                }

                self::resetThemePhpData();
            }
        }

        return self::$detectedAitThemes;
    }



    protected static function searchByCodenameAndPackage($theme)
    {
        $found = false;

        if(self::isThemeDirnameCodename($theme->template) and !self::isSkeleton($theme->template)){
            $codename = self::getCodename();

            if(self::isThemeForThemeforest($theme)){
                self::$detectedAitThemes['themeforest'][$codename] = array(
                    'version' => $theme->get('Version'),
                    'package' => self::getPackage(),
                );
            }else{
                self::$detectedAitThemes['club'][$codename] = array(
                    'version' => $theme->get('Version'),
                    'package' => self::getPackage(),
                );
            }

            $found = true;
        }

        return $found;
    }



    protected static function searchByAuthorHeader($theme)
    {
        $codename = $theme->template;
        $author = str_replace(array('.', '-', '/'), '', strtolower($theme->get('Author')));
        if($author === 'aitthemescom'){
            if(file_exists($theme->template_dir . '/AIT/ait-bootstrap.php')){
                self::$detectedAitThemes['club'][$codename] = array(
                    'version' => $theme->get('Version'),
                    'package' => self::getPackage(),
                );
            }
        }elseif(
            $author === 'aitthemesclub' and
            !self::isSkeleton($theme->template) and // built themes do not have 'Template:' header, so our dev child themes are not detected
            !self::isSkeleton($theme->stylesheet)  // also skip Skeleton itself
        ){
            if(self::isThemeForThemeforest($theme)){
                self::$detectedAitThemes['themeforest'][$codename] = array(
                    'version' => $theme->get('Version'),
                    'package' => self::getPackage(),
                );
            }else{
                self::$detectedAitThemes['club'][$codename] = array(
                    'version' => $theme->get('Version'),
                    'package' => self::getPackage(),
                );
            }
        }
    }



    public static function checkIfActiveThemeIsRenamed()
    {
        $stylesheet = get_option('stylesheet');
        $template = get_option('template');

        $themes = wp_get_themes();

        $isActiveTheme = function($theme) use($stylesheet)
        {
            return ($theme->stylesheet === $stylesheet);
        };


        $isChildTheme = function($theme) use($stylesheet, $template)
        {
            return ($stylesheet !== $template and $theme->stylesheet === $stylesheet);
        };


        $result = array(
            'path'              => '',
            'theme_name'        => '',
            'parent_theme_name' => '',
            'dir_name'          => '',
            'codename'          => '',
            'valid'             => true,
            'type'              => 'normal',
        );

        if(is_multisite() and is_network_admin()){
            return $result;
        }

        foreach($themes as $theme){
            self::loadThemePhpFile($theme);

            if($isActiveTheme($theme) and self::getCodename()){

                $result['theme_name'] = $theme->name;

                if($isChildTheme($theme)){ // check the parent theme instead
                    if(self::getCodename()){
                        $result['parent_theme_name'] = $theme->parent()->name;
                        $result['path'] = $theme->template_dir;
                        $result['path_child'] = $theme->stylesheet_dir;
                        $result['dir_name'] = basename($theme->template_dir);
                        $result['codename'] = self::getCodename();
                        $result['type'] = 'parent';
                        if(!self::isThemeDirnameCodename($theme->template)){
                            $result['valid'] = false;
                            break;
                        }
                    }
                }else{
                    if(self::getCodename()){
                        $result['path'] = $theme->stylesheet_dir;
                        $result['dir_name'] = basename($theme->stylesheet_dir);
                        $result['codename'] = self::getCodename();
                        if(!self::isThemeDirnameCodename($theme->stylesheet)){
                            $result['valid'] = false;
                            break;
                        }
                    }
                }
            }
            self::resetThemePhpData();
        }

        return $result;
    }



    public static function getAllAitThemes()
    {
        $themes = self::searchForAitThemes();
        return array_merge($themes['club'], $themes['themeforest']);
    }



    public static function getAitClubThemes()
    {
        $themes = self::searchForAitThemes();
        return $themes['club'];
    }



    public static function isThereAnyAitClubThemes()
    {
        return (bool) count(self::getAitClubThemes());
    }



    public static function getAitThemeforestThemes()
    {
        $themes = self::searchForAitThemes();
        return $themes['themeforest'];
    }



    public static function isThereAnyThemeforestThemes()
    {
        return (bool) count(self::getAitThemeforestThemes());
    }



    public static function isThemeForThemeforest($theme)
    {
        return (self::$themePhpFile['package'] === 'themeforest');
    }



    public static function getAitPlugins()
    {
        if(self::$detectedAitPlugins === null){

            $plugins = get_plugins();

            self::$detectedAitPlugins = array();

            foreach($plugins as $pluginBasename => $data){
                if(strtolower($data['Author']) === 'aitthemes.club'){
                    $codename = dirname($pluginBasename);
                    self::$detectedAitPlugins[$codename] = array(
                        'codename' => $codename,
                        'plugin' => $pluginBasename,
                        'version' => $data['Version'],
                    );
                // revslider bundled with our themes is updated via our server
                }elseif($pluginBasename === 'revslider/revslider.php'){
                    $pluginFilePath = WP_PLUGIN_DIR . "/" . $pluginBasename;
                    $pluginDirPath = dirname($pluginFilePath);
                    // but only when it is our modified version of revslider
                    if(file_exists("{$pluginDirPath}/ait-revslider.php")) {
                        self::$detectedAitPlugins['revslider'] = array(
                            'codename' => 'revslider',
                            'plugin' => $pluginBasename,
                            'version' => $data['Version'],
                        );
                    }
                }
            }
        }

        return self::$detectedAitPlugins;
    }



    public static function getAitPluginsExceptPrepackedAndFree()
    {
        $p = self::getAitPlugins();
        foreach(self::getFreePlugins() as $plugin){
            unset($p[$plugin]);
        }

        foreach(self::getPrepackedPlugins() as $plugin){
            unset($p[$plugin]);
        }

        return $p;
    }



    public static function getAitPluginsExceptFree()
    {
        $p = self::getAitPlugins();
        foreach(self::getFreePlugins() as $plugin){
            unset($p[$plugin]);
        }

        return $p;
    }



    public static function isThereAnyAitPlugins()
    {
        return (bool) count(self::getAitPlugins());
    }



    public static function isThereAnyAitPluginsExceptPrepacked()
    {
        $p = self::getAitPlugins();
        foreach(self::getPrepackedPlugins() as $plugin){
            unset($p[$plugin]);
        }
        return (bool) count($p);
    }



    public static function isThereAnyAitPluginsExceptFree()
    {
        $p = self::getAitPlugins();
        foreach(self::getFreePlugins() as $plugin){
            unset($p[$plugin]);
        }
        return (bool) count($p);
    }



    public static function isThereAnyAitPluginsExceptPrepackedAndFree()
    {
        return (bool) count(self::getAitPluginsExceptPrepackedAndFree());
    }



    public static function isFreePlugin($plugin)
    {
        return in_array($plugin, self::getFreePlugins());
    }



    public static function isPrepackedPlugin($plugin)
    {
        return in_array($plugin, self::getPrepackedPlugins());
    }



    public static function getFreePlugins()
    {
        return array(
            'ait-updater',
            'ait-sysinfo',
            'ait-wp42-compatibility-fix',
            'ait-directory-migrations',
        );
    }



    public static function getPrepackedPlugins()
    {
        return array(
            'ait-toolkit',
            'ait-shortcodes',
            'revslider',
        );
    }



    protected static function isThemeDirnameCodename($stylesheet)
    {
        if(self::$themePhpFile['codename'] === 'fw1' or self::$themePhpFile['codename'] === 'skeleton') return true;
        return (self::$themePhpFile['codename'] === $stylesheet);
    }



    public static function getTypeOfTheme($codename)
    {
        $themes = self::searchForAitThemes();
        if(isset($themes['club'][$codename])) return 'club';
        if(isset($themes['themeforest'][$codename])) return 'themeforest';
        return null;
    }



    protected static function getCodename()
    {
        return self::$themePhpFile['codename'];
    }



    protected static function getPackage()
    {
        return self::$themePhpFile['package'];
    }



    protected static function isSkeleton($stylesheet)
    {
        return strncmp($stylesheet, 'skeleton', strlen('skeleton')) === 0; // starts with 'skeleton'
    }



    protected static function resetThemePhpData()
    {
        self::$themePhpFile = array('codename' => '', 'package' => '');
    }



    protected static function loadThemePhpFile($theme)
    {
        $files = array(
            $theme->template_dir . '/ait-theme/@theme.php',
            $theme->template_dir . '/AIT/@theme.php',
        );

        foreach($files as $themephp){
            if(file_exists($themephp)){

                $content = file_get_contents($themephp);

                preg_match("/define\('AIT_THEME_CODENAME',\s'(.+)'/i", $content, $result);
                if(isset($result[1])){
                    self::$themePhpFile['codename'] = $result[1];
                }

                preg_match("/define\('AIT_THEME_PACKAGE',\s'(.+)'/i", $content, $result);
                if(isset($result[1])){
                    self::$themePhpFile['package'] = $result[1];
                }

            }
        }
    }


}
