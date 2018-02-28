<?php
header("HTTP/1.0 200 OK");
header('Content-Type: application/json');


if (isset($_FILES["file"])) {
	// return file server path
	$wp_root = dirname(__FILE__) .'/../../../../';
	if(file_exists($wp_root . 'wp-load.php')) {
		require_once($wp_root . "wp-load.php");
	} else if(file_exists($wp_root . 'wp-config.php')) {
		require_once($wp_root . "wp-config.php");
	} else {
		exit;
	}

	$tempPath   = $_FILES["file"]["tmp_name"];
	$uploadDir  = wp_upload_dir();
	$targetPath = '' . $uploadDir['basedir'] . '/' . $_FILES["file"]["name"];

	move_uploaded_file( $tempPath, $targetPath);

	$firstDataRow = 0;
	$foundRows = 0;
	if (($handle = fopen($targetPath, "r")) !== FALSE) {

		//get delimiter from first row
		$data_row = fgets($handle, 10000);

		if (strpos($data_row, 'sep=') === false) {
			$delimiter = false;
			$report = array(
				'type' => 'error',
				'message' => __('Missing separator information', 'ait-csv-import-export'),
			);
			echo json_encode( array( 'result' => false, 'report' => $report ) );
			return;
		} else {
			$delimiter = substr($data_row, strpos($data_row, 'sep=') + 4, 1);
		}

		$firstDataRow = 1;


		// skip empty lines until row headings reached
		while ( array(null) == fgetcsv($handle, 10000, $delimiter, '"') ) {
			$firstDataRow++;
			continue;
		}


		// consider the headings row
		$firstDataRow++;

		// skip empty lines until first csv entry reached
		while ( array(null) == fgetcsv($handle, 10000, $delimiter, '"') ) {
			$firstDataRow++;
			continue;
		}


		// count all valid rows without empty lines
		$foundRows = 1;
		while ( ($data_row = fgetcsv($handle, 10000, $delimiter, '"')) !== FALSE ) {
			if (array(null) == $data_row) {
				continue;
			}
			$foundRows++;
		}
	}
	fclose($handle);

	echo json_encode( array( 'status' => true, 'file' => $targetPath, 'delimiter' => $delimiter, 'firstDataRow' => $firstDataRow, 'foundRows' => $foundRows ) );
	exit();
}

if (isset($_POST['delete_file'])) {
	$return = unlink($_POST['delete_file']);

	echo json_encode( array( 'result' => $result ) );
}


 ?>