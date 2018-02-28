<?php

/**
*
*/
class AitEventsProICSExport
{
	protected static $data;
	protected static $timeZone;

	public static function addEvent($event)
	{
		if (empty(self::$timeZone)) {
			$dateFrom = "DTSTART:".date("Ymd\THis\Z",strtotime($event['start']));
			$dateTo = empty($event['end']) ? '' : "\r\nDTEND:".date("Ymd\THis\Z",strtotime($event['end']));
		} else {
			$dateFrom = "DTSTART;TZID=".self::$timeZone.":".date("Ymd\THis",strtotime($event['start']));
			$dateTo = empty($event['end']) ? '' : "\r\nDTEND;TZID=".self::$timeZone.":".date("Ymd\THis",strtotime($event['end']));
		}
		self::$data .= "BEGIN:VEVENT\r\nORGANIZER:\r\nUID:".date("Ymd\THis\Z")."@".get_bloginfo('wpurl')."\r\n".$dateFrom.$dateTo."\r\nDTSTAMP:".date("Ymd\THis\Z")."\r\nSUMMARY:".$event['name']."\r\nDESCRIPTION:".$event['description']."\r\nCLASS:PUBLIC\r\nBEGIN:VALARM\r\nTRIGGER:-PT10080M\r\nACTION:DISPLAY\r\nDESCRIPTION:Reminder\r\nEND:VALARM\r\nEND:VEVENT\r\n";
	}



	public static function openData()
	{
		self::$timeZone = get_option('timezone_string');
		$prodID = "//".get_bloginfo('name')."//EventsPro//".get_bloginfo('language');
		self::$data = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nMETHOD:PUBLISH\r\nPRODID:".$prodID."\r\n";
	}



	public static function closeData()
	{
		self::$data .= "END:VCALENDAR\r\n";
	}



	public static function getFile()
	{
		header("Content-Type: text/Calendar; charset=utf-8");
        header('Content-Disposition: inline; filename="events_pro.ics"');
        Header('Content-Length: '.strlen(self::$data));
        // Header('Connection: close');

		ob_clean();
        echo self::$data;
		exit;
	}
}


?>