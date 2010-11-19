<?php
/*
Plugin Name: CF Post API
Plugin URI: http://crowdfavorite.com
Description: API for posting new content to Wordpress from just about anywhere
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// Include our svn-external'd JSON functions
include_once('cf-json/cf-json.php');

// Area to set configs, like API Key, etc...
function cfapi_configs() {
	define('CFAPI_KEY', apply_filters('cfapi_key', 'fbd05efe098d8ecc0a7d1a6b6316087d'));
	define('CFAPI_META_NAME', '_cfapi_created');
	define('CFAPI_DEBUG', apply_filters('cfapi_debug', false));
}
add_action('init', 'cfapi_configs', 2);

function cfapi_request_handler() {
	if (isset($_POST['cfapi_action'])) {
		switch ($_POST['cfapi_action']) {
			case 'create_post':
				cfapi_request_controller($_POST);
				die();
				break;
			default:
				break;
		}
	}
	else if (isset($_GET['cfapi_action'])) {
		switch ($_GET['cfapi_action']) {
			case 'create_post':
				cfapi_request_controller($_GET);
				die();
				break;
			default:
				break;
		}
	}
}
add_action('init', 'cfapi_request_handler');

function cfapi_request_controller($payload = array()) {
	$inserted = false;
	$errors = array();

	if (isset($payload['cfapi_data']) && is_array($payload['cfapi_data'])) {
		$passed_data = $payload['cfapi_data'];
		
		// Check API Key
		if (isset($passed_data['api_key']) && cfapi_is_valid_api_key(stripslashes($passed_data['api_key']))) {
			// Valid API Key
			do_action('cfapi_pre_process');
			$results = cfapi_process($passed_data);
			do_action('cfapi_post_process', $results);
			$inserted = true;
		}
		else {
			// Invalid API Key
			$errors[] = 'Invalid or missing API Key';
		}
	}
	else {
		$errors[] = 'No post data passed';
	}
	if (!$inserted) {
		$results = array(
			'result' => false, 
			'post_id' => false, 
			'errors' => $errors
		);
	}
	$results = apply_filters('cfapi_final_result_output', $results, $passed_data);
	if (function_exists('cf_json_encode')) {
		echo cf_json_encode($results);
	}
	else {
		echo 'Need cf_json_encode for results';
	}
}

function cfapi_is_valid_api_key($key = null) {
	return ($key === CFAPI_KEY) ? true : false;
}

function cfapi_process($passed_data) {
	// Initialize vars and an error array 
	$post_id = false;
	$result = false;
	$errors = array();
	
	// Make sure we have required fields
	$valid = cfapi_is_data_valid($passed_data, 'post');
	if ($valid['valid']) {
		// All data is sanitized by wp by wp_insert_post ... we can do more if wanted
		$post_id = cfapi_do_insert_post($passed_data);
		if ($post_id) {
			// Post insert succeeded
			$result = true;

			// Now check for postmeta
			if (!empty($passed_data['post_meta'])) {
				foreach($passed_data['post_meta'] as $name => $value) {
					if (!empty($name)) {
						$postmeta_result = cfapi_do_insert_postmeta($post_id, $name, $value);
					}
					else {
						$errors[] = 'Invalid Post Meta: Passed Data: '."\n".$name.'::'.$value;
					}
				}
			}
			
			// Add CF API postmeta
			update_post_meta($post_id, CFAPI_META_NAME, true);
		}
		else {
			// Insert post failed add to errors
			$errors[] = 'Post insert failed.  Passed Data: ' . "\n" . print_r($passed_data, true);
		}
	}
	else {
		$errors[] = 'Submitted post data is not valid: ' . "\n" . print_r($valid, true);
	}

	$final_result = array(
		'result' => $result, 
		'post_id' => $post_id,
		'post_meta' => $postmeta_result,
		'errors' => $errors
	);
	
	return $final_result;
}

function cfapi_is_data_valid($data = array(), $type = 'post') {
	if (!is_array($data) || empty($data)) { return false; }
	
	switch ($type) {
		case 'post':
			$required_fields = array(
				'post_title',
				'post_content'
			);
			break;
		case 'meta':
			$required_fields = array(
				'key',
				'value'
			);
			break;
	}
	
	$invalid = array();
	foreach ($required_fields as $req_fld) {
		/* 
		* Reqs for validation:
		* - exists
		* - not empty
		*/
		if (empty($data[$req_fld])) {
			// We have an invalid field
			$invalid[] = $req_fld;
		}
	}
	
	if (count($invalid)) {
		// We're invalid
		return array('valid' => false, 'fields' => $invalid);
	}
	else {
		return array('valid' => true);
	}
}

function cfapi_do_insert_post($data = null) {
	if (is_null($data)) { return false; }
	
	// Strip slashes from incomming data
	foreach ($data as $key => $item) {
		$post_data[$key] = stripslashes_deep($item);
	}

	// Filter our post data here
	$post_data = apply_filters('cfapi_filter_postdata', $post_data);
	
	$result = wp_insert_post($post_data);
	if (is_object($result) || !intval($result)) {
		$result = false;
	}
	return $result;
}

function cfapi_do_insert_postmeta($post_id = null, $name, $value) {
	if (is_null($post_id) || empty($name)) { return false; }
	// Make sure we have an int
	$post_id = (int) $post_id;
	
	// If we don't have a valid post_id, then get out
	if ($post_id == 0) { return false; }
	
	// Get rid of slashes
	$value = stripslashes_deep($value);

	// Set default autoload
	$autoload = true;
	$meta = apply_filters('cfapi_insert_postmeta', compact('name', 'value', 'autoload'), $post_id);
	
	// Return whatever wordpress gives us
	if (!add_post_meta($post_id, $meta['name'], $meta['value'], $meta['autoload'])) {
		// Try updating if our add failed
		return update_post_meta($post_id, $meta['name'], $meta['value']);
	}
	// We had a successful post_meta addition
	return true;
}

?>