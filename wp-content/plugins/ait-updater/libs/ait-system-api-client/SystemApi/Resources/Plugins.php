<?php

namespace Ait\SystemApi\Resources;


class Plugins
{

	public function get($codename)
	{
		if(!$codename){
			trigger_error("You must provide codename of theme", E_USER_WARNING);
			return false;
		}

		return $this->api->get('/plugins/info/' . $codename);
	}



	public function all()
	{
		return $this->api->get('/plugins', array('_as_array' => true));
	}



	public function count()
	{
		return $this->api->get('/plugins/count');
	}



	public function checkUpdates($args = array())
	{
		$args['_use_cache'] = false; // do not cache this request, update checks are once in 12 hours or so
		$args['_as_array'] = true;
		return $this->api->post('/plugins/update-check', $args);
	}



	public function download($codename, $args)
	{
		$args['_use_cache'] = false;
		return $this->api->post('/plugins/download/' . $codename, $args);
	}
}
