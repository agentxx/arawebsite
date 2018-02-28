<?php

namespace Ait\SystemApi\Resources;


class Stats
{

	public function all()
	{
		return $this->api->get('/stats');
	}

}
