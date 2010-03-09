# CF Post API plugin

## Description

Creates a post from an API Key-authenticated request.  Can accept all standard post fields (`post_title` and `post_content` are the only required fields), as well as postmeta information.

## Overview and Use

This plugin functions as a flexible way whereby posts or pages can be created and/or updated by posting data (via HTTP POST) to an endpoint (main blog's url).  There is basic authentication, via an API Key (currently statically set) and a specific `$_POST` variable that it listens for, before any actions are taken.

### Requirements

- WordPress Plugins Activated 
	- `CF Post API`
- HTTP Client capable of sending a POST request to a URL endpoint
- POST data
	- `cfapi_action` = `create_post`
	- post information (see **PHP data architecture** for required fields)

### Creating a post

Each POST request to the endpoint is for the creating or updating of a single WordPress post.

1. Create your post in whichever format is determined.
1. Use an intermediary program to take the post information that was created, and prepare your data to be posted to the blog's url.  
	- Examples below show how the data is expected to be received at the endpoint.  See: **PHP data architecture**, and **cURL data params** sections for examples of what post information is required, and which is optional. 
1. Ensure the `CF Post API` plugin is activated.
1. Using an HTTP client, anything from cURL to JavaScript that can send POST data to send information to the endpoint, send the request to the endpoint.

### Updating a post

Posts are updated in the same manner as creating a post.  The API checks to see if a post exists by title, and if it does the post information is used to overwrite the existing post content.  

**Note:** Postmeta values are non-removable with the current version of the API.  You can add to, update, or empty their values, but cannot remove the postmeta record once it's been created.

---

## Examples

	== Required Fields ==
	{API Action: string} = create_post
	{API Key: string} = fbd05efe098d8ecc0a7d1a6b6316087d
	{API Endpoint: string} = URL to WordPress instance (e.g., http://example.com/index.php)
	{Post Title: string} =  Exact title of page to create/update in WordPress
	{Post Content: string} = Exact content of page to create/update in WordPress
	
	== Optional Fields ==
	{Post Status: string} = Either "publish" or "draft"
	{Post Category: int} = Integer of WordPress Category
	{Post Date: string} = string representation of full date (e.g., "2008-02-25 16:30:02")
	{Post Author: int} = Integer ID of user that should be the author
	{Meta Key: string} = Meta key name
	{Meta Value: int/string} = Value of previous Meta Key
	{Meta Autoload: bool} = Should WordPress auto-load this meta row each page load, or load as needed (e.g., true/false)

**Create Standard post**

	curl -d cfapi_action={API Action} -d cfapi_data[api_key]={API Key} -d cfapi_data[post_title]={Post Title} -d cfapi_data[post_content]={Post Content} -d cfapi_data[post_status]={Post Status} {API Endpoint}

**Create Post with postmeta**

	curl -d cfapi_action={API Action} -d cfapi_data[api_key]={API Key} -d cfapi_data[post_title]={Post Title} -d cfapi_data[post_content]={Post Content} -d cfapi_data[post_status]={Post Status} -d cfapi_data[postmeta][1][key]={Meta Key} -d cfapi_data[postmeta][1][value]={Meta Value} -d cfapi_data[postmeta][1][autoload]={Meta Autoload} -d cfapi_data[post_category][]={Post Category} -d cfapi_data[post_date]={Post Date} {API Endpoint}


**cURL data params**

	-d cfapi_action={API Action} (required)
	-d cfapi_data[api_key]={API Key} (required)
	-d cfapi_data[post_title]={Post Title}  (required)
	-d cfapi_data[post_content]={Post Content} (required)
	-d cfapi_data[post_status]={Post Status} (optional)
	-d cfapi_data[post_category][]={Post Category} (optional)
	-d cfapi_data[post_date]={Post Date} (optional)
	-d cfapi_data[post_author]={Post Author} (optional)
	-d cfapi_data[postmeta][1][key]={Meta Key} (optional)
	-d cfapi_data[postmeta][1][value]={Meta Value} (optional)
	-d cfapi_data[postmeta][1][autoload]={Meta Autoload} (optional)

**PHP data architecture**

	cfapi_data = array(
		'api_key',  // required
		'post_title', // required
		'post_content', // required
		'postmeta' = array(
			array(
				'key',
				'value,
				'autoload'
			),
		),
	);

--- 

## Hooks

**Filters**

	apply_filters('cfapi_key', 'fbd05efe098d8ecc0a7d1a6b6316087d') - Filtering of the API Key to validate against
	apply_filters('cfapi_filter_postdata', $data) - Filter post data directly before wp_insert_post
	apply_filters('cfapi_insert_postmeta', $meta, $post_id) - Filter postmeta directly before adding or updating of postmeta

**Actions**

	do_action('cfapi_pre_process') -- After validation, but before post processing (maybe turn off KSES here?)
	do_action('cfapi_post_process', $results) -- After post and postmeta creation attempt

