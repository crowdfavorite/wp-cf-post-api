<?php
/**
 * JSON ENCODE and DECODE for PHP < 5.2.0
 * Checks if json_encode is not available and defines json_encode & json_decode
 *
 * @version 1.0
 * @uses the Pear Class Services_JSON - http://pear.php.net/package/Services_JSON
 */
 
if (!function_exists('json_encode') && !class_exists('Services_JSON')) {
	require_once('JSON.php');
}	

/**
 * cfct_json_encode
 *
 * @param array/object $json 
 * @return string json
 */
if (!function_exists('cf_json_encode')) {
	function cf_json_encode($data) {
		if (function_exists('json_encode')) {
			return json_encode($data);
		}
		else {
			global $cfct_json_object;
			if (!($cfct_json_object instanceof Services_JSON)) {
				$cfct_json_object = new Services_JSON();
			}
			return $cfct_json_object->encode($data);
		}
	}
}

/**
 * cfct_json_decode
 *
 * @param string $json 
 * @param bool $array - toggle true to return array, false to return object  
 * @return array/object
 */
if (!function_exists('cf_json_decode')) {
	function cf_json_decode($json, $array) {
		if (function_exists('json_decode')) {
			return json_decode($json, $array);
		}
		else {
			global $cfct_json_object;
			if (!($cfct_json_object instanceof Services_JSON)) {
				$cfct_json_object = new Services_JSON();
			}
			$cfct_json_object->use = $array ? SERVICES_JSON_LOOSE_TYPE : 0;
			return $cfct_json_object->decode($json);
		}
	}
}
?>