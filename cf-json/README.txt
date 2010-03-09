# CF JSON

CF JSON is a function set designed to be included as an external library, not as a standalone plugin. This library provides 2 functions for safely providing JSON encoding and decoding functionality accross versions of php that may or may not have native JSON support.

## Functions

- `cfct_json_encode`: encode an array of data as JSON.
	- param `$data`: pass in an array or object to be JSON encoded
- `cfct_json_decode`: decode an json string in to an array or object
	- param `$json`: JSON string to be decoded
	- param `$array`: boolean, true to return an array, false to return an object. Included to retain parity with the native PHP `json_decode` function.