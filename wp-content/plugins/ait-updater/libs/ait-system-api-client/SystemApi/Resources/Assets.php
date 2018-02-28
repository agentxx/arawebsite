<?php

namespace Ait\SystemApi\Resources;


class Assets
{

	public function get($assetCodename)
	{
		if(!$assetCodename){
			trigger_error("You must provide codename of asset", E_USER_WARNING);
			return false;
		}

		return $this->api->get('/assets/info/' . $assetCodename);
	}



	public function all()
	{
		return $this->api->get('/assets', array('_as_array' => true));
	}



	public function count()
	{
		return $this->api->get('/assets/count');
	}
}
