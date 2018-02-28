<?php

namespace Ait\SystemApi\Resources;


class Themes
{

	public function get($codename)
	{
		if(!$codename){
			trigger_error("You must provide codename of theme", E_USER_WARNING);
			return false;
		}

		return $this->api->get('/themes/info/' . $codename);
	}



	public function all()
	{
		return $this->api->get('/themes', array('_as_array' => true));
	}



	public function count()
	{
		return $this->api->get('/themes/count');
	}



	public function changelog($codename, $version = 'all', $html = true)
	{
		return $this->api->get($this->changelogEndpoint($codename, $version, $html));
	}



	public function changelogEndpoint($codename, $version = 'all', $html = true)
	{
		$params = http_build_query(array(
			'v'          => $version,
			'output'     => $html ? 'html' : 'json',
			'dateformat' => get_option('date_format'),
		));
		return "/themes/changelog/$codename?$params";
	}



	public function checkUpdates($args = array())
	{
		$args['_use_cache'] = false; // do not cache this request, update checks are once in 12 hours or so
		$args['_as_array'] = true;
		return $this->api->post('/themes/update-check', $args);
	}



	public function download($codename, $args)
	{
		$args['_use_cache'] = false;
		return $this->api->post('/themes/download/' . $codename, $args);
	}
}
