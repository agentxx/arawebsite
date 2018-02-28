<?php

class
AitWpAdminExtensions{static
function
register(){$ext=self::loadConfig();if(!AitUtils::isAjax()){if($ext->wpAdmin->adminFooterText){add_filter('admin_footer_text',array(__CLASS__,'adminFooterText'));}}}static
function
loadConfig(){$extensionsFilePath=aitPath('config','/wp-extensions.php');if($extensionsFilePath===false){$ext=require
aitPaths()->dir->fwConfig.'/wp-extensions.php';}else{$ext=include$extensionsFilePath;}return$ext;}static
function
adminFooterText($text){$t=aitOptions()->getOptionsByType('theme');if(isset($t['adminBranding']['adminFooterText'])){return'<span id="footer-thankyou">'.AitLangs::getCurrentLocaleText($t['adminBranding']['adminFooterText']).'</span>';}else{return$text;}}}class
AitAutomaticPluginInstallation{static
function
run(){$plugins=self::getPrepackedPluginsPaths();$installer=new
AitPluginBulkInstaller(new
Automatic_Upgrader_Skin());$installer->bulkInstall($plugins);}protected
static
function
getPrepackedPluginsPaths(){$packages=array();$plugins=AitTheme::getConfiguration('plugins');$paidPlugins=AitTheme::getConfiguration('paid-plugins');if(!empty($paidPlugins)and
AIT_THEME_PACKAGE!=='basic'){$plugins=array_merge($plugins,$paidPlugins);}foreach($plugins
as$slug=>$plugin){if(isset($plugin['source'])and
file_exists($plugin['source'])and
isset($plugin['ait-auto-install'])and$plugin['ait-auto-install']===true){$packages[]=$plugin['source'];}}return$packages;}}class
AitShortcodesGenerator
extends
NObject{protected
static$shortcodes=array();protected
static$fullConfig=array('shortcodes'=>array());protected
static$attrsDefaults=array('shortcodes'=>array());protected
static$types=array();protected
static$manager;static
function
register(){global$pagenow;if(!aitIsPluginActive('shortcodes'))return;if($pagenow!='post-new.php'and$pagenow!='post.php'and$pagenow!='media-upload.php'and$pagenow!='admin.php'and$pagenow!='admin-ajax.php'and$pagenow!='user-edit.php'and$pagenow!='profile.php')return;self::$manager=AitShortcodesManager::getInstance();add_action('admin_init',array(__CLASS__,'onAdminInit'));add_action('admin_enqueue_scripts',array(__CLASS__,'enqueueJs'));}static
function
onAdminInit(){self::$shortcodes=self::$manager->getShortcodes();$rawOptions=array('shortcodes'=>array());$attrs=array();foreach(self::$shortcodes
as$sc=>$o){$rawOptions['shortcodes'][$sc]=$o->getOptions();$rawOptions['shortcodes'][$sc]['text-domain']='ait-shortcodes';self::$types[$sc]=$o->getType();$attrs['shortcodes'][$sc]=$o->getAttrs();}$result=aitConfig()->processConfig($rawOptions,false,'shortcodes-full-config',array_values(self::$manager->getConfigFiles()));self::$fullConfig=$result['full-config']['shortcodes'];self::$attrsDefaults=array_replace_recursive($result['defaults']['shortcodes'],$attrs);if(get_user_option('rich_editing')=='true'){if(!empty(self::$shortcodes))add_filter('mce_external_plugins',array(__CLASS__,'addMceExternalPlugins'));add_filter('mce_buttons',array(__CLASS__,'addMceButtons'));add_filter('media_upload_tabs',array(__CLASS__,'mediaUploadTabs'));add_action("media_upload_ait-shortcodes",array(__CLASS__,'renderGeneratorIframe'));}}static
function
enqueueJs(){global$pagenow;$pages=array('edit.php','post-new.php','post.php','media-upload.php','nav-menus.php','profile.php','user-edit.php');if(AitAdmin::getCurrentPageSlug()or
in_array($pagenow,$pages)or
apply_filters('ait-enqueue-admin-assets',false)){wp_enqueue_script('ait.admin.shortcodes',aitPaths()->url->admin."/assets/js/ait.admin.shortcodes.js",array('ait.admin','media','media-views'),AIT_THEME_VERSION,true);$o=array('defaults'=>self::$attrsDefaults['shortcodes'],'types'=>self::$types);wp_localize_script('ait.admin.shortcodes','AitShortcodes',$o);}}static
function
mediaUploadTabs($tabs){$tabs['ait-shortcodes']=__('AIT Shortcodes','ait-admin');return$tabs;}static
function
renderGeneratorIframe(){wp_enqueue_media();wp_iframe(array(__CLASS__,'renderShortcodesForms'));}static
function
renderShortcodesForms(){?>
		<div id="ait-shortcodes-options">

			<div class="media-frame-menu">
				<div class="ait-shortcodes-tabs">
					<div class="media-menu">
						<?php

foreach(self::$shortcodes
as$sc){if($sc->isChild())continue;?>
							<a href="#ait-shortcode-<?php echo$sc->getName()?>-panel" id="ait-shortcode-<?php echo$sc->getName()?>-panel-tab" data-shortcode="<?php echo$sc->getName()?>" class="media-menu-item"><?php echo$sc->getTitle()?></a>
							<?php
}?>
					</div>
				</div>
			</div>
			<?php
?><div class="media-frame-content"><?php

AitOptionControl::$useGroupKeyInNameAttr=false;add_filter('ait-langs-enabled','__return_false');AitOptionsControlsRenderer::create(array('configType'=>'shortcodes','adminPageSlug'=>'shortcode','fullConfig'=>self::$fullConfig,'defaults'=>self::$attrsDefaults,'options'=>self::$attrsDefaults))->render();?>
			</div>

			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-secondary"></div>
					<div class="media-toolbar-primary">
						<a href="#" id="ait-insert-shortcode" class="button media-button button-primary button-large media-button-select" data-shortcode=""><?php _e('Insert shortcode','ait-admin')?></a>
					</div>
				</div>
			</div>

		</div>

		<?php
}static
function
addMceExternalPlugins($plugins){$version=get_bloginfo('version');if($version<3.9){$plugins['aitShortcodesButton']=aitPaths()->url->admin."/assets/js/tinymce-shortcodes-dropdown.js";}else{$plugins['aitShortcodesButton']=aitPaths()->url->admin."/assets/js/tinymce-shortcodes-dropdown-3.9.js";}return$plugins;}static
function
addMceButtons($buttons){$buttons[]='aitShortcodesButton';return$buttons;}}class
AitAdminAjax{protected
static$externalAjaxActions=array();/**
	 * Registers all ajax hooks
	 * The method which suppose to be wp_ajax_* callback must have @WpAjax annotation
	 */static
function
register(){$methods=get_class_methods(__CLASS__);$r=new
NClassReflection(__CLASS__);foreach($methods
as$method){if($r->getMethod($method)->getAnnotation('WpAjax')===true)add_action("wp_ajax_admin:{$method}",array(__CLASS__,$method));}foreach(self::$externalAjaxActions
as$action=>$callback){add_action("wp_ajax_admin:{$action}",$callback);}}static
function
addAjaxAction($action,$callback){self::$externalAjaxActions[$action]=$callback;}static
function
sendOk(){wp_send_json_success();}static
function
sendNotOk(){wp_send_json_error();}static
function
sendJson($data){wp_send_json_success($data);}static
function
sendErrorJson($data){wp_send_json_error($data);}/**
	 * Saves Theme Options
	 * @WpAjax
	 */static
function
saveThemeOptions(){$nonceKey=aitOptions()->getOptionKey('theme');AitUtils::checkAjaxNonce($nonceKey,true);self::saveOptions();}/**
	 * Saves Plugin Options
	 * @WpAjax
	 */static
function
savePluginOptions(){$codeName=aitOptions()->getRequestedPluginCodename();$nonceKey=aitOptions()->getOptionKey($codeName);AitUtils::checkAjaxNonce($nonceKey,true);self::saveOptions();}/**
	 * Saves Theme Settings and Page's Settings
	 * @WpAjax
	 */static
function
savePagesOptions(){$oid=aitOptions()->getRequestedOid();$nonceKey=implode(',',aitOptions()->getOptionsKeys(array('layout','elements'),$oid));AitUtils::checkAjaxNonce($nonceKey,true);self::saveOptions(!empty($oid),$oid);}private
static
function
saveOptions($noAutoload=false,$oid=''){$optionsKeys=explode(',',stripslashes(isset($_POST['options-keys'])?$_POST['options-keys']:''));$a=false;$noAutoload=$noAutoload||$a;if($optionsKeys){foreach($optionsKeys
as$optionKey){$value=array();if(isset($_POST[$optionKey])){$value=$_POST[$optionKey];}$value=stripslashes_deep($value);if($noAutoload){delete_option($optionKey);$r=add_option($optionKey,$value,'','no');$result=array('added'=>$r);}else{$r=update_option($optionKey,$value);$result=array('updated'=>$r);}}}if(isset($_POST['specific-post'])and
isset($_POST['specific-post']['id'])){$p=(object)$_POST['specific-post'];if(isset($p->template)){clean_post_cache($p->id);update_post_meta($p->id,'_wp_page_template',$p->template);}$post=array();$post['ID']=$p->id;if(isset($p->comments)){$post['comment_status']=$p->comments;}if(isset($p->title)){$post['post_title']=$p->title;}if(isset($p->content)){$post['post_content']=$p->content;}wp_update_post($post);}elseif($oid==''and
isset($_POST['specific-post']['comments'])){update_option('default_comment_status',$_POST['specific-post']['comments']);}if($oid==''){$register=aitOptions()->getLocalOptionsRegister();$tags=array_merge(array('global'),$register['special'],$register['pages']);}else{$tags=array($oid);}do_action('ait-save-options',$_POST,$optionsKeys,$oid);if(isset($result['added'])and!$result['added']){self::sendErrorJson($result);}else{AitCache::clean(array('tags'=>$tags,'less'=>true));aitManager('assets')->compileLessFiles();self::sendJson($result);}}/**
	 * Resets all options to default values
	 * Includes theme, layout and elements
	 * @WpAjax
	 */static
function
resetAllOptions(){AitUtils::checkAjaxNonce('reset-all-options');AitCache::clean();aitOptions()->resetAllOptions();self::sendOk();}/**
	 * Resets all theme options to default values
	 * Includes theme, layout and elements
	 * @WpAjax
	 */static
function
resetThemeOptions(){AitUtils::checkAjaxNonce('reset-theme-options');AitCache::clean();aitOptions()->resetThemeOptions();self::sendOk();}/**
	 * Reset Global Pages Options
	 * @WpAjax
	 */static
function
resetGlobalPagesOptions(){AitUtils::checkAjaxNonce('reset-pages-options');AitCache::clean();aitOptions()->resetDefaultLayoutOptions();self::sendOk();}/**
	 * Resets theme options in given section
	 * Includes theme, layout and elements
	 * @WpAjax
	 */static
function
resetOptionsGroup(){$configType=isset($_POST['configType'])?$_POST['configType']:'';$group=isset($_POST['group'])?$_POST['group']:'';$oid=aitOptions()->getRequestedOid();AitUtils::checkAjaxNonce("reset-{$configType}-{$group}-options");AitCache::clean();aitOptions()->resetOptionsGroup($configType,$group,$oid);self::sendOk();}/**
	 * Imports global options for specific element or layout
	 * @WpAjax
	 */static
function
importGlobalOptions(){$configType=isset($_POST['configType'])?$_POST['configType']:'';$group=isset($_POST['group'])?$_POST['group']:'';$oid=aitOptions()->getRequestedOid();AitUtils::checkAjaxNonce("import-{$configType}-{$group}-options");AitCache::clean();aitOptions()->importGlobalOptions($configType,$group,$oid);self::sendOk();}/**
	 * Deletes local options
	 * @WpAjax
	 */static
function
deleteLocalOptions(){check_ajax_referer('ait-delete-local-options');$oid=aitOptions()->getRequestedOid();if($oid){aitOptions()->deleteLocalOptions($oid);AitCache::clean(array('tags'=>array($oid),'less'=>true));}$localOptionsRegister=aitOptions()->getLocalOptionsRegister();if((isset($localOptionsRegister['special'])&&$first=reset($localOptionsRegister['special']))||(isset($localOptionsRegister['pages'])&&$first=reset($localOptionsRegister['pages']))){$url=AitUtils::adminPageUrl(array('page'=>'pages-options','oid'=>$first));}else{$url=AitUtils::adminPageUrl(array('page'=>'pages-options'));}self::sendJson(array('url'=>esc_url_raw($url)));}/**
	 * Uploads and imports AIT backup archive
	 * @WpAjax
	 */static
function
uploadAndImport(){$whatToImport=isset($_POST['what-to-import'])?$_POST['what-to-import']:false;$importAttachments=isset($_POST['import-attachments']);if(!$whatToImport){self::sendErrorJson(array('whatToImport'=>'','msg'=>__('Something is wrong with import form','ait-admin')));}$sendResults=array();$sendResults['whatToImport']=$whatToImport;$content=array();if(isset($_FILES['import-file'])and$_FILES['import-file']['error']==UPLOAD_ERR_OK){$gzFile=$_FILES['import-file']['tmp_name'];$content=file_get_contents($gzFile);}else{if($whatToImport=='demo-content'){$p=str_replace(aitPath('theme'),'',aitPath('includes'));$gzFile=aitPath('includes')."/demo-content.ait-backup";$importAttachments=true;if(file_exists($gzFile)){$content=file_get_contents($gzFile);}else{self::sendErrorJson(array('whatToImport'=>$whatToImport,'msg'=>sprintf(__("File with demo content '%s' doesn't exists.",'ait-admin'),$p."/demo-content.ait-backup")));}}else{$messages=array(false,__("The uploaded file exceeds the upload_max_filesize directive in php.ini.",'default'),__("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",'default'),__("The uploaded file was only partially uploaded.",'default'),__("No file was uploaded.",'default'),'',__("Missing a temporary folder.",'default'),__("Failed to write file to disk.",'default'),__("File upload stopped by extension.",'default'));self::sendErrorJson(array('whatToImport'=>$whatToImport,'msg'=>$messages[$_FILES['import-file']['error']]));}}$u=parse_url(get_option('siteurl'));$siteUrl=$u['host'];$siteUrl.=empty($u['path'])?'':str_replace(array('/','\\'),'-',$u['path']);$bck=aitPaths()->dir->uploads.'/backups/'.sprintf(".ht-backup-%s-%s-%s.ait-backup",$siteUrl,'all',date('Y-m-d-H.i.s'));try{AitBackup::exportToFile('all',$bck);}catch(Exception$e){}try{do_action('ait-before-import',$whatToImport);$sendResults=AitBackup::import($whatToImport,$content,$importAttachments);$sendResults['whatToImport']=$whatToImport;if(isset($sendResults['corrupted'])){self::sendErrorJson(array('whatToImport'=>$whatToImport,'msg'=>$sendResults['corrupted']));}else{AitCache::clean();do_action('ait-after-import',$whatToImport,$sendResults);wp_cache_delete('notoptions','options');wp_cache_delete('alloptions','options');global$wp_rewrite;$wp_rewrite->init();$wp_rewrite->flush_rules(true);self::sendJson($sendResults);}}catch(Exception$e){self::sendErrorJson(array('whatToImport'=>$whatToImport,'msg'=>$e->getMessage()));}}/**
	 * Generates ZIP file with exported options and content and downloads file
	 * It depends on jQuery File Download Plugin
	 * @WpAjax
	 */static
function
exportAndDownload(){$whatToExport=isset($_POST['what-to-export'])?$_POST['what-to-export']:false;if(!$whatToExport){self::sendErrorJson(__('Something is wrong with export form','ait-admin'));}try{$export=AitBackup::export($whatToExport);$u=parse_url(get_option('siteurl'));$siteUrl=$u['host'];$siteUrl.=empty($u['path'])?'':str_replace(array('/','\\'),'-',$u['path']);$exportFile=sprintf("%s-%s-%s.ait-backup",$siteUrl,$whatToExport,date('Y-m-d-H.i.s'));if($whatToExport=='demo-content')$exportFile="demo-content.ait-backup";header('Set-Cookie: fileDownload=true');header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');header('Cache-Control: no-cache');header('Pragma: hack');header('Content-Type: application/x-gzip');header('Content-Disposition: attachment; filename="'.$exportFile.'"');header('Content-Length: '.strlen($export));header('Connection: close');echo$export;exit;}catch(Exception$e){echo$e->getMessage();exit;}}/**
	 * Render TinyMCE editor instance
	 *
	 * @WpAjax
	 */static
function
tinyMceEditor(){$id=$_POST['id'];$content=stripslashes($_POST['content']);$name=stripslashes($_POST['textarea_name']);$editor=new
AitEditorOptionControl(new
AitOptionsControlsSection(new
AitOptionsControlsGroup()),'');$editor->setValue($content);$editor->ajaxHtml($id,$name);}/**
	 * Dismiss all wp pointers from step-by-step tour
	 *
	 * @WpAjax
	 */static
function
dismissPointers(){$pointers=$_POST['pointer'];$dismissed=array_filter(explode(',',(string)get_user_meta(get_current_user_id(),'dismissed_wp_pointers',true)));foreach($pointers
as$pointer){if($pointer!=sanitize_key($pointer))wp_die(0);if(in_array($pointer,$dismissed))continue;$dismissed[]=$pointer;}$dismissed=implode(',',$dismissed);update_user_meta(get_current_user_id(),'dismissed_wp_pointers',$dismissed);self::sendOk();}}abstract
class
AitAdminPage{protected$pageSlug;protected$storage;function
__construct($pageSlug){$this->pageSlug=$pageSlug;}function
beforeRender(){}function
render(){}function
renderPage(){$optionsLayoutClass=($this->pageSlug=='pages-options'or$this->pageSlug=='default-layout')?'':' ait-options-layout';?>
		<div class="wrap">
			<div id="ait-<?php echo$this->pageSlug?>-page" class="ait-admin-page ait-<?php echo$this->pageSlug?>-page<?php echo$optionsLayoutClass?>">
				<div class="ait-admin-page-wrap">
					<?php ?>
					<h2 style="display: none;"></h2>
					<?php $this->render();?>
				</div>
			</div>
		</div>
		<?php
}protected
function
formBegin($optionsKeys){global$post;$keys=implode(',',(array)$optionsKeys);$nonce=AitUtils::nonce($keys,true);?>
		<form action="#" method="post" id="ait-options-form" class="ait-options-form">
			<input type='hidden' name='options-keys' value="<?php echo$keys?>">
			<input type="hidden" name="_ajax_nonce" value="<?php echo$nonce?>">

			<?php if(isset($this->oid)and!empty($this->oid)):?><input type="hidden" name="oid" value="<?php echo$this->oid?>"><?php endif;?>

			<?php if(isset($post)):?>
			<input type="hidden" name="specific-post[id]" value="<?php echo$post->ID?>">
		<?php endif;?>
		<?php
}protected
function
formEnd(){?></form><?php
}}class
AitAdminBackupPage
extends
AitAdminPage{function
beforeRender(){add_action('admin_enqueue_scripts',create_function('','wp_enqueue_script("ait-jquery-filedownload");'));}protected
function
getGroups(){return
array('export'=>array('title'=>__('Export','ait-admin'),'callback'=>array($this,'exportControl')),'import'=>array('title'=>__('Import','ait-admin'),'callback'=>array($this,'importControl')),'import-demo-content'=>array('title'=>__('Import Demo Content','ait-admin'),'callback'=>array($this,'importDemoContentControl')));}function
render(){?>

		<div class="ait-options-page-header">
			<h3 class="ait-options-header-title"><?php _e('Import / Export','ait-admin')?></h3>

			<div class="ait-sticky-header">
				<h4 class="ait-sticky-header-title"><?php _e('Import / Export','ait-admin')?><i class="fa fa-circle"></i><span class="subtitle"></span></h4>
			</div>
		</div>

		<div class="ait-options-page">
			<div class="ait-options-page-content">
				<div class="ait-options-sidebar">
					<div class="ait-options-sidebar-content">

						<ul id="ait-<?php echo$this->pageSlug?>-tabs" class="ait-options-tabs">
							<?php
$this->renderTabs();?>
						</ul>
					</div>
				</div>

				<div class="ait-options-content">
					<div class="ait-options-controls-container">
						<div id="ait-<?php echo$this->pageSlug?>-panels" class="ait-options-controls ait-options-panels">

							<?php foreach($this->getGroups()as$groupKey=>$groupValues):?>
								<div id="<?php echo$this->getPanelId($groupKey);?>" class="ait-options-group ait-options-panel ait-<?php echo$this->pageSlug?>-tabs-panel">
									<div class="ait-controls-tabs-panel ait-options-basic">
										<div class="ait-options-section">
											<div class="ait-opt-container">
												<div class="ait-opt-wrap full-width">

													<?php call_user_func($groupValues['callback'],$groupKey)?>

												</div>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach;?>

						</div>
					</div>

				</div><!-- /.ait-options-content -->
			</div><!-- /.ait-options-layout-content -->
		</div><!-- /.ait-options-page -->
	<?php
}protected
function
getPanelId($groupKey){return
sanitize_key(sprintf("ait-%s-%s-panel",$this->pageSlug,$groupKey));}protected
function
renderTabs(){$tabs='';$t=$this->getGroups();foreach($t
as$k=>$v){$title=$v['title'];$panelId=$this->getPanelId($k);$tabs.="<li id='{$panelId}-tab'><a href='#{$panelId}'>{$title}</a></li>";}echo$tabs;}function
exportControl($groupKey){?>

		<form id="ait-<?php echo$this->pageSlug?>-<?php echo$groupKey?>-form" action="" method="post">
			<div class="ait-opt-container ait-opt-radio-main">
				<div class="ait-opt ait-opt-radio">
					<div class="ait-opt-wrapper">

						<?php if(apply_filters('ait-enable-old-backup-ui',false)or
apply_filters('ait-enable-old-export-ui',false)):?>

							<?php if(AitUtils::isAitServer()):?>
								<label><input type="radio" name="what-to-export" value="demo-content"> Demo Content</label>
							<?php endif;?>
							<div>
								<label><input type="radio" name="what-to-export" value="all" checked="checked"> <?php _ex('All','export','ait-admin')?>
								<div class="ait-opt-help">
									<div class="ait-help">
										<?php _ex('Exports all the WordPress Content, theme settings and WordPress settings','export','ait-admin')?>
									</div>
								</div>
								</label>
							</div>
							<div>
								<label><input type="radio" name="what-to-export" value="theme-options"> <?php _ex('All theme settings','export','ait-admin')?></label>
								<div class="ait-opt-help">
									<div class="ait-help">
										<?php _ex('Exports all the theme settings (Theme Options, Default Layout and Page Builder)','export','ait-admin')?>
									</div>
								</div>
							</div>
							<div>
								<label><input type="radio" name="what-to-export" value="wp-options"> <?php _ex('WordPress settings','export','ait-admin')?></label>
								<div class="ait-opt-help">
									<div class="ait-help">
										<?php _ex('Exports some WordPress settings (menu settings, sidebars, widgets)','export','ait-admin')?>
									</div>
								</div>
							</div>
							<div>
								<label><input type="radio" name="what-to-export" value="content"> <?php _ex('WordPress Content','export','ait-admin')?></label>
								<div class="ait-opt-help">
									<div class="ait-help">
										<?php _ex('Exports all the WordPress content. All your posts, pages, comments, custom fields, taxonomies, navigation menus and custom post types.','export','ait-admin')?>
									</div>
								</div>
							</div>
						<?php else:?>
							<?php if(AitUtils::isAitServer()):?><?php ?>
								<label><input type="radio" name="what-to-export" value="demo-content">Demo Content</label>
								<label><input type="radio" name="what-to-export" value="all" checked="checked">All - Full Content</label>
							<?php else:?>
								<input type="hidden" name="what-to-export" value="all">
								<div class="alert alert-info">
									<?php _ex('Exports all the WordPress content, theme settings and some WordPress settings','export','ait-admin')?>
								</div>
							<?php endif;?>
						<?php endif;?>
					</div>
				</div>
			</div>
		</form>
		<div class="ait-backup-action export">
			<a href="#" class="ait-backup-action-button ait-button positive uppercase"><?php _ex('Export','export action button label','ait-admin')?></a>
			<span class="action-indicator"></span>
		</div>
	<?php
}function
importControl($groupKey){?>
		<div class="alert alert-warning">
		<?php if(apply_filters('ait-enable-old-backup-ui',false)or
apply_filters('ait-enable-old-import-ui',false)):?>
			<?php _e('<strong>Warning!</strong> This import will delete all entries in database for selected option','ait-admin')?>
		<?php else:?>
			<?php _e('<strong>Warning!</strong> This import will delete all content, all theme settings and some WordPress settings in database.','ait-admin')?>
		<?php endif;?>
		</div>
		<form id="ait-<?php echo$this->pageSlug?>-<?php echo$groupKey?>-form" method="post">
		<?php if(apply_filters('ait-enable-old-backup-ui',false)or
apply_filters('ait-enable-old-import-ui',false)):?>
			<p><label><input type="radio" name="what-to-import" value="all" checked="checked"> <?php _ex('All','import','ait-admin')?></label></p>
			<p><label><input type="radio" name="what-to-import" value="theme-options"> <?php _ex('All theme settings','import','ait-admin')?></label></p>
			<p><label><input type="radio" name="what-to-import" value="wp-options"> <?php _ex('WordPress settings','import','ait-admin')?></label></p>
			<p><label><input type="radio" name="what-to-import" value="content"> <?php _ex('WordPress Content','import','ait-admin')?></label></p>
		<?php else:?>
			<div><input type="hidden" name="what-to-import" value="all"></div>
		<?php endif;?>

			<div class="ait-opt-container ait-opt-file-upload-main">
				<div class="ait-opt ait-opt-file-upload">
					<div class="ait-opt-wrapper">
						<label class="ait-opt-file-wrapper">
							<span class="ait-opt-file-input"><?php _ex('Choose your file','import','ait-admin')?></span>
							<input type="file" name="import-file" accept=".ait-backup">
							<span class="ait-opt-btn"><?php _ex('Browse','browse file from disk button label','ait-admin')?></span>
						</label>
					</div>
				</div>
			</div>

			<div class="ait-opt-container ait-opt-checkbox-main">
				<div class="ait-opt ait-opt-checkbox">
					<div class="ait-opt-wrapper">
						<label><input type="checkbox" name="import-attachments" value="1" checked="checked"> <?php _ex('Import Attachments?','import','ait-admin')?></label>
					</div>
				</div>
			</div>
		</form>
		<div class="ait-backup-action import">
			<a href="#" class="ait-backup-action-button ait-button positive uppercase"><?php _ex('Import','import action button label','ait-admin')?></a>
			<span class="action-indicator"></span>
			<div class="action-report"></div>
			<?php self::jsTemplates();?>
		</div>
	<?php
}function
importDemoContentControl($groupKey){?>
		<div class="alert alert-warning">
			<?php _e('<strong>Warning!</strong> Importing of demo content will delete all your current content in database and will replace all your images with demo images in the Media Library.','ait-admin')?>
		</div>
		<form id="ait-<?php echo$this->pageSlug?>-<?php echo$groupKey?>-form" method="post">
			<?php if(AitUtils::isAitServer()):?>
				<div class="ait-opt-container ait-opt-file-upload-main">
					<div class="ait-opt ait-opt-file-upload">
						<div class="ait-opt-wrapper">
							<label class="ait-opt-file-wrapper">
								<span class="ait-opt-file-input"><?php _ex('Choose your file','import','ait-admin')?></span>
								<input type="file" name="import-file" accept=".ait-backup">
								<span class="ait-opt-btn"><?php _ex('Browse','browse file from disk button label','ait-admin')?></span>
							</label>
						</div>
					</div>
				</div>
			<?php endif;?>
		</form>
		<div class="ait-backup-action import-demo-content">
			<a href="#" class="ait-backup-action-button ait-button positive uppercase"><?php _ex('Import Demo Content','import action button label','ait-admin')?></a>
			<span class="action-indicator"></span>
			<div class="action-report"></div>
			<?php self::jsTemplates();?>
		</div>
	<?php
}protected
static
function
jsTemplates(){?>
		<script type="text/html" class="action-report-tpl">
			<# if(typeof failed !== 'undefined') { #>
				<div class='action-report-error alert alert-danger'>
					{{ failed }}
				</div>
			<# } #>

			<# if(typeof imports !== 'undefined') { #>
				<# _.each(imports, function(msgs, status) { #>
					<# if(_.keys(msgs).length){ #>
						<#
							var c = 'warning';
							if(status == 'ok') c = 'success';
							if(status == 'error') c = 'danger';
						#>
						<div class='action-report-{{{ status }}} alert alert-{{{ c }}}'>
							<ul>
							<# _.each(msgs, function(msg) { #>
								<li>{{ msg }}</li>
							<# }); #>
							</ul>
						</div>
					<# } #>
				<# }); #>
			<# } #>

			<# if(typeof attachments !== 'undefined') { #>
				<# _.each(attachments, function(atts, type) { #>
					<# if(atts.length){ #>
						<#
							var c = 'success';
							if(type == 'failed') c = 'danger';
						#>
						<div class='action-report-attachments-{{{ type }}} alert alert-{{{ c }}}'>
							<ul>
							<# _.each(atts, function(attachment) { #>
								<li>{{ attachment }}</li>
							<# }); #>
							</ul>
						</div>
					<# } #>
				<# }); #>
			<# } #>
		</script>
	<?php
}}class
AitAdminPluginOptionsPage
extends
AitAdminPage{protected$pluginCodename='';protected$params=array();function
__construct($params){parent::__construct($params['slug']);$this->params=$params;$this->pluginCodename=$params['pluginCodename'];}function
render(){?>

		<div class="ait-options-page-header">
			<h3 class="ait-options-header-title"><?php echo
esc_html($this->params['menu-title'])?></h3>
			<div class="ait-options-header-tools">
				<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>
				<div class="ait-header-save">
					<button class="ait-save-plugin-options">
						<?php esc_html_e('Save Options','ait-admin')?>
					</button>

					<div id="action-indicator-save" class="action-indicator action-save"></div>
				</div>
			</div>

			<div class="ait-sticky-header">
				<h4 class="ait-sticky-header-title"><?php echo
esc_html($this->params['menu-title'])?><i class="fa fa-circle"></i><span class="subtitle"></span></h4>
			</div>
		</div>

		<div class="ait-options-page">

			<div class="ait-options-page-content">
				<div class="ait-options-sidebar">
					<div class="ait-options-sidebar-content">
						<ul id="ait-<?php echo$this->pageSlug?>-tabs" class="ait-options-tabs">
							<?php
$this->renderTabs();?>
						</ul>
					</div>
				</div>

				<div class="ait-options-content">

					<?php
$this->formBegin(aitOptions()->getOptionKey($this->pluginCodename));?>
					<input type="hidden" name="pluginCodename" value="<?php echo$this->pluginCodename?>">

					<div class="ait-options-controls-container">
						<div id="ait-<?php echo$this->pageSlug?>-panels" class="ait-options-controls ait-options-panels">

							<?php

AitOptionsControlsRenderer::create(array('configType'=>$this->pluginCodename,'adminPageSlug'=>$this->pageSlug,'fullConfig'=>aitConfig()->getFullConfig($this->pluginCodename),'defaults'=>aitConfig()->getDefaults($this->pluginCodename),'options'=>aitOptions()->getOptionsByType($this->pluginCodename),'isPlugin'=>true))->render();?>

						</div>
					</div>

					<?php $this->formEnd()?>

				</div><!-- /.ait-options-content -->
			</div><!-- /.ait-options-layout-content -->

		</div><!-- /.ait-options-page -->
		<?php
}protected
function
renderTabs(){$tabs='';$t=aitConfig()->getFullConfig($this->pluginCodename);foreach($t
as$groupKey=>$groupData){$panelId=sanitize_key(sprintf("ait-%s-%s-panel",$this->pageSlug,$groupKey));$title=(!empty($groupData['@title']))?$groupData['@title']:$groupKey;$_translate='__';$title=$_translate($title,'ait-admin');$tabs.="<li id='{$panelId}-tab'><a href='#{$panelId}'>$title</a></li>";}echo$tabs;}}class
AitAdminPagesOptionsPage
extends
AitAdminPage{protected$oid='';protected$importFrom=NULL;protected$post=NULL;protected$pageUrl;const
LAST_EDITED_OID='_ait_page_builder_last_edited_oid';function
beforeRender(){$o=aitOptions();$postOid=$o->getRequestedOid('post');$getOid=$o->getRequestedOid('get');if($postOid){if($o->pageForLocalOptionsIsAvailable($postOid)){if(!$o->hasCustomLocalOptions($postOid)){$o->addLocalOptions($postOid);}AitUtils::adminRedirect(array('page'=>$this->pageSlug,'oid'=>$postOid));}else{delete_option(self::LAST_EDITED_OID);AitUtils::adminRedirect(array('page'=>$this->pageSlug));}}elseif($getOid){if($o->pageForLocalOptionsIsAvailable($getOid)){if(!$o->hasCustomLocalOptions($getOid)and
isset($_GET['oidnonce'])and
AitUtils::checkNonce($_GET['oidnonce'],'oidnonce')){$o->addLocalOptions($getOid);AitUtils::adminRedirect(array('page'=>$this->pageSlug,'oid'=>$getOid));}elseif($o->hasCustomLocalOptions($getOid)){$this->oid=$getOid;}else{$first=$o->getFirstFoundLocalOptionsId();if($first){AitUtils::adminRedirect(array('page'=>$this->pageSlug,'oid'=>$first));}else{delete_option(self::LAST_EDITED_OID);AitUtils::adminRedirect(array('page'=>$this->pageSlug));}}}else{delete_option(self::LAST_EDITED_OID);AitUtils::adminRedirect(array('page'=>$this->pageSlug));}}elseif($oid=get_option(self::LAST_EDITED_OID)){if($o->pageForLocalOptionsIsAvailable($oid)){AitUtils::adminRedirect(array('page'=>$this->pageSlug,'oid'=>$oid));}else{if($first=$o->getFirstFoundLocalOptionsId()){AitUtils::adminRedirect(array('page'=>$this->pageSlug,'oid'=>$first));}else{delete_option(self::LAST_EDITED_OID);AitUtils::adminRedirect(array('page'=>$this->pageSlug));}}}if($this->oid){update_option(self::LAST_EDITED_OID,$this->oid);}elseif($first=$o->getFirstFoundLocalOptionsId()){AitUtils::adminRedirect(array('page'=>$this->pageSlug,'oid'=>$first));}if(isset($_GET['importFrom'])){$this->importFrom=$_GET['importFrom'];}$this->pageUrl=AitUtils::adminPageUrl(array('page'=>$this->pageSlug));$this->setupPost();add_action('admin_bar_menu',array(&$this,'addViewAndEditLinksToAdminBar'),110);add_thickbox();}function
render(){if($this->isIntroPage())return$this->renderIntroPage();$this->isRenderingDefaultLayout=(empty($this->oid)and$this->pageSlug==='default-layout');?>
		<div class="ait-options-mainmenu full-pagebuilder">
			<div class="ait-options-mainmenu-content">
				<?php

if($this->isRenderingDefaultLayout){?>
				        <h3 class="ait-options-header-title has-subtitle"><?php _e('Default Layout <small>Layout for all pages</small>','ait-admin')?></h3>
						<?php
}else{$this->renderPagesDropdown('page-options-selection',__('Edit different page&hellip;','ait-admin'),$this->oid);}$this->renderHeaderTools();?>
			</div>
		</div><!-- /.ait-options-mainmenu -->




		<div class="ait-options-page" data-unsaved-changes-message="<?php esc_html_e("Changes have been made.",'ait-admin');?>">
			<div class="hidden" id="hidden-wp-editor-wrapper">
				<?php wp_editor('','hidden-wp-editor');?>
			</div>
			<div class="ait-options-page-content">

				<?php
$elementsControlsRenderer=AitElementsControlsRenderer::create(array('configType'=>'elements','adminPageSlug'=>'pages-options','oid'=>$this->oid,'fullConfig'=>aitConfig()->getFullConfig('elements'),'defaults'=>aitConfig()->getDefaults('elements'),'options'=>aitOptions()->getOptionsByType('elements',isset($this->importFrom)?$this->importFrom:$this->oid)),'AitElementsControlsRenderer');?>


				<div class="ait-available-elements-container">
					<div id="stick-to-top">

						<div id="ait-available-elements-contents" class="hidden">
							<form action="" method="post" class="ait-available-elements-contents-form">
								<?php
$elementsControlsRenderer->renderAvailableElementsContents();?>
							</form>
						</div>

						<div class="ait-available-elements-tabs ait-simple-tabs">
							<h3 class="ait-simple-tab active" data-tab-id="ait-available-elements-droppable-to-columns"><?php _e('Columnable Elements','ait-admin')?></h3>
							<h3 class="ait-simple-tab" data-tab-id="ait-available-elements-not-droppable-to-columns"><?php _e('Fullwidth Elements','ait-admin')?></h3>
						</div>

						<div id="ait-available-elements">
							<?php
$elementsControlsRenderer->renderAvailableElementsHandlers();?>

							<a href="#" class="toggle-collapse" style="display: none;"></a>
						</div>
					</div>
				</div><!-- /.ait-available-elements-container -->

				<div class="ait-options-content">
					<!-- Elements -->

					<div id="ait-used-elements-contents" class="hidden">
						<form action="" method="post" class="ait-used-sortable-elements-contents-form">
						<?php $elementsControlsRenderer->renderUsedSortableElementsContents();?>
						</form>
					</div>

					<div id="ait-used-elements" class="ait-elements">

						<!-- Layout -->

						<div id="ait-layout-options" class="ait-element ait-used-element ait-layout-options" data-ait-element-content-id="ait-layout-options-content">

							<div class="ait-element-handler">
								<div class="ait-element-actions">
									<a class="ait-element-edit" href="#"><i class="fa fa-edit"></i></a>
								</div>
								<div class="ait-element-icon" style="background-color: #dadada;"><i class="fa fa-file-text"></i></div>
								<div class="ait-element-title">
									<h4><?php esc_html_e('Layout Options','ait-admin');?></h4>
								</div>
							</div>

							<div id="ait-layout-options-content" class="ait-element-content ait-layout-options-controls-container no-tabs" data-ait-element-id="ait-layout-options">
								<div class="ait-element-wrap">
									<h3><?php esc_html_e('Layout Options','ait-admin');?></h3>
									<div class="ait-element-controls ait-layout-options-controls">

										<?php $this->formBegin(aitOptions()->getOptionsKeys(array('layout','elements'),$this->oid));?>

											<?php

AitOptionsControlsRenderer::create(array('configType'=>'layout','adminPageSlug'=>'pages-options','oid'=>$this->oid,'fullConfig'=>aitConfig()->getFullConfig('layout'),'defaults'=>aitConfig()->getDefaults('layout'),'options'=>aitOptions()->getOptionsByType('layout',isset($this->importFrom)?$this->importFrom:$this->oid)))->render();?>

										<?php $this->formEnd()?>

									</div>

									<div class="ait-element-actions">
										<button class="ait-button ait-element-close" type="button">OK</button>
									</div>
								</div>
							</div>
						</div>

						<h2 class="ait-elements-group toggle-unsortables open"><?php esc_html_e('Sticked unsortable elements','ait-admin')?></h2>
						<p class="ait-elements-placeholder-note"><?php esc_html_e('You can only enable or disable these elements','ait-admin')?></p>
						<div id="ait-used-elements-unsortable" class="ait-used-elements ait-unsortable-elements open">
						<?php

AitElementsControlsRenderer::create(array('configType'=>'elements','adminPageSlug'=>'pages-options','oid'=>$this->oid,'fullConfig'=>aitConfig()->getFullConfig('elements'),'defaults'=>aitConfig()->getDefaults('elements'),'options'=>aitOptions()->getOptionsByType('elements',isset($this->importFrom)?$this->importFrom:$this->oid)),'AitElementsControlsRenderer')->renderUsedUnsortableElements();?>
						</div>

						<h2 class="ait-elements-group"><?php esc_html_e('Sortable elements','ait-admin')?></h2>
						<p class="ait-elements-placeholder-note"><?php esc_html_e('Drag&Drop here elements from the right hand side','ait-admin')?></p>
						<div id="ait-used-elements-sortable-wrapper">
							<form action="" method="post" class="ait-used-sortable-elements-form">
							<div id="ait-elements-with-sidebars-background"></div>

								<div id="ait-used-elements-sortable" class="ait-used-elements">
									<form action="" method="post" class="ait-used-sortable-elements-handlers-form">
										<?php
$elementsControlsRenderer->renderUsedSortableElementsHandlers();?>
									</form>

								</div>
							</form>
						</div>

					</div>


				</div><!-- /.ait-options-content -->
			</div><!-- /.ait-options-layout-content -->
		</div><!-- /.ait-options-page -->
		<?php
}protected
function
renderIntroPage(){?>
		<div class="ait-intro-container">
			<div class="ait-intro-content">
				<h2><?php esc_html_e('One step closer to your Page Builder','ait-admin')?></h2>

				<div class="ait-intro-panel">
					<div class="ait-options-mainmenu">
						<div class="ait-options-mainmenu-content">
							<?php $this->renderPagesDropdown('page-options-selection',__('Select page to edit','ait-admin'),$this->oid);?>
						</div>
					</div>

					<div class="ait-intro-separator"><span><?php esc_html_e('or','ait-admin')?></span></div>

					<div class="ait-intro-create">
						<a href="<?php echo
admin_url('post-new.php?post_type=page')?>" title="<?php esc_html_e('Create New Page','ait-admin')?>">
							<div class="ait-intro-create-icon">
								<i class="fa fa-plus-circle"></i>
								<span class="ait-tool-title"><?php esc_html_e('Create new page','ait-admin')?></span>
							</div>
						</a>
					</div>
				</div>

			</div>
		</div>
		<?php
}protected
function
renderHeaderTools(){?>
		<div class="ait-custom-header-tools">

			<div class="ait-pagetools-toggle"><i class="fa fa-gear"></i></div>
			<ul class="ait-pagetools">
				<?php if(!$this->isIntroPage()):?>
					<li class="ait-page-import ait-tooltip-container">
						<a href="#TB_inline?width=content&amp;height=content&amp;inlineId=ait-page-options-import-selection-popup" class="thickbox">
							<i class="fa fa-download"></i>
							<span class="ait-tool-title"><?php esc_html_e('Import options','ait-admin');?></span>
						</a>
						<div class="ait-tooltip"><?php esc_html_e('Import options','ait-admin');?></div>
						<div id="ait-page-options-import-selection-popup" style="display:none;">
							<div class="ait-controls-tabs-panel">
								<div class="ait-options-section ait-sec-title">
									<h2 class="ait-options-section-title"><?php esc_html_e('Import options','ait-admin');?></h2>
									<div id="ait-page-options-import-selection-content-wrapper" class="ait-opt-container">
										<div class="ait-opt-wrap">
											<div class="ait-opt-label">
												<div class="ait-label-wrapper">
													<span class="ait-label"><?php esc_html_e('Select page','ait-admin')?></span>
													<div class="ait-help"><?php esc_html_e('Imports options from this page','ait-admin')?></div>
												</div>
											</div>
											<div class="ait-opt">
												<?php $this->renderPagesDropdown('page-options-import-selection',esc_html__('Select a page from which to import options','ait-admin'),'',true);?>
											</div>
										<button data-url="<?php echo
esc_url($this->pageUrl)?>&amp;oid=<?php echo$this->oid?>" id="ait-import-page-options-button" class="button-primary">Import</button>
									</div>
								</div>
							</div>
						</div>
					</li>

					<?php if(aitOptions()->isNormalPageOptions($this->oid)):?>
						<li class="ait-page-edit ait-tooltip-container">
							<a target="_blank" href="<?php global$post;echo
get_edit_post_link($post->ID)?>">
								<i class="fa fa-edit"></i>
								<span class="ait-tool-title"><?php esc_html_e('Edit page','ait-admin')?></span>
							</a>
							<div class="ait-tooltip"><?php esc_html_e('Edit page','ait-admin')?></div>
						</li>
					<?php endif;?>

					<li id="action-delete-local-options" class="ait-page-delete ait-tooltip-container">
						<?php
$nonce=AitUtils::nonce('delete-local-options');printf('<a href="%s" data-ait-delete-local-options=\'%s\'><i class="fa fa-trash"></i><span class="ait-tool-title">%s</span></a><div class="ait-tooltip">%s</div>',esc_url(add_query_arg('oid',$this->oid)),json_encode(array('oid'=>$this->oid,'nonce'=>$nonce)),__('Delete options','ait-admin'),__('Delete options','ait-admin'));?>
					</li>
				<?php endif;?>


				<li class="ait-page-new ait-tooltip-container">
					<a href="<?php echo
admin_url('post-new.php?post_type=page')?>" title="<?php esc_html_e('Add New Page','ait-admin')?>">
						<i class="fa fa-plus"></i>
						<span class="ait-tool-title"><?php esc_html_e('New page','ait-admin')?></span>
					</a>
					<div class="ait-tooltip"><?php esc_html_e('New page','ait-admin')?></div>
				</li>

				<?php if(!$this->isIntroPage()&&aitOptions()->isNormalPageOptions($this->oid)):?>
					<li class="ait-page-view ait-tooltip-container">
						<a target="_blank" href="<?php global$post;echo
get_permalink($post->ID)?>">
							<i class="fa fa-eye"></i>
							<span class="ait-tool-title"><?php esc_html_e('View page','ait-admin')?></span>
						</a>
						<div class="ait-tooltip"><?php esc_html_e('View page','ait-admin')?></div>
					</li>
				<?php endif;?>
			</ul>

			<div class="ait-header-save">
				<button class="ait-save-pages-options">
					<?php esc_html_e('Save Options','ait-admin')?>
				</button>
				<div id="action-indicator-save" class="action-indicator action-save"></div>
			</div>

		</div>
	<?php
}protected
function
getPostTitle(){global$post;if(isset($post))return$post->post_title;return'';}protected
function
getTitle(){$title='';if(aitOptions()->isNormalPageOptions($this->oid)){$title=esc_html($this->getPostTitle());}else{$specialPages=aitOptions()->getSpecialCustomPages();$esc_html__='esc_html__';if(isset($specialPages[$this->oid])){$title=$esc_html__($specialPages[$this->oid]['label'],'ait-admin');if(isset($specialPages[$this->oid]['sub-label'])and!empty($specialPages[$this->oid]['sub-label'])){$title.=" <small>(".$esc_html__($specialPages[$this->oid]['sub-label'],'ait-admin').")</small>";}}}return$title;}protected
function
renderPagesDropdown($name,$placeholderText='',$selectedOid=null,$onlyListPagesWithCustomOptions=false){$pagesDropdownId='ait-'.$name;?>
		<form action="<?php echo
esc_url($this->pageUrl)?>" method="post" class="ait-page-options-selection-form">
				<?php
$localOptsRegister=aitOptions()->getLocalOptionsRegister();$fn=create_function('$oid',"return (int) str_replace('_page_', '', \$oid);");$pagesWithCustomLocalOptions=array_map($fn,$localOptsRegister['pages']);$specialPagesWithCustomLocalOptions=$localOptsRegister['special'];$blogPageIndex='';if(get_option('show_on_front')=='page'){$blogPageIndex=get_option('page_for_posts');if($blogPageIndex){if(($key=array_search($blogPageIndex,$pagesWithCustomLocalOptions))!==false){unset($pagesWithCustomLocalOptions[$key]);}}else{if(($key=array_search('_blog',$specialPagesWithCustomLocalOptions))!==false){unset($specialPagesWithCustomLocalOptions[$key]);}}}$customTitleClass='';if(aitOptions()->isNormalPageOptions($this->oid)){$customTitleClass='ait-custom-title';}?><div id="<?php echo$pagesDropdownId?>" class="<?php
echo$pagesDropdownId." ".$customTitleClass;?>">
						<div id="<?php echo$pagesDropdownId?>-select-placeholder">
							<div class="chosen-container chosen-container-single">
								<a class="chosen-single">
									<span>
										<?php esc_html_e('Loading...','ait-admin');?>
									</span>
								</a>
							</div>
						</div>
				<?php
$pages=get_posts(array('numberposts'=>-1,'post_type'=>'page'));$disabledPagesIds=array();$homePageIndex=get_option('page_on_front');foreach($pages
as$i=>$page){if($blogPageIndex&&$page->ID==$blogPageIndex){unset($pages[$i]);}if(isset($homePageIndex)&&$page->ID==(int)$homePageIndex){$page->post_title.=' ('.esc_html__('home','ait-admin').')';}if($page->post_status=='trash'){if(count(get_pages("child_of={$page->ID}"))>0){$disabledPagesIds[]=$page->ID;}else{unset($pages[$i]);}}}$args=(object)array('depth'=>0,'selected'=>$selectedOid,'name'=>'oid','id'=>'oid','oid_prefix'=>'_page_','pages_with_local_options'=>$pagesWithCustomLocalOptions,'disabled_pages_ids'=>$disabledPagesIds,'only_list_pages_with_local_options'=>$onlyListPagesWithCustomOptions);$specialPages=aitOptions()->getSpecialCustomPages();if($onlyListPagesWithCustomOptions){$specialPagesWithCustomOptions=array();foreach($specialPagesWithCustomLocalOptions
as$pageId){if(isset($specialPages[$pageId])){$specialPagesWithCustomOptions[$pageId]=$specialPages[$pageId];}}$specialPages=$specialPagesWithCustomOptions;}$walker=new
AitPagePostDropdownWalker;$output="<select class='hidden' name='{$args->name}' id='{$args->id}' data-placeholder='{$placeholderText}'>\n";$output.="<option value=''></option>\n";$label=esc_html__('Special pages','ait-admin');$output.="<optgroup label=\"{$label}\" data-page-type=\"special\">";foreach($specialPages
as$id=>$page){$label=$page['label'];if(isset($page['sub-label'])and!empty($page['sub-label'])){$label.=" ({$page['sub-label']})";}$selectedAttr=($id==$args->selected||($id=='_blog'&&"_page_".$blogPageIndex==$args->selected))?' selected':'';$output.="<option class=\"special-page".(in_array($id,$specialPagesWithCustomLocalOptions)?" has-local-options":"")."\" value=\"".esc_attr($id)."\"".$selectedAttr.">{$label}</option>\n";}$output.="</optgroup>";if(!empty($pages)){$label=esc_html__('Normal pages','ait-admin');$output.="<optgroup label=\"{$label}\" data-page-type=\"standard\">";$output.=$walker->walk($pages,$args->depth,(array)$args);$output.="</optgroup>";}$output.="</select>\n";echo$output;?>
				</div>
		</form>
		<?php
}protected
function
isIntroPage(){return
empty($this->oid);}protected
function
setupPost(){if(AitUtils::startsWith($this->oid,'_page_')or$this->oid=='_blog'){$_page=false;if($this->oid=='_blog'){$blog=get_option('page_for_posts');if($blog){$_page=get_post($blog);}}else{if(AitUtils::contains($this->oid,'_page_')){$id=substr($this->oid,strlen('_page_'));$_page=get_post($id);}}if($_page){global$post;$post=$_page;setup_postdata($post);}}}function
addViewAndEditLinksToAdminBar($wp_admin_bar){global$post;if(!$post)return;if($post->post_type=='page'){$wp_admin_bar->add_node(array('id'=>'view-page','title'=>esc_html__('View Page','ait-admin'),'href'=>get_permalink($post->ID)));$wp_admin_bar->add_node(array('id'=>'edit-page','title'=>esc_html__('Edit Page','ait-admin'),'href'=>get_edit_post_link($post->ID)));}}}class
AitAdminDefaultLayoutPage
extends
AitAdminPagesOptionsPage{function
beforeRender(){$this->pageUrl=AitUtils::adminPageUrl(array('page'=>$this->pageSlug));}protected
function
renderHeaderTitle(){?>
        <h3 class="ait-options-header-title"><?php _e('Default Layout <small>Default Layout Options Administration for all pages</small>','ait-admin')?></h3>
        <?php
}protected
function
renderHeaderTools(){?>
        <div class="ait-custom-header-tools">
			<div class="ait-pagetools-toggle"><i class="fa fa-gear"></i></div>
        	<ul class="ait-pagetools">
                <li class="ait-reset-button ait-tooltip-container">
					<a href="#" class="ait-reset-options" title="<?php _e('Reset to Defaults','ait-admin')?>"
                        <?php echo
aitDataAttr('reset-options',array('confirm'=>__('Are you sure you want to reset all settings to defaults?','ait-admin'),'nonce'=>AitUtils::nonce("reset-pages-options"),'what'=>'pages-options','oid'=>false))?>
                    >
                        <i class="fa fa-undo"></i>
						<span class="ait-tool-title"><?php esc_html_e('Import options','ait-admin');?></span>
					</a>
					<div class="ait-tooltip"><?php _e('Reset to Defaults','ait-admin')?></div>
					<div id="action-indicator-reset" class="action-indicator action-reset"></div>
				</li>

            </ul>

			<div class="ait-header-save">
				<button class="ait-save-pages-options">
					<?php esc_html_e('Save Options','ait-admin')?>
				</button>
				<div id="action-indicator-save" class="action-indicator action-save"></div>
			</div>
        </div>
    <?php
}protected
function
renderTitle(){_e('<strong>Default Layout</strong> Options','ait-admin');}protected
function
isIntroPage(){return
false;}}class
AitAdminThemeOptionsPage
extends
AitAdminPage{function
render(){?>

		<div class="ait-options-page-header">
			<h3 class="ait-options-header-title"><?php esc_html_e('Theme Options','ait-admin')?></h3>
			<div class="ait-options-header-tools">
				<a class="ait-scroll-to-top"><i class="fa fa-chevron-up"></i></a>
				<div class="ait-header-save">
					<button class="ait-save-<?php echo$this->pageSlug?>">
						<?php esc_html_e('Save Options','ait-admin')?>
					</button>

					<div id="action-indicator-save" class="action-indicator action-save"></div>
				</div>
			</div>

			<div class="ait-sticky-header">
				<h4 class="ait-sticky-header-title"><?php esc_html_e('Theme Options','ait-admin')?><i class="fa fa-circle"></i><span class="subtitle"></span></h4>
			</div>
		</div>

		<div class="ait-options-page">


			<div class="ait-options-page-content">
				<div class="ait-options-sidebar">
					<div class="ait-options-sidebar-content">
						<ul id="ait-<?php echo$this->pageSlug?>-tabs" class="ait-options-tabs">
							<?php
$this->renderTabs();?>
						</ul>
					</div>
				</div>

				<div class="ait-options-content">

					<?php
$this->formBegin(aitOptions()->getOptionKey('theme'));?>

					<div class="ait-options-controls-container">
						<div id="ait-<?php echo$this->pageSlug?>-panels" class="ait-options-controls ait-options-panels">

							<?php

AitOptionsControlsRenderer::create(array('configType'=>'theme','adminPageSlug'=>$this->pageSlug,'fullConfig'=>aitConfig()->getFullConfig('theme'),'defaults'=>aitConfig()->getDefaults('theme'),'options'=>aitOptions()->getOptionsByType('theme')))->render();?>

						</div>
					</div>

					<?php $this->formEnd()?>

				</div><!-- /.ait-options-content -->
			</div><!-- /.ait-options-layout-content -->

		</div><!-- /.ait-options-page -->
		<?php
}protected
function
renderTabs(){$tabs='';$t=aitConfig()->getFullConfig('theme');foreach($t
as$groupKey=>$groupData){$panelId=sanitize_key(sprintf("ait-%s-%s-panel",$this->pageSlug,$groupKey));$title=(!empty($groupData['@title']))?$groupData['@title']:$groupKey;$_translate='__';$title=$_translate($title,'ait-admin');$tabs.="<li id='{$panelId}-tab'><a href='#{$panelId}'>$title</a></li>";}echo$tabs;}}class
AitOptionsControlsRenderer{protected$configType;protected$adminPageSlug;protected$options;protected$defaults;protected$optionsControlsGroups=array();protected$oid;protected$isRenderingDefaultLayout;protected$isRenderingPluginOptions;protected
static$renderer;function
__construct($params){$defaults=array('configType'=>'','adminPageSlug'=>'','oid'=>'','fullConfig'=>array(),'defaults'=>array(),'options'=>array());$params=(object)array_merge($defaults,$params);$this->configType=$params->configType;$this->adminPageSlug=$params->adminPageSlug;$this->oid=$params->oid;$this->fullConfig=$params->fullConfig;$this->defaults=$params->defaults;$this->options=$params->options;$this->isRenderingDefaultLayout=empty($this->oid);$this->isRenderingPluginOptions=!empty($params->isPlugin);}static
function
create($params,$class=''){if(!$class)$class=__CLASS__;self::$renderer=new$class($params);return
self::$renderer;}function
render(){$groupFactory=AitTheme::getFactory('options-controls-group');$groups=array();foreach($this->fullConfig
as$groupId=>$groupDefinition){$groups[]=$groupFactory->createOptionsControlsGroup($this->configType,$groupId,$groupDefinition,$this->options[$groupId],$this->defaults[$groupId]);}foreach($groups
as$group){$panelId=sanitize_key(sprintf("ait-%s-%s-panel",$this->adminPageSlug,$group->getId()));?>

			<div id="<?php echo$panelId?>" class="ait-options-group ait-options-panel ait-<?php echo$this->adminPageSlug?>-tabs-panel">
				<?php
$this->renderOptionsControlsGroup($group);?>
			</div>
		<?php
}}protected
function
renderOptionsControlsGroup(AitOptionsControlsGroup$group){$basicControls=$advancedControls=$tabs='';$j=0;$c=0;foreach($group->getSections()as$i=>$section){$basic=$advanced=array();foreach($section->getOptionsControls()as$optionControl){if(!apply_filters('ait-allow-render-option-control',true,$optionControl,$this->oid)){continue;}if(!$optionControl->isBasic()or$section->areAllAdvanced()){$advanced[$i][$c]=$optionControl->getHtml();}else{$basic[$i][$c]=$optionControl->getHtml();}$c++;}$c=0;if(empty($basic[$i])and!empty($advanced[$i])and!$section->areAllAdvanced()){if($section->isCapabilityEnabled()){if(current_user_can($section->getCapabilityName())){$basicControls.=$this->renderSectionBegin($section);$basicControls.=implode("\n",$advanced[$i]);$basicControls.=$this->renderSectionEnd($section);}}else{$basicControls.=$this->renderSectionBegin($section);$basicControls.=implode("\n",$advanced[$i]);$basicControls.=$this->renderSectionEnd($section);}}elseif(!empty($basic[$i])and
empty($advanced[$i])){if($section->isCapabilityEnabled()){if(current_user_can($section->getCapabilityName())){$basicControls.=$this->renderSectionBegin($section);$basicControls.=implode("\n",$basic[$i]);$basicControls.=$this->renderSectionEnd($section);}}else{$basicControls.=$this->renderSectionBegin($section);$basicControls.=implode("\n",$basic[$i]);$basicControls.=$this->renderSectionEnd($section);}}else{if(empty($basic[$i])and
empty($advanced[$i]))continue;if(!$section->areAllAdvanced()){$basicControls.=$this->renderSectionBegin($section);$basicControls.=implode("\n",$basic[$i]);$basicControls.=$this->renderSectionEnd($section);}if($j==0){$tabs=$this->renderBasicAdvancedTabs($group);if((!$this->isRenderingDefaultLayout
and$this->adminPageSlug=='pages-options')){$enabler=new
AitAdvancedOptionsEnablerOptionControl($section,$group->getId());$enabler->setValue($group->areAdvancedEnabled());$advancedControls.=$enabler->getHtml();}$j++;}$advancedControls.=$this->renderSectionBegin($section,false);$advancedControls.=implode("\n",$advanced[$i]);$advancedControls.=$this->renderSectionEnd($section,false);}}$output='';if($basicControls==''and$advancedControls==''){$output.=$this->noControls(__('Here are no options.','ait-admin'));$section=new
AitOptionsControlsSection($group);$no=new
AitHiddenOptionControl($section,$group->getId());$output.=$no->getHtml();}else{if(apply_filters("ait-allow-render-controls-{$this->configType}-{$group->getId()}",true,$this->oid)){$output.=$this->renderUtilsBar($group,$tabs);$output.=$this->renderBasicControls($group,$basicControls);$output.=$this->renderAdvancedControls($group,$advancedControls);}else{$output.='<div style="display:none">';$output.=$this->renderBasicControls($group,$basicControls);$output.=$this->renderAdvancedControls($group,$advancedControls);$output.='</div>';$output.=$this->noControls(apply_filters('ait-dont-allow-render-controls-message',__('Controls are not allowed to render','ait-admin'),$this->configType,$group->getId(),$this->oid));}}echo$output;}protected
function
renderBasicControls(AitOptionsControlsGroup$group,$basic){$count=$this->countBasicControlsIn($group);ob_start();?>
		<div id="<?php echo$this->getBAHtmlId($group,'basic')?>" class="ait-controls-tabs-panel ait-options-basic ait-options-basic-count-<?php echo$count;?>">
			<?php echo$basic;?>
		</div>
		<?php

return
ob_get_clean();}protected
function
renderAdvancedControls(AitOptionsControlsGroup$group,$advanced){if(empty($advanced))return'';ob_start();?>
		<div id="<?php echo$this->getBAHtmlId($group,'advanced')?>" class="ait-controls-tabs-panel ait-options-advanced <?php if(!$group->areAdvancedEnabled()and!$this->isRenderingDefaultLayout):echo'advanced-options-disabled';endif;?>">
			<?php echo$advanced?>
		</div>
		<?php

return
ob_get_clean();}function
renderSectionBegin(AitOptionsControlsSection$section,$basic=true){ob_start();$b=$basic?"-basic":'-advanced';$sId=$section->getId()?" id='{$section->getId()}{$b}'":'';$class=$section->getId()?" section-{$section->getId()}":'';$class.=$section->getTitle()?' ait-sec-title':'';$hidden=$section->isHidden()?' style="display:none;" ':'';$_translate='_e';?>
		<div class="ait-options-section <?php echo$class?>" <?php echo$sId,$hidden?>>
		<?php if($section->getTitle()){?>
			<h2 class="ait-options-section-title"><?php $_translate($section->getTitle(),'ait-admin')?></h2>
		<?php }if($section->getHelp()){?>
			<div class="ait-options-section-help"><?php $_translate($section->getHelp(),'ait-admin')?></div>
		<?php }return
ob_get_clean();}function
renderSectionEnd($section,$basic=true){return"\n</div>\n";}function
renderBasicAdvancedTabs(AitOptionsControlsGroup$optionsControlsGroup){ob_start();?>
		<ul class="ait-controls-tabs">
			<li id="<?php echo$this->getBAHtmlId($optionsControlsGroup,'basic')?>-tab"><a href="#<?php echo$this->getBAHtmlId($optionsControlsGroup,'basic')?>"><?php _e('Basic','ait-admin')?></a></li>
			<li id="<?php echo$this->getBAHtmlId($optionsControlsGroup,'advanced')?>-tab"><a href="#<?php echo$this->getBAHtmlId($optionsControlsGroup,'advanced')?>"><?php _e('Advanced','ait-admin')?></a></li>
		</ul>
		<?php

return
ob_get_clean();}protected
function
countBasicControlsIn($group){$basicControlsInThisGroup=0;foreach($group->getSections()as$i=>$section){$_basic=array();foreach($section->getOptionsControls()as$optionControl){$isAdvanced=(!$optionControl->isBasic()or$section->areAllAdvanced());if(!$isAdvanced){$basicControlsInThisGroup++;}}}return$basicControlsInThisGroup-3;}protected
function
getBAHtmlId(AitOptionsControlsGroup$optionsControlsGroup,$type){return
sanitize_key("ait-options-{$type}-{$optionsControlsGroup->getId()}").(!is_null($optionsControlsGroup->getIndex())?"-__{$optionsControlsGroup->getIndex()}__":'');}function
renderUtilsBar(AitOptionsControlsGroup$optionsControlsGroup,$tabs=''){$import='';$reset='';$tpl='<li><a href="#" class="%s" %s>%s%s</a></li>';if(!$this->isRenderingDefaultLayout
and$this->configType=='elements'):$import=sprintf($tpl,'ait-import-global-options',aitDataAttr('import-global-options',array('confirm'=>__('Are you sure you want to import options from Global Options to this element?','ait-admin'),'configType'=>$this->configType,'nonce'=>AitUtils::nonce("import-{$this->configType}-{$optionsControlsGroup->getId()}-options"),'what'=>'group','group'=>$optionsControlsGroup->getId(),'oid'=>$this->oid)),'<span class="action-indicator action-import-global-options"></span>',__('Import','default'));endif;if(($optionsControlsGroup->getReset()and
AitConfig::isMainConfigType($this->configType)and!$this->isRenderingPluginOptions)or$this->configType=='elements'):$confirm=$this->configType=='elements'?__('Are you sure you want to reset options from this element to default values?','ait-admin'):__('Are you sure you want to reset options from this group to default values?','ait-admin');$group=$this->configType=='layout'?'':$optionsControlsGroup->getId();$reset=sprintf($tpl,"ait-reset-group-options",aitDataAttr('reset-options',array('confirm'=>$confirm,'configType'=>$this->configType,'nonce'=>AitUtils::nonce("reset-{$this->configType}-{$group}-options"),'what'=>'group','group'=>$group,'oid'=>$this->oid)),'<span class="action-indicator action-reset-group"></span>',__('Reset','ait-admin'));endif;$r='';if($tabs
or$import
or$reset):ob_start();?>
			<div class="ait-controls-utils-bar <?php if($tabs==''):?>no-tabs<?php endif;?>">
				<?php echo$tabs?>

				<ul class="ait-element-utils">
					<?php echo$import?>
					<?php echo$reset?>
				</ul>

			</div>
			<?php
$r=ob_get_clean();endif;return$r;}protected
function
noControls($message=''){if(!$message){$message=__('No options','ait-admin');}$o='<div class="ait-no-controls">';$o.="<em>{$message}</em>";$o.='</div>';return$o;}}class
AitPagePostDropdownWalker
extends
Walker_PageDropdown{function
start_el(&$output,$post,$depth=0,$args=array(),$id=0){$pad=str_repeat('&nbsp;',$depth*3);$oidPrefix=(isset($args['oid_prefix'])and$args['oid_prefix'])?$args['oid_prefix']:'';if($oidPrefix){$valueAttribute=$oidPrefix.$post->ID;}else{$valueAttribute=$post->ID;}$cssClasses="normal-page level-$depth";if(in_array($post->ID,$args['pages_with_local_options'])){$cssClasses.=" has-local-options";}else
if($args['only_list_pages_with_local_options']){return;}$selectedAttribute=($valueAttribute==$args['selected']?"selected":"");$disabledAttribute=in_array($post->ID,$args['disabled_pages_ids'])?' disabled':'';$output.=sprintf("\t<option class='%s' value='%s'%s%s>",$cssClasses,$valueAttribute,$selectedAttribute,$disabledAttribute);$output.=$pad.' '.esc_html($post->post_title);$output.="</option>\n";}}class
AitCategoryDropdownWalker
extends
Walker_CategoryDropdown{function
start_el(&$output,$category,$depth=0,$args=array(),$id=0){$pad=str_repeat('&nbsp;',$depth*3);$useSlug=(isset($args['use_slug'])and$args['use_slug'])?true:false;$value=$useSlug?$category->slug:$category->term_id;$cat_name=apply_filters('list_cats',$category->name,$category);$output.="\t<option class=\"level-$depth\" value=\"".$value."\"";if((isset($args['@multi_selected'])and
is_array($args['@multi_selected'])and
in_array($value,$args['@multi_selected']))or((is_string($args['selected'])or
is_numeric($args['selected']))and$args['selected']==$value)){$output.=' selected="selected"';}$output.='>';$output.=$pad.$cat_name;if($args['show_count'])$output.='&nbsp;&nbsp;('.$category->count.')';$output.="</option>\n";}}class
AitBackup{const
DUMMY_IMG_URL='https://demo.ait-themes.club/demo-images/';protected
static$wpContentTables=array('posts','postmeta','terms','termmeta','term_taxonomy','term_relationships','comments','commentmeta');protected
static$isExportingDemoContent=false;protected
static$isImportingDemoContent=false;protected
static$currentOperation='';static
function
export($whatToExport){self::$currentOperation='export';@set_time_limit(0);if($whatToExport=='demo-content'){$whatToExport='all';self::$isExportingDemoContent=true;self::addOnAfterDumpAttachmentsCallback();}$method=AitUtils::id2class($whatToExport,'','dump');if(!method_exists(__CLASS__,$method)){throw
new
Exception(sprintf(__("Export method '%s' does not exist. Something is wrong.",'ait-admin'),$method));}$content=array();$content[$whatToExport]=self::$method();$content[$whatToExport]=self::processUrls($content[$whatToExport]);$data=@gzcompress(base64_encode(serialize($content)),9);if(!$data){throw
new
Exception(sprintf(__("Export could not be compressed via gzcompress function. Check your PHP settings.",'ait-admin'),$method));}return$data;}protected
static
function
addOnAfterDumpAttachmentsCallback(){add_action('ait-after-dump-attachments',function($attachments,$originalImages,$isExportingDemoContent){if($isExportingDemoContent
and
file_exists(__DIR__.'/AitAfterDumpAttachments.php')){require
__DIR__.'/AitAfterDumpAttachments.php';$zipName=$attachments[0]['basename'];AitAfterDumpAttachments::run($zipName,$originalImages);}},10,3);}static
function
exportToFile($whatToExport,$file){$dir=dirname($file);$d=AitUtils::mkdir($dir);if($d){$data=self::export($whatToExport);$result=@file_put_contents($file,$data);return$result;}else{throw
new
Exception(sprintf(__("Directory '%s' can not be created.",'ait-admin'),$dir));}}protected
static
function
dumpAll(){$d1=self::dumpContent();$d2=self::dumpThemeOptions();$d3=self::dumpWpOptions();$options=array_merge_recursive($d2,$d3);return
array_merge($d1,$options);}protected
static
function
dumpWpOptions(){global$wpdb;$dump=array();$options=array('theme_mods_'.AIT_CURRENT_THEME,'sidebars_widgets','show_on_front','page_on_front','page_for_posts','widget_%','blogname','blogdescription','polylang','uploads_use_yearmonth_folders','permalink_structure');$options=apply_filters('ait-backup-wpoptions',$options,self::$isExportingDemoContent);$where=array();foreach($options
as$opt){if(AitUtils::contains($opt,'%')){$operator='LIKE';}else{$operator='=';}$where[]=$wpdb->prepare("`option_name` $operator %s",$opt);}$where=implode(' OR ',$where);$sql="SELECT `option_name`, `option_value`, `autoload` FROM `{$wpdb->options}` WHERE $where;";$dump['options']=$wpdb->get_results($sql,ARRAY_A);if($dump['options']===false
and$wpdb->last_error){throw
new
Exception($wpdb->last_error);}foreach($dump['options']as$i=>$row){if($row['option_name']=='theme_mods_'.AIT_CURRENT_THEME){$dump['options'][$i]['option_name']=str_replace('theme_mods_'.AIT_CURRENT_THEME,'theme_mods_%theme%',$row['option_name']);break;}}return$dump;}protected
static
function
dumpThemeOptions(){global$wpdb;$dump=array();$theme=esc_sql(AIT_CURRENT_THEME);$where=" `option_name` LIKE '\_ait\_{$theme}\_%\_opts%'";$sql="SELECT `option_name`, `option_value`, `autoload` FROM `{$wpdb->options}` WHERE $where;";$dump['options']=$wpdb->get_results($sql,ARRAY_A);if($dump['options']===false
and$wpdb->last_error){throw
new
Exception($wpdb->last_error);}foreach($dump['options']as$i=>$row){$dump['options'][$i]['option_name']=str_replace("_{$theme}_",'_%theme%_',$row['option_name']);}return$dump;}protected
static
function
dumpContent(){global$wpdb;$dump=array();foreach(self::$wpContentTables
as$table){$where='';if($table=='posts'and
self::$isExportingDemoContent){$where=" WHERE (post_status != 'auto-draft' and post_status !=  'trash') and post_type != 'revision'";}elseif($table=='comments'and
self::$isExportingDemoContent){$where=" WHERE comment_approved = '1'";}$result=$wpdb->get_results("SELECT * FROM {$wpdb->$table}{$where}",ARRAY_A);$dump[$table]=$result?$result:array();}$customTables=apply_filters('ait-backup-content-custom-tables',array(),self::$isExportingDemoContent);if(!empty($customTables)and
is_array($customTables)){foreach($customTables
as$table){if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'")==$wpdb->prefix.$table){$escTable=esc_sql($table);$reuslt=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$escTable}",ARRAY_A);$dump[$table]=$reuslt?$reuslt:array();}}}self::dumpAttachments($dump);return$dump;}protected
static
function
dumpAttachments(&$dump){$uploadsDir=realpath(aitPaths()->dir->uploads);$uploadsUrl=aitPaths()->url->uploads;$mimes=array_keys(get_allowed_mime_types());$exts=array();foreach($mimes
as$mime){$exts=array_merge($exts,explode('|',$mime));}array_walk($exts,array(__CLASS__,'buildExt'));$files=array();foreach($dump['posts']as$p){if($p['post_type']==='attachment'){$xFile=get_attached_file($p['ID'],true);if(file_exists($xFile)){$files[]=new
SplFileInfo(realpath($xFile));}}}$dump['@attachments']=array();$originalImages=array();foreach($files
as$file){$ext=$file->getExtension();if(self::$isExportingDemoContent
and!in_array($ext,array('png','jpg','jpeg','gif'))){continue;}$basename=self::getRelativePathname($file->getPathname());$url=self::$isExportingDemoContent?'':"$uploadsUrl/$basename";$dump['@attachments'][]=array('url'=>$url,'basename'=>$basename);$originalImages[]=$file->getPathname();$base=substr($file->getBasename(),0,-(strlen($ext)+1));$pattern="$base-[0-9]*x[0-9]*.$ext";$found=NFinder::findFiles($pattern)->in($uploadsDir);foreach($found
as$sizedFile){$basename=self::getRelativePathname($sizedFile->getPathname());$url=self::$isExportingDemoContent?'':"$uploadsUrl/$basename";$dump['@attachments'][]=array('url'=>$url,'basename'=>$basename);$originalImages[]=$sizedFile->getPathname();}}if(file_exists("$uploadsDir/revslider")){$revsliderFiles=NFinder::findFiles($exts)->from("$uploadsDir/revslider")->exclude('templates');foreach($revsliderFiles
as$revFile){if(self::$isExportingDemoContent
and!in_array($revFile->getExtension(),array('png','jpg','jpeg','gif'))){continue;}$basename=self::getRelativePathname($revFile->getPathname());$url=self::$isExportingDemoContent?'':"$uploadsUrl/$basename";$dump['@attachments'][]=array('url'=>$url,'basename'=>$basename);$originalImages[]=$revFile->getPathname();}}if(self::$isExportingDemoContent){$basename=AIT_THEME_CODENAME.'-'.date('YmdHis').'.zip';$dump['@attachments']=array(array('url'=>self::DUMMY_IMG_URL.AIT_THEME_CODENAME.'/'.$basename,'basename'=>$basename));}do_action('ait-after-dump-attachments',$dump['@attachments'],$originalImages,self::$isExportingDemoContent);}protected
static
function
getRelativePathname($filePath){$uploadsDirname=basename(realpath(aitPaths()->dir->uploads));return
str_replace('\\','/',substr($filePath,strpos($filePath,$uploadsDirname)+strlen($uploadsDirname)+1));}static
function
filterNonMediaFiles($file){$filePath=self::getRelativePathname(str_replace("\\","/",$file->getRealPath()));if(AitUtils::contains($filePath,'/')){preg_match('/^[0-9]{4}\/[0-9]{2}\//',$filePath,$matches);return(count($matches)>0
or
AitUtils::startsWith($filePath,'revslider'));}else{return
true;}}static
function
buildExt(&$item,$key){$item="*.{$item}";}static
function
import($whatToImport,$content,$importAttachments=true){self::$currentOperation='import';@set_time_limit(0);if($whatToImport=='demo-content'){$whatToImport='all';self::$isImportingDemoContent=true;}$method=AitUtils::id2class($whatToImport,'','load');if(!method_exists(__CLASS__,$method)){throw
new
Exception(sprintf(__("Import method '%s' does not exist. Something is wrong.",'ait-admin'),$method));}$decompressed=@gzuncompress($content);$result=array();if($decompressed){$raw=unserialize(base64_decode($decompressed));if(isset($raw[$whatToImport])){$dump=$raw[$whatToImport];$dump=self::processUrls($dump);}else{$result['corrupted']=__('Content of the backup file is corrupted, can not be uncompressed.','ait-admin');return$result;}$attachments=$dump['@attachments'];unset($dump['@attachments']);if($whatToImport=='all'){$result['imports']=self::loadAll($dump);}else{$result['imports']=self::tryLoad($whatToImport,$dump);}if(self::$isImportingDemoContent){$result['attachments']=self::fetchDemoImages($attachments);}elseif($importAttachments){$result['attachments']=self::fetchAttachments($attachments);}}else{$result['corrupted']=__('Content of the backup file is corrupted, can not be uncompressed.','ait-admin');}return$result;}static
function
importFromFile($whatToImport,$file,$importAttachments=true){$content=@file_get_contents($file);if($content===false)throw
new
Exception(sprintf(__('Content from the file "%s" can not be read.','ait-admin'),$file));return
self::import($whatToImport,$content,$importAttachments);}protected
static
function
loadAll($dump){$r1=self::tryLoad('content',$dump);$r2=self::tryLoad('theme-options',$dump);$r3=self::tryLoad('wp-options',$dump);return
array_merge_recursive($r1,$r2,$r3);}protected
static
function
loadWpOptions($dump){global$wpdb;$errors=array();$optionsCounter=0;$options=array('theme_mods_'.AIT_CURRENT_THEME,'sidebars_widgets','show_on_front','page_on_front','page_for_posts','widget_%','blogname','blogdescription','polylang','uploads_use_yearmonth_folders','permalink_structure');$options=apply_filters('ait-backup-wpoptions',$options,self::$isImportingDemoContent);$sql=array();foreach($options
as$option){if(strpos($option,'%')!==FALSE){$operator='LIKE';}else{$operator='=';}$sql[]=$wpdb->prepare("`option_name` $operator %s",$option);}$sql=implode(' OR ',$sql);$sql="DELETE FROM {$wpdb->options} WHERE $sql;";$result=$wpdb->query($sql);if($result===false
and$wpdb->last_error){$errors[]=$wpdb->last_error;}$check=$wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE option_id = 1",ARRAY_A);foreach($dump['options']as$id=>$option){if(!isset($check['blog_id']))unset($option['blog_id']);if($option['option_name']=='theme_mods_%theme%')$option['option_name']='theme_mods_'.AIT_CURRENT_THEME;if(in_array($option['option_name'],$options)or
AitUtils::startsWith($option['option_name'],'widget_')or
AitUtils::startsWith($option['option_name'],'reservations_')or
AitUtils::contains($option['option_name'],'_category_')){$optionsCounter++;$result=$wpdb->insert($wpdb->options,$option);if($result===false)$errors[]=sprintf(__('Inserting of the WordPress option "%s" failed.','ait-admin'),$option['option_name']);}}if(!empty($errors)){$code=(count($errors)!=$optionsCounter)?206:0;$msg=$code==0?__('All inserts of theme settings to the database failed.','ait-admin'):implode("\n\n",$errors);throw
new
Exception($msg,$code);}else{return
true;}}protected
static
function
loadThemeOptions($dump){global$wpdb;$errors=array();$optionsCounter=0;$theme=esc_sql(AIT_CURRENT_THEME);$where=" `option_name` LIKE '\_ait\_{$theme}\_%\_opts%'";$sql="DELETE FROM {$wpdb->options} WHERE $where;";$result=$wpdb->query($sql);if($result===false
and$wpdb->last_error){$errors[]=$wpdb->last_error;}foreach($dump['options']as$id=>$option){$key=&$option['option_name'];if(AitUtils::startsWith($key,"_ait_%theme%_")){$optionsCounter++;$key=str_replace('_%theme%_',"_{$theme}_",$key);$result=$wpdb->insert($wpdb->options,$option);if($result===false)$errors[]=sprintf(__('Inserting of the theme option "%s" failed.','ait-admin'),$option['option_name']);}}if(!empty($errors)){$code=(count($errors)!=$optionsCounter)?206:0;$msg=$code==0?__('All inserts of theme settings to the database failed.','ait-admin'):implode("\n\n",$errors);throw
new
Exception($msg,$code);}else{return
true;}}protected
static
function
loadContent($dump){global$wpdb;$errors=array();$insertsCounter=0;$batchInsertSql='';foreach(self::$wpContentTables
as$table){$batchInsertSql='';$insertsCounter=0;$wpdb->query("TRUNCATE TABLE {$wpdb->$table}");if(!empty($dump[$table])){$fields=implode('`, `',array_keys($dump[$table][0]));$rowsCount=count($dump[$table]);foreach($dump[$table]as$i=>$row){$insert=self::createInsertSQL($row).', ';$insertsCounter++;$batchInsertSql.=$insert;$hasNext=(($insertsCounter+1)<=$rowsCount);$canDoInsert=(!$hasNext||(($insertsCounter
%
30)===0));if($canDoInsert){$batchInsertSql=trim($batchInsertSql,', ');$result=$wpdb->query("INSERT INTO `{$wpdb->$table}` (`$fields`) VALUES {$batchInsertSql}");if($result===false
and$wpdb->last_error){$errors[$table][]=$wpdb->last_error;}$batchInsertSql='';}}}}$customTables=apply_filters('ait-backup-content-custom-tables',array(),self::$isImportingDemoContent);do_action('ait-create-content-custom-tables',self::$isImportingDemoContent);$insertsCounter=0;$batchInsertSql='';if(!empty($customTables)and
is_array($customTables)){foreach($customTables
as$table){if(!isset($dump[$table]))continue;$escTable=esc_sql($table);$batchInsertSql='';$insertsCounter=0;if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$escTable}'")==$wpdb->prefix.$table){$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$escTable}");if(!empty($dump[$table])){$fields=implode('`, `',array_keys($dump[$table][0]));$rowsCount=count($dump[$table]);foreach($dump[$table]as$i=>$row){$insert=self::createInsertSQL($row).', ';$insertsCounter++;$batchInsertSql.=$insert;$hasNext=(($insertsCounter+1)<=$rowsCount);$canDoInsert=(!$hasNext||(($insertsCounter
%
30)===0));if($canDoInsert){$batchInsertSql=trim($batchInsertSql,', ');$result=$wpdb->query("INSERT INTO `{$wpdb->prefix}{$escTable}` (`$fields`) VALUES {$batchInsertSql}");if($result===false
and$wpdb->last_error){$errors[$table][]=$wpdb->last_error;}$batchInsertSql='';}}}}}}if(!empty($errors)){$errorMsgs='';foreach($errors
as$table=>$errs){$c=count($errs);if($c!=0){$errorMsgs.="\nTable [{$table}]:\n".implode("\n\t",$errs);}}$code=$errorMsgs!=''?206:0;throw
new
Exception(sprintf(__('Content was partialy imported. Some errors occured. %s','ait-admin'),$errorMsgs),$code);}else{return
true;}}protected
static$checkFor=array();static
function
processUrls($data){self::$checkFor=array('%child-theme-url%','%parent-theme-url%','%uploads-url%','%site-url%',get_template_directory_uri(),get_stylesheet_directory_uri(),aitPaths()->url->uploads,site_url(),trim(json_encode(get_template_directory_uri()),'"'),trim(json_encode(get_stylesheet_directory_uri()),'"'),trim(json_encode(aitPaths()->url->uploads),'"'),trim(json_encode(site_url()),'"'));$attachments=isset($data['@attachments'])?$data['@attachments']:array();unset($data['@attachments']);array_walk_recursive($data,array(__CLASS__,'convertUrls'));$data['@attachments']=$attachments;return$data;}protected
static
function
containsPlaceholderOrUrl(&$haystack){foreach(self::$checkFor
as$needle){if(mb_strpos($haystack,$needle)!==false){return
true;}}return
false;}protected
static
function
convertUrls(&$value,$key){if(empty($value)or!is_string($value)){return;}$value=maybe_unserialize($value);if(self::$currentOperation=='export'and!is_array($value)){$value=apply_filters('ait-replace-value-in-export',$value,$key,self::$isExportingDemoContent);}if(is_string($value)and!self::containsPlaceholderOrUrl($value)){return;}global$_aitSiteUrl,$_aitParentThemeUrl,$_aitChildThemeUrl;$_aitUploadsUrl=aitPaths()->url->uploads;if(!isset($_aitSiteUrl)){$_aitSiteUrl=site_url();}if(!isset($_aitParentThemeUrl)){$_aitParentThemeUrl=get_template_directory_uri();$_aitChildThemeUrl=get_stylesheet_directory_uri();}if(is_string($value)){if(self::$currentOperation=='import'){$jsonresult=json_decode($value);$isJson=false;if(version_compare(PHP_VERSION,'5.3.0','>=')and
function_exists('json_last_error')and
json_last_error()===JSON_ERROR_NONE){$isJson=true;}elseif(version_compare(PHP_VERSION,'5.3.0','<=')and$jsonresult!==null){$isJson=true;}if($isJson){$value=str_replace('%child-theme-url%',trim(json_encode($_aitChildThemeUrl),'"'),$value);$value=str_replace('%parent-theme-url%',trim(json_encode($_aitParentThemeUrl),'"'),$value);$value=str_replace('%uploads-url%',trim(json_encode($_aitUploadsUrl),'"'),$value);$value=str_replace('%site-url%',trim(json_encode($_aitSiteUrl),'"'),$value);}else{$value=str_replace('%child-theme-url%',$_aitChildThemeUrl,$value);$value=str_replace('%parent-theme-url%',$_aitParentThemeUrl,$value);$value=str_replace('%uploads-url%',$_aitUploadsUrl,$value);$value=str_replace('%site-url%',$_aitSiteUrl,$value);}}elseif(self::$currentOperation=='export'){$value=str_replace($_aitChildThemeUrl,'%child-theme-url%',$value);$value=str_replace($_aitParentThemeUrl,'%parent-theme-url%',$value);$value=str_replace($_aitUploadsUrl,'%uploads-url%',$value);$value=str_replace($_aitSiteUrl,'%site-url%',$value);$value=str_replace(trim(json_encode($_aitChildThemeUrl),'"'),'%child-theme-url%',$value);$value=str_replace(trim(json_encode($_aitParentThemeUrl),'"'),'%parent-theme-url%',$value);$value=str_replace(trim(json_encode($_aitUploadsUrl),'"'),'%uploads-url%',$value);$value=str_replace(trim(json_encode($_aitSiteUrl),'"'),'%site-url%',$value);}}elseif(is_array($value)){array_walk_recursive($value,array(__CLASS__,'convertUrls'));$value=serialize($value);}}protected
static
function
createInsertSQL($data){global$wpdb;$fields=array_keys($data);$formattedFields=array();foreach($fields
as$field){if(isset($wpdb->field_types[$field])){$form=$wpdb->field_types[$field];}else{$form='%s';}$formattedFields[]=$form;}$cols=implode(', ',$formattedFields);return$wpdb->prepare("($cols)",$data);}protected
static
function
fetchDemoImages($attachments){$failed=array();$ok=array();$uploadsDir=realpath(aitPaths()->dir->uploads);$zipToDownload=(isset($attachments[0])and
AitUtils::endsWith($attachments[0]['url'],'.zip'))?$attachments[0]['url']:'';$zipFile=download_url($zipToDownload);if(is_wp_error($zipFile)){$failed[]=sprintf(__('Demo images can not be fetched. Reason: %s','ait-admin'),implode('. ',$zipFile->get_error_messages()));}else{$cb=function($method){return'direct';};add_filter('filesystem_method',$cb);WP_Filesystem();$result=unzip_file($zipFile,$uploadsDir);if(is_wp_error($result)){$failed[]=sprintf(__('Can not extract demo images from zip file. Reason: %s','ait-admin'),implode('. ',$result->get_error_messages()));}else{$ok[]=__('Dummy demo images were sucessfully downloaded','ait-admin');}remove_filter('filesystem_method',$cb);}return
compact('ok','failed');}protected
static
function
fetchAttachments($attachments){$failed=array();$ok=array();$uploadsDir=realpath(aitPaths()->dir->uploads);foreach($attachments
as$i=>$data){$file="{$uploadsDir}/{$data['basename']}";if(file_exists($file))continue;$upload=self::fetchRemoteFile($data['url'],$file);if(is_wp_error($upload)){if(!self::$isImportingDemoContent){$failed[]=sprintf(__('File from URL "%s" can not be fetched. Reason: %s','ait-admin'),$data['url'],implode('. ',$upload->get_error_messages()));}}else{if(!self::$isImportingDemoContent){$ok[]=sprintf(__('Successfully downloaded and saved file from URL "%s"','ait-admin'),$data['url']);}}}if(self::$isImportingDemoContent){$ok=array(__('Dummy demo images were sucessfully downloaded','ait-admin'));}else{if(empty($failed)){$ok=array(__('All attachments were sucessfully downloaded','ait-admin'));}else{$ok=array();}}return
array('ok'=>$ok,'failed'=>$failed);}protected
static
function
generateAllSizesForGuidImages($file){if(file_is_displayable_image($file)){global$_wp_additional_image_sizes;$sizes=array();foreach(get_intermediate_image_sizes()as$s){$sizes[$s]=array('width'=>'','height'=>'','crop'=>false);if(isset($_wp_additional_image_sizes[$s]['width']))$sizes[$s]['width']=intval($_wp_additional_image_sizes[$s]['width']);else$sizes[$s]['width']=get_option("{$s}_size_w");if(isset($_wp_additional_image_sizes[$s]['height']))$sizes[$s]['height']=intval($_wp_additional_image_sizes[$s]['height']);else$sizes[$s]['height']=get_option("{$s}_size_h");if(isset($_wp_additional_image_sizes[$s]['crop']))$sizes[$s]['crop']=$_wp_additional_image_sizes[$s]['crop'];else$sizes[$s]['crop']=get_option("{$s}_crop");}$sizes=apply_filters('intermediate_image_sizes_advanced',$sizes);if($sizes){$editor=wp_get_image_editor($file);if(!is_wp_error($editor))$editor->multi_resize($sizes);}}}static
function
fetchRemoteFile($url,$file){if(AitUtils::contains($url,'http://ait-themes.com/dummyimg/')){$url=str_replace('http://ait-themes.com/dummyimg/',self::DUMMY_IMG_URL,$url);}$headers=self::httpGet($url,$file);if(!$headers){@unlink($file);return
new
WP_Error('fetch_remore_file_error',__('Remote server did not respond','ait-admin'));}if($headers['response']!='200'){@unlink($file);return
new
WP_Error('fetch_remore_file_error',sprintf(__('Remote server returned error response %1$d %2$s','ait-admin'),esc_html($headers['response']),get_status_header_desc($headers['response'])));}$filesize=filesize($file);if(isset($headers['content-length'])and$filesize!=$headers['content-length']){@unlink($file);return
new
WP_Error('fetch_remore_file_error',__('Remote file have incorrect size','ait-admin'));}if($filesize==0){@unlink($file);return
new
WP_Error('fetch_remore_file_error',__('Zero size file downloaded','ait-admin'));}return$file;}protected
static
function
tryLoad($whatToImport,$dump){$return=array('ok'=>array(),'warning'=>array(),'error'=>array());$msgs=array('content'=>array('ok'=>__('Content was successfully imported, yay!','ait-admin'),'warning'=>__('Content was imported partialy. Some data could not be imported. Here is the report: %s','ait-admin'),'error'=>__('Content could not be imported. Reason(s): %s','ait-admin')),'theme-options'=>array('ok'=>__('All theme settings were successfully imported, yay!','ait-admin'),'warning'=>__('All theme settings were imported partialy. Some data could not be imported. Here is the report: %s','ait-admin'),'error'=>__('All theme settings could not be imported. Reason(s): %s','ait-admin')),'wp-options'=>array('ok'=>__('WordPress settings (sidebars, widgets, etc..) were successfully imported, yay!','ait-admin'),'warning'=>__('WordPress settings were imported partialy. Some data could not be imported. Here is the report: %s','ait-admin'),'error'=>__('WordPress settings (sidebars, widgets, etc..) could not be imported. Reason(s): %s','ait-admin')));$method=AitUtils::id2class($whatToImport,'','load');if(!method_exists(__CLASS__,$method)){throw
new
Exception(sprintf(__("Import method '%s' does not exist. Something is wrong.",'ait-admin'),$method));}try{self::$method($dump);$return['ok'][$whatToImport]=$msgs[$whatToImport]['ok'];}catch(Exception$e){if($e->getCode()==206){$return['warning'][$whatToImport]=sprintf($msgs[$whatToImport]['warning'],"<pre>".$e->getMessage()."</pre>");}else{$return['error'][$whatToImport]=sprintf($msgs[$whatToImport]['error'],"<pre>".$e->getMessage()."</pre>");}}return$return;}static
function
httpGet($url,$filePath){@set_time_limit(60);$options=array();$options['redirection']=3;$options['method']='GET';$options['timeout']=30;$response=wp_remote_request($url,$options);if(is_wp_error($response)){return
false;}$headers=wp_remote_retrieve_headers($response);$headers['response']=wp_remote_retrieve_response_code($response);wp_mkdir_p(dirname($filePath));if(PHP_OS==='WINNT'){$filePath=addslashes($filePath);}$fp=fopen($filePath,'w');if(!$fp)return$headers;fwrite($fp,wp_remote_retrieve_body($response));fclose($fp);clearstatcache();return$headers;}}class
AitAdmin{protected
static$pagesSlugs=array();protected
static$topLevelAdminPageSlug='';static
function
run(){AitWpAdminExtensions::register();add_action('admin_init',array(__CLASS__,'onAdminInit'));AitShortcodesGenerator::register();if(!AitUtils::isAjax()){add_action('admin_enqueue_scripts',array(__CLASS__,'enqueueAdminCssAndJs'),24);add_action('admin_menu',array(__CLASS__,'renderAdminMenu'),1);add_filter('custom_menu_order','__return_true');add_filter('menu_order',array(__CLASS__,'changeMenuOrder'),0);add_action('load-themes.php',array(__CLASS__,'activateTheme'));add_action('switch_theme',array(__CLASS__,'deactivateTheme'));add_action('pll_after_add_language',array(__CLASS__,'onAfterAddLanguage'));add_action('admin_print_styles',array(__CLASS__,'highlightAitMenuItems'));add_action('media_buttons',array(__CLASS__,'addPageBuilderButton'),100,1);add_filter('redirect_post_location',array(__CLASS__,'redirectToPageBuilder'),10,2);add_action('admin_notices',array(__CLASS__,'membershipNotice'));self::modifyPageRowActions();}}static
function
membershipNotice(){if(AIT_THEME_PACKAGE!=='basic')return;$assetsImgUrl=aitPaths()->url->admin.'/assets/img/';$ctaUrl="https://www.ait-themes.club/directory-membership/?utm_source=wp-admin&utm_medium=wp-admin-banner&utm_campaign=Free-Theme";?>
		<div id="ait-membership-notice" class="ait-notice ait-basic-package-notice" onclick="jQuery('#ait-membership-notice').toggleClass('active')">
			<div class="text"><strong class="big"><?php _e('Download premium extensions','ait-admin')?></strong></div>
			<button type="button" class="ait-notice-button white uppercase has-arrow"><?php _e('Learn More','ait-admin')?></button>
		</div>

		<div class="ait-notice-accordion">
			<div class="ait-notice-accordion-cols">
				<a href="<?php echo$ctaUrl?>" target="_blank" class="ait-notice-accordion-item green">
					<div class="img"><img src="<?php echo$assetsImgUrl?>ait-banner-extensions.png"></div>
					<h3><i class="dashicons dashicons-schedule"></i><span><?php _e('Tons of clever extensions','ait-admin')?></span></h3>
				</a>
				<a href="<?php echo$ctaUrl?>" target="_blank" class="ait-notice-accordion-item blue">
					<div class="img"><img src="<?php echo$assetsImgUrl?>ait-banner-updates.png"></div>
					<h3><i class="dashicons dashicons-upload"></i><span><?php _e('Continual security updates & new features','ait-admin')?></span></h3>
				</a>
				<a href="<?php echo$ctaUrl?>" target="_blank" class="ait-notice-accordion-item orange">
					<div class="img"><img src="<?php echo$assetsImgUrl?>ait-banner-support.png"></div>
					<h3><i class="dashicons dashicons-sos"></i><span><?php _e('World class support service','ait-admin')?></span></h3>
				</a>
			</div>
			<div class="ait-notice-accordion-footer">
				<div class="ait-notice-button-group">
					<span class="ait-notice-button-hint"><?php _e('Remove this ad','ait-admin')?></span>
					<a href="<?php echo$ctaUrl?>" target="_blank" class="ait-notice-button positive"><?php printf(__('Upgrade to Directory Membership %s','ait-admin'),'<span class="dashicons dashicons-cart"></span>')?></a>
				</div>
			</div>
		</div>
		<?php
}static
function
highlightAitMenuItems(){$usedColorName=get_user_option('admin_color');$colors=array('fresh'=>'#090909','blue'=>'#3290B1','coffee'=>'#3C3632','ectoplasm'=>'#352946','light'=>'#bbb','midnight'=>'#16181A','ocean'=>'#526469','sunrise'=>'#b43c38');if(isset($colors[$usedColorName])){$color=$colors[$usedColorName];$adminPages=aitConfig()->getAdminConfig('pages');$css='';foreach($adminPages
as$page){$css.="li#toplevel_page_ait-{$page['slug']} > a { background: {$color}; }";}echo"<style>$css</style>";}}static
function
onAdminInit(){if(AitUtils::isAjax()){AitAdminAjax::register();}}static
function
onAfterAddLanguage(){AitCache::clean();}static
function
renderAdminMenu(){$t=aitOptions()->getOptionsByType('theme');$iconUrl=isset($t['adminBranding']['adminMenuIcon'])?$t['adminBranding']['adminMenuIcon']:aitPaths()->url->admin.'/assets/img/ait-admin-menu-icon16.png';$adminMenuTitle=isset($t['adminBranding']['adminTitle'])?AitLangs::getCurrentLocaleText($t['adminBranding']['adminTitle'],esc_html__('Theme Admin','ait-admin')):esc_html__('Theme Admin','ait-admin');$aitAdminItemsPosition=40.01;$adminPages=aitConfig()->getAdminConfig('pages');global$menu;$menu[]=array('','read','ait-separator1','','wp-menu-separator ait-separator');$menu[]=array('','read','ait-separator2','','wp-menu-separator ait-separator');foreach($adminPages
as$page){$class=AitUtils::id2class($page['slug'],'Page','AitAdmin');$pageObject=new$class($page['slug']);$pageHook=add_menu_page(($page['slug']=='theme-options')?$adminMenuTitle:$page['menu-title'],($page['slug']=='theme-options')?$adminMenuTitle:$page['menu-title'],apply_filters('ait-admin-pages-permission','manage_options',$page),"ait-{$page['slug']}",array($pageObject,"renderPage"),$iconUrl,(string)$aitAdminItemsPosition+=0.01);if(isset($page['sub'])and!empty($page['sub'])){if($page['slug']=='theme-options'){$pageHook=add_submenu_page("ait-{$page['slug']}",$page['menu-title'],$page['menu-title'],apply_filters('ait-admin-pages-permission','manage_options',$page),"ait-{$page['slug']}",array($pageObject,"renderPage"));}foreach($page['sub']as$subpage){$class=AitUtils::id2class($subpage['slug'],'Page','AitAdmin');if(isset($subpage['type'])&&$subpage['type']=='plugin'){$pageObject=new
AitAdminPluginOptionsPage($subpage);}else{$pageObject=new$class($subpage['slug']);}$pageHook=add_submenu_page("ait-{$page['slug']}",$subpage['menu-title'],$subpage['menu-title'],apply_filters('ait-admin-pages-permission','manage_options',$subpage),"ait-{$subpage['slug']}",array($pageObject,"renderPage"));add_action('load-'.$pageHook,array($pageObject,"beforeRender"));}}add_action('load-'.$pageHook,array($pageObject,"beforeRender"));}}static
function
changeMenuOrder($menuOrder){$newOrder=$cpts=array();$adminPages=aitConfig()->getAdminConfig('pages');$slugs=wp_list_pluck($adminPages,'slug');$firstSlug=array_shift($slugs);$lastSlug=array_pop($slugs);foreach($menuOrder
as$i=>$item){if(AitUtils::contains($item,$firstSlug)){$newOrder[]='ait-separator1';$newOrder[]=$item;}elseif(AitUtils::contains($item,$lastSlug)){$newOrder[]=$item;$newOrder[]='ait-separator2';}elseif(AitUtils::startsWith($item,'edit.php?post_type=ait-')){$cpts["x$i"]=$item;}elseif($item!='ait-separator1'and$item!='ait-separator2'){$newOrder[]=$item;}}$lastAitSepIndex=array_search('ait-separator2',$newOrder);if(!empty($cpts)){NArrays::insertAfter($newOrder,$lastAitSepIndex,$cpts);$newOrder=array_values($newOrder);}else{unset($newOrder[$lastAitSepIndex]);}return$newOrder;}static
function
getCurrentPageSlug(){$id=get_current_screen()->id;$adminPages=aitConfig()->getAdminConfig('pages');$return='';foreach($adminPages
as$page){if(AitUtils::endsWith($id,$page['slug'])){$return=$page['slug'];break;}if(isset($page['sub'])){foreach($page['sub']as$subpage){if(AitUtils::endsWith($id,$subpage['slug'])){$return=$subpage['slug'];break;}}}}return$return;}static
function
activateTheme(){global$pagenow;if($pagenow=='themes.php'and(isset($_GET['activated'])or
isset($_GET['ait-theme-continue']))){AitCache::clean();$new=aitConfig()->extractDefaultsFromConfig(aitConfig()->getRawConfig(),true);foreach(AitConfig::getMainConfigTypes()as$configType){$key=aitOptions()->getOptionKey($configType);$wasAdded=add_option($key,$new[$configType]);}if(@is_writable(WP_PLUGIN_DIR)){AitAutomaticPluginInstallation::run();}do_action('ait-theme-activation');flush_rewrite_rules();if($wasAdded){$redirectTo=add_query_arg(array('page'=>'ait-backup#ait-backup-import-demo-content-panel'),admin_url("admin.php"));}else{$redirectTo=admin_url('themes.php');}aitManager('assets')->compileLessFiles();wp_redirect(esc_url_raw($redirectTo));}}static
function
deactivateTheme(){flush_rewrite_rules();}static
function
enqueueAdminCssAndJs(){global$pagenow;$assetsUrl=aitPaths()->url->admin.'/assets';wp_enqueue_style('ait-wp-admin-style',"{$assetsUrl}/css/wp-admin.css",array('media-views'),AIT_THEME_VERSION);self::pageBuilderTutorial();$pages=array('edit.php','post-new.php','post.php','media-upload.php','nav-menus.php','profile.php','user-edit.php');if(self::getCurrentPageSlug()or
in_array($pagenow,$pages)or
apply_filters('ait-enqueue-admin-assets',false)){$langCode=AitLangs::getCurrentLanguageCode();$min=((defined('SCRIPT_DEBUG')and
SCRIPT_DEBUG)or
AIT_DEV)?'':'.min';wp_enqueue_style('ait-colorpicker',"{$assetsUrl}/libs/colorpicker/colorpicker.css",array(),'2.2.1');wp_enqueue_style('ait-jquery-chosen',"{$assetsUrl}/libs/chosen/chosen.css",array(),'0.9.10');wp_enqueue_style('jquery-ui',"{$assetsUrl}/libs/jquery-ui/jquery-ui.css",array('media-views'),AIT_THEME_VERSION);wp_enqueue_style('ait-jquery-timepicker-addon',"{$assetsUrl}/libs/timepicker-addon/jquery-ui-timepicker-addon{$min}.css",array(),AIT_THEME_VERSION);wp_enqueue_style('jquery-switch',"{$assetsUrl}/libs/jquery-switch/jquery.switch.css",array(),'0.4.1');wp_enqueue_style('ait-admin-style',"{$assetsUrl}/css/style.css",array('media-views'),AIT_THEME_VERSION);wp_enqueue_style('ait-admin-options-controls',"{$assetsUrl}/css/options-controls".($pagenow=='edit.php'?"-quickedit":"").".css",array('ait-admin-style','ait-jquery-chosen'),AIT_THEME_VERSION);$fontCssFile=aitUrl('css','/libs/font-awesome.min.css');if($fontCssFile){wp_enqueue_style('ait-font-awesome-select',$fontCssFile,array(),'4.2.0');}wp_dequeue_style('myStyleSheets');wp_dequeue_style('chosenStyle');wp_enqueue_script('ait.admin',"{$assetsUrl}/js/ait.admin.js",array('media-editor'),AIT_THEME_VERSION,TRUE);self::adminGlobalJsSettings();wp_register_script('ait-jquery-filedownload',"{$assetsUrl}/libs/file-download/jquery.fileDownload{$min}.js",array('jquery','ait.admin'),'1.3.3',TRUE);wp_enqueue_script('ait-colorpicker',"{$assetsUrl}/libs/colorpicker/colorpicker{$min}.js",array('jquery'),'2.2.1',TRUE);wp_enqueue_script('ait-jquery-ui-touch',"{$assetsUrl}/libs/jquery-touch-punch/jquery.ui.touch-punch{$min}.js",array('jquery'),'0.2.3',TRUE);wp_enqueue_script('ait-jquery-chosen',"{$assetsUrl}/libs/chosen/chosen.jquery{$min}.js",array('jquery'),'1.0.0',TRUE);wp_enqueue_script('ait-jquery-sheepit',"{$assetsUrl}/libs/sheepit/jquery.sheepItPlugin{$min}.js",array('jquery','ait.admin'),'1.1.1-ait-1',TRUE);wp_enqueue_script('ait-jquery-deparam',"{$assetsUrl}/libs/jquery-deparam/jquery-deparam{$min}.js",array('jquery','ait.admin'),FALSE,TRUE);wp_enqueue_script('ait-jquery-rangeinput',"{$assetsUrl}/libs/rangeinput/rangeinput.min.js",array('jquery','ait.admin'),'1.2.7',TRUE);wp_enqueue_script('ait-jquery-numberinput',"{$assetsUrl}/libs/numberinput/numberinput{$min}.js",array('jquery','ait.admin'),FALSE,TRUE);wp_enqueue_script('ait-jquery-truncate',"{$assetsUrl}/libs/jquery-truncate/jquery.truncate{$min}.js",array('jquery','ait.admin'),FALSE,TRUE);wp_enqueue_script('ait-jquery-timepicker-addon',"{$assetsUrl}/libs/timepicker-addon/jquery-ui-timepicker-addon{$min}.js",array('jquery','ait.admin','jquery-ui-slider','jquery-ui-datepicker'),FALSE,TRUE);if($langCode!=='en'){wp_enqueue_script('ait-jquery-datepicker-translation',"{$assetsUrl}/libs/datepicker/jquery-ui-i18n{$min}.js",array('jquery','ait.admin','jquery-ui-datepicker'),FALSE,TRUE);wp_enqueue_script('ait-jquery-timepicker-translation',"{$assetsUrl}/libs/timepicker-addon/jquery-ui-timepicker-addon-i18n{$min}.js",array('jquery','ait.admin'),FALSE,TRUE);}wp_enqueue_script('ait-jquery-switch',"{$assetsUrl}/libs/jquery-switch/jquery.switch{$min}.js",array('jquery','ait.admin'),FALSE,TRUE);wp_enqueue_script('ait-bootstrap-dropdowns',"{$assetsUrl}/libs/bootstrap-dropdowns/bootstrap-dropdowns{$min}.js",array('jquery','ait.admin'),FALSE,TRUE);$t=aitOptions()->getOptionsByType('theme');$gmapsApiKey=empty($t['google']['mapsApiKey'])?"":$t['google']['mapsApiKey'];wp_enqueue_script('ait-google-maps',"//maps.google.com/maps/api/js?key={$gmapsApiKey}&language=".AitLangs::getGmapsLang(),array('jquery'),FALSE,TRUE);wp_enqueue_script('ait-jquery-gmap3',"{$assetsUrl}/libs/gmap3/gmap3.min.js",array('jquery','ait.admin','ait-google-maps'),FALSE,TRUE);wp_enqueue_script('ait-jquery-raty',"{$assetsUrl}/libs/raty/jquery.raty-2.5.2.js",array('jquery'),'2.5.2',TRUE);wp_enqueue_media();wp_enqueue_script('ait.admin.Tabs',"{$assetsUrl}/js/ait.admin.tabs.js",array('ait.admin','jquery'),AIT_THEME_VERSION,TRUE);wp_enqueue_script('ait.admin.options',"{$assetsUrl}/js/ait.admin.options.js",array('ait.admin','jquery','jquery-ui-tabs','ait-jquery-chosen','jquery-ui-datepicker','ait-jquery-gmap3'),AIT_THEME_VERSION,TRUE);wp_enqueue_script('ait.admin.backup',"{$assetsUrl}/js/ait.admin.backup.js",array('ait.admin','jquery','ait-jquery-filedownload'),AIT_THEME_VERSION,TRUE);wp_enqueue_script('ait.admin.options.elements',"{$assetsUrl}/js/ait.admin.options.elements.js",array('ait.admin','ait.admin.options','jquery-ui-draggable','jquery-ui-droppable','jquery-ui-sortable'),AIT_THEME_VERSION,TRUE);wp_enqueue_script('ait.admin.nav-menus',"{$assetsUrl}/js/ait.admin.nav-menus.js",array('ait.admin','ait.admin.options','jquery-ui-draggable','jquery-ui-droppable','jquery-ui-sortable'),AIT_THEME_VERSION,TRUE);}}static
function
adminGlobalJsSettings(){$u=wp_upload_dir();$settings=array('ajax'=>array('url'=>admin_url('admin-ajax.php'),'actions'=>array()),'currentPage'=>self::getCurrentPageSlug(),'paths'=>array('root'=>aitPaths()->url->root,'theme'=>aitPaths()->url->theme,'wpcontent'=>content_url(),'uploads'=>$u['baseurl']),'l10n'=>array('save'=>array('working'=>__('&hellip; saving &hellip;','ait-admin'),'done'=>esc_html__('settings were saved successfully','ait-admin'),'error'=>esc_html__('there was an error during saving','ait-admin')),'reset'=>array('working'=>__('Resetting&hellip;','ait-admin'),'done'=>__('Successfully reset. This page will reload&hellip;','ait-admin')),'confirm'=>array('removeElement'=>esc_html__('Are you sure you want to remove this element?','ait-admin'),'removeCustomOptions'=>esc_html__('Are you sure you want to delete custom page options of this page?','ait-admin'),'addCustomOptions'=>__("You are about to create custom options for page:\n{pageTitle}.\nIs this ok?",'ait-admin')),'datetimes'=>array('dateFormat'=>AitUtils::phpDate2jsDate(get_option('date_format')),'startOfWeek'=>get_option('start_of_week')),'labels'=>array('settingsForSpecialPageType'=>esc_html__("Special page",'ait-admin'),'settingsForStandardPageType'=>esc_html__("Standard page",'ait-admin')),'elementUserDescriptionPlaceholder'=>esc_html__('Click to add custom description','ait-admin'),'backup'=>array('info'=>array('noBackupFile'=>esc_html__('Please select backup file','ait-admin'),'selectedBadFileFix'=>__("You selected option '{option}' but file was '{filename}'. We corrected this for you :)",'ait-admin'),'importBackup'=>esc_html__('Are you sure you want to import backup? All tables will be truncated before import!','ait-admin'),'importDemoContent'=>esc_html__('Are you sure you want to import demo content? Whole content of your website will be replaced!','ait-admin')),'import'=>array('working'=>esc_html__("Importing...",'ait-admin'),'done'=>esc_html__('Importing is done. Check out the report.','ait-admin'),'error'=>esc_html__('There was an error during importing. Check out the report.','ait-admin')),'export'=>array('working'=>esc_html__("Exporting...",'ait-admin'),'done'=>esc_html__('You just got a file download dialog or ribbon.','ait-admin'),'error'=>esc_html__('Your file download failed. Please try again.','ait-admin')))));$class='AitAdminAjax';$methods=get_class_methods($class);$r=new
NClassReflection($class);foreach($methods
as$method){if($r->getMethod($method)->getAnnotation('WpAjax')===true){$settings['ajax']['actions'][$method]="admin:{$method}";}}wp_localize_script('ait.admin','AitAdminJsSettings',apply_filters('ait-admin-global-js-settings',$settings));}static
function
pageBuilderTutorial(){$screen='toplevel_page_ait-pages-options';$pointers=array(array('id'=>'ait-pb-1','screen'=>$screen,'target'=>'.full-pagebuilder #ait-page-options-selection','title'=>__('Page Select','ait-admin'),'content'=>__('This panel indicates the page you are currently editing. To edit different one, you can click on this dropdown and pick from the list of all pages.','ait-admin'),'position'=>array('edge'=>'top','align'=>'middle')),array('id'=>'ait-pb-2','screen'=>$screen,'target'=>'.ait-custom-header-tools','title'=>__('Page Tools','ait-admin'),'content'=>__('Here you can find useful tools to manage your pages. E.g. import options from different page or quick view of the current one.','ait-admin'),'position'=>array('edge'=>'top','align'=>'left')),array('id'=>'ait-pb-3','screen'=>$screen,'target'=>'#ait-available-elements','title'=>__('Page Builder Elements','ait-admin'),'content'=>__('This is the list of all available elements which you can click on or drag and drop to add to your page. Elements are categorized as following: Columnable and Fullwidth elements. Columnable ones can be dropped also right into Columns element.','ait-admin'),'position'=>array('edge'=>'top','align'=>'left')),array('id'=>'ait-pb-4','screen'=>$screen,'target'=>'#ait-used-elements-sortable-wrapper','title'=>__('Drop your elements here','ait-admin'),'content'=>__('Elements are dropped into this area. You can sort and edit these elements.','ait-admin'),'position'=>array('edge'=>'bottom','align'=>'middle')),array('id'=>'ait-pb-5','screen'=>$screen,'target'=>'#ait-used-elements-unsortable','title'=>__('Unsortable Elements','ait-admin'),'content'=>__('These elements can be fully edited but not sorted. They have their own place in the content of your page. Like head should be always on top of your body, right? ;)','ait-admin'),'position'=>array('edge'=>'bottom','align'=>'middle')),array('id'=>'ait-pb-6','screen'=>$screen,'target'=>'#ait-layout-options','title'=>__('Page Layout Options','ait-admin'),'content'=>__('You guess right, if you think these options are important. As they are on the very top and separated. They control the overall layout of your page like sidebars or footer.','ait-admin'),'position'=>array('edge'=>'top','align'=>'left')),array('id'=>'ait-pb-7','screen'=>$screen,'target'=>'.ait-header-save','title'=>__('Save your changes','ait-admin'),'content'=>__("And at last behold the save button. It's pretty big so you shouldn't forget to save your changes. So good luck in building!",'ait-admin'),'position'=>array('edge'=>'top','align'=>'right')));$aitPointers=new
WP_Help_Pointer($pointers,true);}static
function
addPageBuilderButton($editorId){$s=get_current_screen();$post=get_post();if($post
and$post->post_type=='page'and$s->id=='page'){printf('<a href="#" id="ait-goto-page-builder-button" class="button button-primary" data-ait-empty-title-note="%s">%s</a>',esc_html__('Please enter title of the page','ait-admin'),esc_html__('Save and Open in Page Builder','ait-admin'));}}static
function
modifyPageRowActions(){add_filter('page_row_actions',array(__CLASS__,'addPageBuilderLinkToPageRowActions'),10,2);}static
function
addPageBuilderLinkToPageRowActions($actions,$page){$args=array('page'=>'pages-options','oid'=>'_page_'.$page->ID);if($page->post_status!='auto-draft'){if(get_option('show_on_front')=='page'){if($b=get_option('page_for_posts')){$blog=(int)$b;if($page->ID==$blog)$args['oid']="_blog";}}$title=esc_html__('Page Builder','ait-admin');$args['oidnonce']=AitUtils::nonce('oidnonce');$url=esc_url(AitUtils::adminPageUrl($args));$link="<a href=\"$url\">$title</a>";$actions['page_builder']=$link;}return$actions;}static
function
redirectToPageBuilder($location,$postId){if(!isset($_POST['ait-redirect-to-page-builder'])or$_POST['post_type']!='page'){return$location;}$args=array('page'=>'pages-options','oid'=>'_page_'.$postId);$r=aitOptions()->getLocalOptionsRegister();$blogId=0;if(get_option('show_on_front')=='page'){if($b=get_option('page_for_posts')){$blogId=(int)$b;if($postId==$blogId){$args['oid']="_blog";}}}if(!in_array("_page_{$postId}",$r['pages'])and$postId!=$blogId){$args['oidnonce']=AitUtils::nonce('oidnonce');}$url=AitUtils::adminPageUrl($args);return
esc_url_raw($url);}}class
AitQuickEditOptionsControlsRenderer
extends
AitOptionsControlsRenderer{function
render($fullConfigOptions=array(),$defaults=array(),$options=array()){?>
		<fieldset class="ait-meta<?php if(isset($options['hidden'])&&$options['hidden']):?> hidden<?php endif?>">
			<div class="inline-edit-col">
			<?php

parent::render($fullConfigOptions,$defaults,$options);?>
            </div>
		</fieldset>
		<?php
}}class
AitElementsControlsRenderer
extends
AitOptionsControlsRenderer{protected$usedUnsortableElements=array();protected$usedSortableElements=array();protected$usedElementsIds=array();protected$availableFullWidthElements=array();protected$availableColumnableElements=array();protected$em;function
__construct($params){parent::__construct($params);$this->em=aitManager('elements');$this->prepareUsedElements();foreach(array_values($this->options)as$element){$this->usedElementsIds[key($element)]=true;}$this->prepareAvailableElements();}function
renderUsedUnsortableElements(){foreach($this->options
as$i=>$el){$elIds[key($el)]=$i;}$params=array();foreach($this->defaults
as$i=>$el){$elId=key($el);if(isset($elIds[$elId])&&isset($this->usedUnsortableElements[$elIds[$elId]])){$el=$this->usedUnsortableElements[$elIds[$elId]];$params['htmlId']=sanitize_key(sprintf("ait-%s-element-%s-%s",$this->isRenderingDefaultLayout?'global':'local',$el->id,$elIds[$elId]));$params['htmlClass']=' ait-used-element ';$params['clone']=($el->cloneable
and!$this->isRenderingDefaultLayout);if(!$el->isDisplay()or!apply_filters("ait-allow-render-controls-{$this->configType}-{$elId}",true,$this->oid)){$params['htmlClass'].=' ait-element-off';}$this->renderUnsortableElement($el,(object)$params);}}}function
renderUsedSortableElementsHandlers(){foreach($this->usedSortableElements
as$element){if($this->em->isElementSidebarsBoundary($element->getId())){$this->renderSidebarsBoundaryElement($element);continue;}$this->renderElementHandler($element,'sortable');}}function
renderUsedSortableElementsContents(){foreach($this->usedSortableElements
as$element){if($this->em->isElementSidebarsBoundary($element->getId())){continue;}$this->renderElementContent($element);}}function
renderAvailableElementsHandlers(){$this->options=$this->defaults;?>

		<div class="ait-simple-tabs-content">
			<div id="ait-available-elements-droppable-to-columns" class="ait-simple-tab-content active">
				<?php

foreach($this->availableColumnableElements
as$element){$this->renderElementHandler($element,'available');}?>
			</div>

			<div id="ait-available-elements-not-droppable-to-columns" class="ait-simple-tab-content">
				<?php

foreach($this->availableFullWidthElements
as$element){$this->renderElementHandler($element,'available');}?>
			</div>

		</div>

		<?php if(!aitIsPluginActive('toolkit')):?>
		<div class="alert alert-warning">
			<?php ?>
			<span class="text"><i class="fa fa-download big"></i> <?php printf(__('These elements are available in the %s Plugin','ait-admin'),'AIT Elements Toolkit')?></span>
			<a href="https://www.ait-themes.club/wordpress-plugins/ait-elements-toolkit/?utm_source=wp-admin&utm_medium=wp-admin-banner&utm_campaign=Free-Theme" target="_blank" class="ait-button positive uppercase"><?php _e('Download Plugin','ait-admin')?></a>
		</div>
		<?php endif;?>

	<?php
}function
renderAvailableElementsContents(){$availableElements=array_merge($this->availableFullWidthElements,$this->availableColumnableElements);foreach($availableElements
as$element){$this->renderElementContent($element);}}protected
function
renderElementHandler(AitElement$element,$type){$htmlElementId=sanitize_key(sprintf("ait-%s-element-%s-__%s__",$this->isRenderingDefaultLayout?'global':'local',$element->getId(),$element->getOptionsControlsGroup()->getIndex()));$htmlElementContentId=$htmlElementId.'-content';$htmlDataClone=$element->isCloneable()&&!$this->isRenderingDefaultLayout;$htmlClass=$element->isUsed()?'ait-used-element':'ait-available-element';$htmlOptExample=sanitize_key(sprintf("ait-opt-elements-%s-__opt__-__%s__",$element->getId(),$element->getOptionsControlsGroup()->getIndex()));if(!$element->isDisplay()or!apply_filters("ait-allow-render-controls-{$this->configType}-{$element->getId()}",true,$this->oid))$htmlClass.=' ait-element-off';if($element->isColumnable())$htmlClass.=' ait-element-columnable';if($element->option('@columns-element-index'))$htmlClass.=' hidden in-column';if($element->isDisabled())$htmlClass.=' ait-element-disabled';if($element->getOptionsControlsGroup()->getIndex()==AitElement::UNDEFINED_INDEX&&((isset($this->usedElementsIds[$element->getId()])and!$element->isCloneable()and!$this->isRenderingDefaultLayout)or(isset($this->usedElementsIds[$element->getId()])and$this->isRenderingDefaultLayout))){$htmlClass.=' hidden';}if(AIT_THEME_PACKAGE=='standard'and$element->isDisabled())$htmlClass.=' hidden';if($element->getId()==='comments'and
aitOptions()->isQueryForSpecialPage(array('_404','_search','_archive','_wc_product','_wc_shop'))){return;}?>

		<div id="<?php echo$htmlElementId?>" class="ait-element <?php echo$htmlClass?> <?php echo$element->getId()=='columns'?'ait-element-columns no-popup':'';?>"
			<?php

echo
aitDataAttr('element',array('type'=>$element->getId(),'clone'=>$htmlDataClone,'global'=>$this->isRenderingDefaultLayout));echo
aitDataAttr('element-id',$htmlElementId);echo
aitDataAttr('element-content-id',$htmlElementContentId);echo
aitDataAttr('columns-element-index',$element->option('@columns-element-index'));echo
aitDataAttr('columns-element-column-index',$element->option('@columns-element-column-index'));?>
			>
			<div class="ait-element-handler">
				<div class="ait-element-actions">
					<!-- <a class="ait-element-help" href="#">?</a> -->
					<?php if($element->getId()!='columns'):?>
						<a class="ait-element-edit" href="#"><i class="fa fa-edit"></i></a>
					<?php else:?>
						<a class="ait-element-toggle" href="#"><i class="fa fa-caret-up"></i></a>
					<?php endif;?>

					<?php if($element->getId()!='content'and$element->getId()!='comments'and$element->isSortable()):?>
						<a class="ait-element-remove" href="#"><i class="fa fa-close"></i></a>
					<?php endif;?>
				</div>
				<?php

if($element->hasOption('@element-user-description')&&$element->option('@element-user-description')!=''){$elementUserDescription=$element->option('@element-user-description');$elementUserDescriptionCssClass=' element-has-user-description';}else{$elementUserDescription='';$elementUserDescriptionCssClass='';}$styleAttr='style="background-color: '.$element->getColor().'"';if($type=='available'){$styleAttr='style="color: '.$element->getColor().'"';}?>

				<div class="ait-element-icon ait-touch-handle" <?php echo$styleAttr?> data-color="<?php echo$element->getColor();?>">
					<?php if($type=='available'):?>
						<div class="ait-element-background" style="background-color: <?php echo$element->getColor();?>;"></div>
					<?php endif;?>
					<i class="fa <?php echo$element->getIcon();?>"></i>
				</div>
				<div class="ait-element-title">
                    <h4><?php $eschtmle='esc_html_e';$eschtmle($element->getTitle(),'ait-admin');?></h4>
					<span class="ait-element-user-description<?php echo$elementUserDescriptionCssClass;?>" title="<?php _e('Edit element description','ait-admin');?>"><?php echo$elementUserDescription?></span>
				</div>
			</div>

			<?php
$p=AitLangs::checkIfPostAndGetLang();$elementData=array('elementId'=>$htmlElementId,'contentId'=>$htmlElementContentId,'optId'=>$htmlOptExample,'currentLocale'=>$p?$p->locale:AitLangs::getDefaultLocale());?>

			<?php if($contentPreview=$element->getContentPreview($elementData)):?>
				<div class="ait-element-preview">
					<div class="ait-element-preview-content"><?php

echo(is_array($contentPreview)?$contentPreview['content']:$contentPreview);?></div>
					<?php echo(!empty($contentPreview['script'])?$contentPreview['script']:'');?>
				</div>
			<?php endif;?>
		</div>
	<?php
}protected
function
renderElementContent(AitElement$element){if($element->getId()==='comments'and
aitOptions()->isQueryForSpecialPage(array('_404','_search','_archive','_wc_product','_wc_shop'))){return;}$htmlElementId=sanitize_key(sprintf("ait-%s-element-%s-__%s__",$this->isRenderingDefaultLayout?'global':'local',$element->getId(),$element->getOptionsControlsGroup()->getIndex()));$htmlId=$htmlElementId.'-content';if($element->getOptionsControlsGroup()->getIndex()==AitElement::UNDEFINED_INDEX)$htmlId.='-prototype';?>
		<div id="<?php echo$htmlId;?>" class="ait-element-content" <?php echo
aitDataAttr('element-id',$htmlElementId);?>>
			<div class="ait-element-wrap">
				<?php if($element->getId()!='columns'):?>

					<?php

if($element->hasOption('@element-user-description')&&$element->option('@element-user-description')!=''){$elementUserDescription=$element->option('@element-user-description');$elementUserDescriptionCssClass=' element-has-user-description';}else{$elementUserDescription='';$elementUserDescriptionCssClass='';}?>

					<h3><?php echo$element->title;?><span class="ait-element-user-description<?php echo$elementUserDescriptionCssClass;?>" title="<?php _e('Edit element description','ait-admin');?>"><?php echo$elementUserDescription?></span></h3>
				<?php endif;?>
				<div class="ait-element-controls">
					<?php
$this->renderOptionsControlsGroup($element->getOptionsControlsGroup())?>
				</div>
				<?php if($element->getId()!='columns'):?>
					<div class="ait-element-actions">
						<button class="ait-button ait-element-close" type="button">OK</button>
					</div>
				<?php endif;?>
			</div>
		</div>
	<?php
}protected
function
renderUnsortableElement(AitElement$el,$params){?>
		<div
			id="<?php echo$params->htmlId?>"
			class="ait-element <?php echo$params->htmlClass?>"
			<?php

echo
aitDataAttr('element',array('type'=>$el->id,'clone'=>$params->clone,'global'=>$this->isRenderingDefaultLayout));echo
aitDataAttr('element-id',$params->htmlId);echo
aitDataAttr('element-content-id',$params->htmlId.'-content');echo
aitDataAttr('columns-element-index',$el->option('@columns-element-index'));echo
aitDataAttr('columns-element-column-index',$el->option('@columns-element-column-index'));?>
		>

			<div class="ait-element-handler">
				<div class="ait-element-actions">
					<!-- <a class="ait-element-help" href="#">?</a> -->
					<a class="ait-element-edit" href="#"><i class="fa fa-edit"></i></a>
					<?php if($el->id!='content'and$el->id!='comments'and$el->sortable):?>
						<a class="ait-element-remove" href="#"><i class="fa fa-close"></i></a>
					<?php endif;?>
				</div>
				<div class="ait-element-icon" style="background-color: <?php echo$el->getColor();?>;" data-color="<?php echo$el->getColor();?>"><i class="fa <?php echo$el->getIcon();?>"></i></div>
				<div class="ait-element-title">
					<h4><?php $eschtmle='esc_html_e';$eschtmle($el->getTitle(),'ait-admin');?></h4>
				</div>
			</div>

			<div id="<?php echo$params->htmlId;?>-content" class="ait-element-content" <?php echo
aitDataAttr('element-id',$params->htmlId);?>>

				<div class="ait-element-wrap">
					<h3><?php echo$el->title;?></h3>
					<div class="ait-element-controls">
						<?php
$this->renderOptionsControlsGroup($el->getOptionsControlsGroup());?>
					</div>
					<div class="ait-element-actions">
						<button class="ait-button ait-element-close" type="button">OK</button>
					</div>
				</div>
			</div>
		</div>
	<?php
}private
function
renderSidebarsBoundaryElement(AitElement$el){$index=$el->getOptionsControlsGroup()->getIndex();$htmlElementId=sanitize_key(sprintf("ait-%s-element-%s-__%d__",$this->isRenderingDefaultLayout?'global':'local',$el->id,$index));$htmlElementContentId=$htmlElementId.'-content';?>
		<div id="<?php echo$htmlElementId?>" class="ait-element ait-used-element ait-sidebars-boundary no-popup ait-<?php echo$el->id?>"
			<?php

echo
aitDataAttr('element',array('type'=>$el->id,'clone'=>false,'global'=>true));echo
aitDataAttr('element-id',$htmlElementId);echo
aitDataAttr('element-content-id',$htmlElementContentId);?>
			>
			<div class="ait-element-handler">
				<div class="ait-element-title">
					<h4 class="ait-touch-handle">
						<?php printf(__('Sidebars <strong>%s</strong> here','ait-admin'),($el->id=='sidebars-boundary-start')?__('start','ait-admin'):__('end','ait-admin'))?>
					</h4>
				</div>
			</div>
			<div id="<?php echo$htmlElementId;?>-content" class="ait-element-content" <?php echo
aitDataAttr('element-id',$htmlElementContentId);?>>
				<?php
$sections=$el->getOptionsControlsGroup()->getSections();$section=reset($sections);$option=$section->getOptionControl($el->getId());echo$option->getHtml();?>
			</div>
		</div>
	<?php
}private
function
prepareUsedElements(){$usedElements=$this->em->createElementsFromOptions($this->options,$this->oid);foreach($usedElements
as$index=>$element){if(!isset($this->em->prototypes[$element->getId()])||$element->isDisabled()){continue;}$element->setUsed(true);if($element->isSortable()){$this->usedSortableElements[(string)$index]=$element;}else{$this->usedUnsortableElements[(string)$index]=$element;}}}private
function
prepareAvailableElements(){$availableElements=$this->em->createElementsFromOptions($this->defaults,$this->oid);$oldLC=setlocale(LC_COLLATE,"0");if(PHP_OS==='WINNT'){setlocale(LC_COLLATE,null);}else{$l=get_locale();setlocale(LC_COLLATE,"$l.UTF8","$l.UTF-8");}usort($availableElements,function($a,$b){return
strcoll($a->getTitle(),$b->getTitle());});setlocale(LC_COLLATE,$oldLC);foreach($availableElements
as$index=>$el){$el->getOptionsControlsGroup()->setIndex(AitElement::UNDEFINED_INDEX);if($this->em->isElementSidebarsBoundary($el->getId())){continue;}if($el->isColumnable()){$this->availableColumnableElements[$index]=$el;}else{$this->availableFullWidthElements[$index]=$el;}}}}if(!class_exists('Plugin_Upgrader',false)){require_once
ABSPATH.'wp-admin/includes/class-wp-upgrader.php';}class
AitPluginBulkInstaller
extends
Plugin_Upgrader{public$result;public$bulk=true;protected$clear_destination=false;function
__construct($skin){parent::__construct($skin);}function
run($options){$result=parent::run($options);$this->install_strings();return$result;}function
bulkInstall($plugins,$args=array()){add_filter('upgrader_post_install',array($this,'autoActivate'),10);$defaults=array('clear_update_cache'=>true);$parsed_args=wp_parse_args($args,$defaults);$this->init();$this->bulk=true;$results=array();$this->update_count=count($plugins);$this->update_current=0;foreach($plugins
as$plugin){$this->update_current++;$result=$this->run(array('package'=>$plugin,'abort_if_destination_exists'=>!AitUtils::contains($plugin,'ait-updater'),'destination'=>WP_PLUGIN_DIR,'clear_destination'=>false,'clear_working'=>true,'is_multi'=>true,'hook_extra'=>array('plugin'=>$plugin)));$results[$plugin]=$this->result;if(false===$result){break;}}do_action('upgrader_process_complete',$this,array('action'=>'install','type'=>'plugin','bulk'=>true,'plugins'=>$plugins));remove_filter('upgrader_post_install',array($this,'autoActivate'),10);wp_clean_plugins_cache($parsed_args['clear_update_cache']);return$results;}function
autoActivate($bool){wp_clean_plugins_cache();$pluginInfo=$this->plugin_info();if(!is_plugin_active($pluginInfo)){$activate=activate_plugin($pluginInfo);}return$bool;}}