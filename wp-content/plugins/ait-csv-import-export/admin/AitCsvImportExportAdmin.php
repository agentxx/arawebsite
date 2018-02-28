<?php

class AitCsvImportExportAdmin
{
	protected static $instance;

	protected static $pluginFile;


	public static function run($pluginFile)
	{
		self::$pluginFile = $pluginFile;
		self::getInstance()->initialize();
		$basedir = dirname($pluginFile);
		require_once $basedir . '/includes/AitCsvAdminPage.php';
		AitCsvAdminPage::getInstance()->run($pluginFile);

	}

	public function initialize(){

		add_action( 'plugins_loaded', array( $this ,'loadTextdomain' ));
		// add_action( 'admin_menu', array($this, 'createAdminPages'));
		add_action( 'admin_enqueue_scripts', array($this, 'load_import_style'));

		// Ajax functions
		add_action( 'wp_ajax_csvImport', array($this, 'ajaxCSVImport'));
		add_action( 'wp_ajax_nopriv_csvImport', array($this, 'ajaxCSVImport'));
	}

	public static function getInstance() {

		if(self::$instance === null){
			self::$instance = new self;
			return self::$instance;
		}
		return self::$instance;
	}



	public function load_import_style() {
		wp_enqueue_style('import_style', plugin_dir_url(self::$pluginFile) . '/admin/assets/css/admin.css');
		wp_enqueue_script('import_script', plugin_dir_url(self::$pluginFile) . '/admin/assets/scripts/scripts.js');
	}




    function loadTextdomain()
    {
        $var = load_plugin_textdomain('ait-csv-import-export', false, dirname(plugin_basename(self::$pluginFile)) . '/languages');
    }



    /* AJAX FUNCTIONS */
	public static function ajaxCSVImport(){

		if(isset($_POST['data'])){
			require_once dirname(__FILE__) . '/AitCsvImportExportImportType.php';
			require_once dirname(__FILE__) . '/AitCsvImportExportHelpers.php';

			header("HTTP/1.0 200");
			header('Content-Type: application/json');


			// set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext){
			// 	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
			// }, E_NOTICE | E_WARNING);

			$importOptions = array(
				'file'           => $_POST['data']['file'],
				'offset'         => $_POST['data']['offset'],
				'rowsPerRequest' => $_POST['data']['rowsPerRequest'],
				'type'           => $_POST['data']['type'],
				'slug'           => $_POST['data']['slug'],
				'lang'           => $_POST['data']['lang'],
				'delimiter'      => $_POST['data']['delimiter'],
				'firstDataRow'   => $_POST['data']['firstDataRow'],
			);

			$importer = new AitCsvImportExportImportType($importOptions['type'], $importOptions['slug'], $importOptions['lang']);
			$response = $importer->importFile($importOptions);

			// try {
			// 	dns_get_record(...);
			// } catch (ErrorException $e) {
			// 	var_dump($e);
			// }

			// restore_error_handler();

			$data = array();

			$result = true;
			if ($response['type'] == 'error') {
				$result = false;
			}
			$data['response'] = $response;




			echo json_encode( array( 'result' => $result, 'data' => $data) );

			// echo json_encode(array('data' => 'a'));
			// echo json_encode(array(
			// 	'request_data' => $_POST,
			// 	'result'       => $result,
			// 	'data'         => array(),
			// 	'response'     => $response
			// ));


			exit();
		}
	}

}
