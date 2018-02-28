<?php


class AitPhpInfoReporter
{

	public static function report()
	{
		$reporter = new self;
		?>
		<div id="ait-php-info-report">
			<?php echo $reporter->getReport(); ?>
		</div>
		<?php
	}



	public function getReport()
	{
		$report = '';

		ob_start();
		phpinfo();
		$report = ob_get_clean();

		$report = str_replace(array('</html>', '</body>'), '', $report);
		$report = substr($report, strpos($report, "<body>"), strlen($report));
		$report = str_replace('<body>', '', $report);

		$credits = strpos($report, "<h1>PHP Credits</h1>");
		if($credits !== false){
			$report = substr($report, 0, $credits);
			$report .= '</div> <!-- /.center -->';
		}

		return $report;
	}
}