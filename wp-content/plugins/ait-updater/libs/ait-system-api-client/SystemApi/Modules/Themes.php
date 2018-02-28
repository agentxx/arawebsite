<?php

// this is just for back compat
namespace Ait\SystemApi\Modules {
	class Themes { function checkUpdates() { return new FakeResponse; } }

	class FakeResponse {
		function isSuccessful() { return true; }
		function getData() { return array(); }
	}
}
