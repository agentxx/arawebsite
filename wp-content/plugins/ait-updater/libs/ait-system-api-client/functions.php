<?php



/**
 * Gets System API client instance
 * @return Ait\SystemApi\Client
 */
function ait_get_system_api_client()
{
	return Ait\SystemApi\Client::instance();
}



/**
 * Checks if given string contains some placeholders
 * @param  string $string
 * @param  array  $placeholders
 * @return bool
 */
function ait_string_contains_system_placeholder($string, $placeholders)
{
	foreach((array) $placeholders as $needle => $val){
		if($needle !== '' && strpos($string, $needle) !== false) return true;
	}
	return false;
}



/**
 * Replaces placeholders in string with actual values
 * @param  string $value
 * @return string
 */
function ait_replace_system_placeholders($string)
{
	static $placeholders;

	if(empty($placeholders['{themes_count}'])){
		$placeholders = array(
			'{langs_count}'                        => '', // old var
			'{languages_count}'                    => '', // new var
			'{themes_count}'                       => '',
			'{plugins_count}'                      => '',
			'{assets_count}'                       => '',
			'{products_count}'                     => '',
			'{customers_count}'                    => '',

			'{refund_days_limit}'                  => '',

			'{premium_themes_count-1}'             => '',
			'{directory_themes_count-1}'           => '',
			'{premium_plugins_count-1}'            => '',
			'{premium_plugins_count}'              => '',
			'{directory_plugins_count-1}'          => '',
			'{directory_plugins_count}'            => '',
			'{premium_assets_count}'               => '',
			'{premium_themes_count}'               => '',
			'{directory_assets_count}'             => '',

			'{premium_subscription_price}'         => '',
			'{directory_subscription_price}'       => '',
			'{subscription_price}'                 => '', // old var
			'{business_subscription_price}'        => '', // new var
			'{single_subscription_price}'          => '',
			'{per_product_price}'                  => '',
			'{per_theme_price}'                    => '',

			'{total_themes_price}'                 => '',
			'{total_plugins_price}'                => '',
			'{total_assets_price}'                 => '',
			'{total_price}'                        => '',
			'{saving_premium_subscription_price}'  => '',
			'{saving_business_subscription_price}' => '',
		);
	}

	if(empty($string) or !is_string($string) or (is_string($string) and !ait_string_contains_system_placeholder($string, $placeholders))) return $string; // bail early

	static $apiData;

	$api = ait_get_system_api_client();

	if(is_null($apiData)){
		$response = $api->stats->all();
		if($response->isSuccessful()){
			$apiData = $response->data();
			$placeholders['{langs_count}']                        = $apiData->counts->languages;
			$placeholders['{languages_count}']                    = $apiData->counts->languages;
			$placeholders['{themes_count}']                       = $apiData->counts->themes;
			$placeholders['{plugins_count}']                      = $apiData->counts->plugins;
			$placeholders['{assets_count}']                       = $apiData->counts->assets;
			$placeholders['{products_count}']                     = $apiData->counts->products;
			$placeholders['{customers_count}']                    = number_format_i18n($apiData->counts->customers);

			$placeholders['{refund_days_limit}']                  = $apiData->counts->refund_days_limit;

			// $placeholders['{premium_themes_count-1}']             = $apiData->counts->themes - $apiData->counts->themes_directory - 1;

			// !!!temporary fix!!!
			// premium themes now includes all themes
			$placeholders['{premium_themes_count-1}']             = $apiData->counts->themes - 1;
			$placeholders['{premium_themes_count}']               = $apiData->counts->themes;
			// !!!temporary fix!!!


			$placeholders['{directory_themes_count-1}']           = $apiData->counts->themes_directory - 1;
			$placeholders['{premium_plugins_count-1}']            = $apiData->counts->plugins - $apiData->counts->plugins_directory - 1;
			$placeholders['{premium_plugins_count}']              = $apiData->counts->plugins - $apiData->counts->plugins_directory;
			$placeholders['{directory_plugins_count-1}']          = $apiData->counts->plugins_directory -1;
			$placeholders['{directory_plugins_count}']            = $apiData->counts->plugins_directory;
			$placeholders['{premium_assets_count}']               = $apiData->counts->assets - $apiData->counts->assets_directory;
			// $placeholders['{premium_themes_count}']               = $apiData->counts->themes - $apiData->counts->themes_directory;
			$placeholders['{directory_assets_count}']             = $apiData->counts->assets_directory;

			$placeholders['{premium_subscription_price}']         = '$' . $apiData->prices->premium_subscription;
			$placeholders['{directory_subscription_price}']       = '$' . $apiData->prices->directory_subscription;
			$placeholders['{subscription_price}']                 = '$' . $apiData->prices->business_subscription;
			$placeholders['{business_subscription_price}']        = '$' . $apiData->prices->business_subscription;
			$placeholders['{single_subscription_price}']          = '$' . $apiData->prices->single_subscription;
			$placeholders['{per_product_price}']                  = '$' . $apiData->prices->per_product;
			$placeholders['{per_theme_price}']                    = '$' . $apiData->prices->per_theme;

			$placeholders['{total_themes_price}']                 = '$' . $apiData->prices->total_themes;
			$placeholders['{total_plugins_price}']                = '$' . $apiData->prices->total_plugins;
			$placeholders['{total_assets_price}']                 = '$' . $apiData->prices->total_assets;
			$placeholders['{total_price}']                        = '$' . $apiData->prices->total;
			$placeholders['{saving_premium_subscription_price}']  = '$' . $apiData->prices->saving_premium_subscription;
			$placeholders['{saving_business_subscription_price}'] = '$' . $apiData->prices->saving_business_subscription;
		}
	}

	$string = str_replace(array_keys($placeholders), array_values($placeholders), $string);

	return $string;
}
