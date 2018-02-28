<?php

/*
 * AIT WordPress Plugin
 *
 * Copyright (c) 2015, Affinity Information Technology, s.r.o. (http://ait-themes.com)
 */

class AitExtendedElementsOptions
{


	public static function getOptions($element, $theme = '')
	{
		switch ($element) {
			case 'search-form':
				return self::getSearchFormOptions();
				break;

			default:
				break;
		}
	}



	public static function getSearchFormOptions()
	{
		return array(
			"label" => "bla",
			"type" => "text",
			"default" => "bla",
			"basic" => true,
		);
	}








}





