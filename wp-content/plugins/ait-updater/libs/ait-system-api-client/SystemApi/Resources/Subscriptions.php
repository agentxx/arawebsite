<?php

namespace Ait\SystemApi\Resources;


class Subscriptions
{


	public function get($type)
	{
		if(!$type){
			trigger_error("You must provide type", E_USER_WARNING);
			return false;
		}

		return $this->api->get('/subscriptions/info/' . $type);
	}



	public function all()
	{
		return $this->api->get('/subscriptions');
	}
}