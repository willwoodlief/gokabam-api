<?php

namespace gokabam_api;

/**
 * Has some basic user information
 * Class GKA_User
 * @package gokabam_api
 */
class GKA_User {
	/**
	 * A unique string that helps id the user's actions in the rest of the classes here
	 * the string starts with user_
	 * @var string $user_id
	 */
	public $user_id = '';

	/**.
	 * @var string $user_name Describes the user, not the login name but the descriptive name
	 */
	public $user_name = '';

	/**
	 * @var string $user_email  how to contact the user
	 */
	public $user_email = '';

	/**
	 * @var integer timestamp since the user started in the system
	 */
	public $ts_since = 0;
}


/**
 * Not meant for human or client consumption, an intermediate step for the server
 * Class GKA_Kid
 * @package gokabam_api
 */
class GKA_Kid {

	/**
	 * @var string $kid
	 * this always has a type, followed by an underscore then a code
	 * @example  version_r5Yu7
	 */
	public $kid = '';

	/**
	 * @var int $object_id
	 */

	public $object_id = 0;
	/**
	 * @var string $table
	 */
	public $table = '';

	/**
	 * @var integer $primary_id
	 */
	public $primary_id = 0;

	/**
	 * @var string $hint
	 * this is only non empty if there is a non alpha numeric symbol after the kid code
	 * that symbol, followed by anything else, is stored as a hint while this is being processed
	 * this is a cool feature, but not used at all
	 */
	public $hint = '';


}

/**
 * Class GKA_Touch
 * @package gokabam_api
 * this is the page load table
 */
class GKA_Touch
{

	/**
	 * @var GKA_Kid|string $version
	 * read only set by server to client
	 * the kid of the  version
	 */
	public $version = '';

	/**
	 * @var int $ts
	 * read only set by the app when returning information
	 * unix timestamp
	 */
	public $ts = 0;


	/**
	 * @var int $user_id
	 * the  user that did the changes
	 * wordpress user id
	 * read only set by the app when returning information
	 */
	public $user = 0;
}

class GKA_Root
{
	/**
	 * @var GKA_Kid|string|null $kid <p>
	 *  if not set when sent, it means this is something to be created new
	 *
	 *  if this is set by client a string in the format prefix_code which is verified
	 *  by the app to be the object id that exists for this type
	 *     there is an alternative version of the string, which if set, will pass through custom data
	 *      prefix_code_then_non_alphanumeric_and_any_characters
	 *      basically any non alpha numeric character after the code will be ignored and passed back through
	 *
	 *  During processing, the app converts this to a GKA_Kid, but its converted back to just the string code
	 *      before its sent back to the client
	 * </p>
	 */
	public $kid = '';

	/**
	 * @var string|bool $status <p>
	 *  This is either going to be
	 *      false (meaning it died before being processed)
	 *      true (everything is ok)
	 *      a message (there was an issue, its put here. A message here is always not good news)
	 * </p>
	 */
	public $status = false;
	/**
	 * @var bool $delete
	 */
	public $delete = false;

	/**
	 * @var GKA_Kid|string|null $parent - kid format
	 *
	 *  if this is set by client its a string in the format prefix_code which is verified
	 *  by the app to be the object id that exists for this type
	 *     there is an alternative version of the string, which if set, will pass through custom data
	 *      prefix_code_then_non_alphanumeric_and_any_characters
	 *      basically any non alpha numeric character after the code will be ignored and passed back through
	 *
	 *  During processing, the app converts this to a GKA_Kid, but its converted back to just the string code
	 *      before its sent back to the client
	 */
	public $parent = '';

	/**
	 * @var string|null $pass_through - kid format
	 * the data is not inspected or altered, it is reflected back to the sender
	 */
	public $pass_through = '';


	/**
	 * @var GKA_Word[] $words
	 * words and tags cannot have words
	 */
	public $words = [];


	/**
	 * @var GKA_Journal[] $journals
	 * journals cannot have journals
	 */
	public $journals = [];

	/**
	 * @var GKA_Tag[] $tags
	 * tags and words cannot  tags
	 */
	public $tags = [];


	/**
	 * @var string|GKA_Touch|null $initial_touch
	 * when this was first created
	 */
	public $initial_touch = '';

	/**
	 * @var string|GKA_Touch|null $recent_touch
	 * read only set by server to client
	 * the kid of the most recent version this object is tied to
	 */
	public $recent_touch = '';


	/**
	 * the checksum of what the data is now
	 * @var string|null  $md5_checksum
	 */
	public $md5_checksum = '';

	/**
	 * fills in all the dependencies of this object
	 * and sets up its properties for output later
	 * @return GKA_Root
	 * @throws SQLException
	 * @throws FillException
	 * @throws ApiParseException
	 */
	public function fill() {
		global $GokabamGoodies;
		if ($GokabamGoodies && ($filler_manager = $GokabamGoodies->get_filler_manager()) ) {
			return $filler_manager->fill($this);
		} else {
			throw new FillException("Filler manager not set up in gokabam goodies");
		}

	}
}



/**
* Class GKA_Word
* @package gokabam_api
* parent is anything but cannot be another word or tag
*/
class GKA_Word extends GKA_Root
{

	/**
	 * @var string $type name,title,blurb,description,overview,data
	 */
	public $type = '';

	/**
	 * @var string $language
	 * 2 letter language code, but if data this is not processed as text to read
	 * so data can be any character|number|symbol combo
	 */
	public $language = '';

	/**
	 * @var string $text
	 * cannot be empty
	 */
	public $text = '';
}



/**
* Class GKA_Version
* @package gokabam_api
* if parent set is an error
*/
class GKA_Version extends GKA_Root
{

	/**
	 * @var string $website_url
	 * if there is an associated website url about this
	 */
	public $website_url = '';


	/**
	 * @var int $post_id
	 * if a blog post is made about this on this wordpress
	 */
	public $post_id = 0;

	/**
	 * @var string $git_repo_url
	 * any associated git repo online
	 */
	public $git_repo_url = '';


	/**
	 * @var string $git_tag
	 */
	public $git_tag = false;

	/**
	 * @var string $git_commit_id
	 */
	public $git_commit_id = false;


	/**
	 * This is the internal name for the version, it can be annotated for title and description
	 * using words, it can also be tags, and notes attached to it
	 * but this is just the constant name
	 * @var string $text
	 */
	public $text = '';


}



/**
* Class GKA_Journal
* @package gokabam_api
* parent: [kid] is anything, not null, cannot be another journal,tag or word
*/
class GKA_Journal extends GKA_Root
{

	/**
	 * @var string $text
	 */
	public $text = '';
}


/**
 * Class GKA_Tag
 * @package gokabam_api
 * parent: [kid] is anything, not null, cannot be another tag or word
 *
 */
class GKA_Tag extends GKA_Root
{

	/**
	 * @var string $text, the key of the tag, what is seen
	 */
	public $text = '';

	/**
	 * @var string $value the hidden meaning or hint behind the tag
	 */
	public $value = '';
}



/**
 * Class GKA_Element
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_DataGroup
 * @see GKA_SQL_Part
 */
class GKA_Element extends GKA_Root
{
	/**
	 * @var string $text - the element name in the code and call
	 */
	public $text = '';


	/**
	 * @var string $type - string|integer|number|boolean|object|array
	 */
	public $type = '';


	/**
	 * @var string $format

	format integer:
		'int32',
		'int8',
		'int64',
		'use_pattern'

	 format number
	   'float',
	   'double',
		'use_pattern'

	 format string:
		'date',
		'date-time',
		'password',
		'byte',
		'binary',
		'email',
		'uri',
		'use_pattern'

	 if set with type array or object will be error

	 */
	public $format = '';

	/**
	* @var string|null $pattern
	 * used if format says use_pattern (for string,integer and number)
	 * error if this is set to something and the type is not one of those three
	 * error if set and the format is not use_pattern
	*/
	public $pattern = '';



	/**
	 * @var bool $is_nullable - default false
	 */
	public $is_nullable = false;


	/**
	 * @var bool $is_optional - default false
	 */
	public $is_optional = false;

	/**
	 * @var string[]|null $enum_values
	 * only with string,integer,number format : error otherwise
	 */
	public $enum_values = '';


	/**
	 * @var string|null $default_value
	 * if not set , the default is always null
	 * will throw error if set for array or object
	 */
	public $default_value = '';


	/**
	 * @var int|null $min
	 * error if set to non zero for object or boolean
	 * type string is min character length
	 * type integer and number the min value
	 * type array is the min number of elements
	 * default null
	 */
	public $min = 0;

	/**
	 * @var int|null $max
	 * error if set to non zero for object or boolean
	 * default null
	 * type string is max character length
	 * type integer and number the max value
	 * type array is the max number of elements
	 */
	public $max = 0;

	/**
	 * @var float|null $multiple
	 * error if set for anything other than integer|number
	 * is the pattern of allowed valued
	 */
	public $multiple = 0.0;

	/**
	 * @var float|null $precision - only if type is
	 * error if set for anything other than number
	 */
	public $precision = 0.0;

	/**
	 * @var int|null $rank -  shows display order
	 * if not set then display is random
	 */
	public $rank = 0;

	/**
	 * @var string|null $radio_group -
	 * if given a non null value, only one thing that share the same text in the radio group can be used at that level
	 *  works with top level elements or types array and object

	 */
	public $radio_group = '';

	/**
	 * @var GKA_Element[]|string[] $elements
	 * zero or more child elements
	 * if this has a kid set, then that kid element is copied
	 * error if set for anything but array or object
	 */
	public $elements = [];

}



/**
 * Class GKA_DataExample
 * @package gokabam_api
 * @see GKA_Everything
 */
class GKA_DataExample extends GKA_Root
{

	/**
	 * @var string $text, json  of the example
	 */
	public $text = '';

}



/**
 * Class GKA_DataGroup
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_Input
 * @see GKA_Output
 * @see GKA_Header
 * @see GKA_Use_Part

 */
class GKA_DataGroup extends GKA_Root
{

	/**
	 * @var GKA_DataExample[]|string[] $examples
	 * array of zero or more full json examples of this data type
	 */
	public $examples = [];

	/**
	 * @var GKA_Element[]|string[] $elements
	 * array of zero or more members
	 * if object pass in kid to copy if not already a member
	 * string is reference
	 */
	public $elements = [];

	/**
	 * @var string $type, must be empty or database_table|regular
	 * if empty will default to regular
	 */
	public $type = '';
}



/**
 * Class GKA_Header
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_Output
 * @see GKA_API
 * @see GKA_Family
 * @see GKA_API_Version
 */
class GKA_Header extends GKA_Root
{

	/**
	 * @var string $name - string header name
	 */
	public $name = '';

	/**
	 * @var string $value -
	 * the contents/value of the header can have regex groups with names that match the out data group
	 */
	public $value = '';


	/**
	 * @var GKA_DataGroup[]|string[] $data_groups
	 * defined here or kid
	 * elements must match the regex group names of the value
	 * zero or 1 element
	 */
	public $data_groups = [];
}



/**
 * Class GKA_API_Version
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_Use_Case
 * @see GKA_Header
 * @see GKA_Family
 */
class GKA_API_Version extends GKA_Root
{

	/**
	 * @var string $text - this is the internal name of the version
	 */
	public $text = '';

	/**
	 * @var GKA_Header[]|string[] $headers array of zero or more headers
	 */
	public $headers = [];

	/**
	 * @var GKA_Family[] $families array of zero or more families
	 */
	public $families = [];


	/**
	 * @var GKA_Use_Case[] $use_cases
	 * 0 or more
	 */
	public $use_cases = [];
}
		


/**
 * Class GKA_Family
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_API_Version
 * @see GKA_API
 * @see GKA_Header
 */
class GKA_Family extends GKA_Root
{

	/**
	 * @var string $text, this is the internal name of the family
	 */
	public $text = '';

	/**
	 * @var GKA_Header[]|string[] $headers array of zero or more headers
	 */
	public $headers = [];


	/**
	 * @var GKA_API[] $apis array of zero or more api
	 */
	public $apis = [];



}
		


/**
 * Class GKA_Input
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_API
 * @see GKA_DataGroup
 */
class GKA_Input extends GKA_Root
{

	/**
	 * @var string $origin - url,query,body,header
	 */
	public $origin = '';

	/**
	 * @var string|null $properties - based on origin
	properties, based on origin:

		url:  the properties is a regex that matches with at least part of the url
	        with the names of the regex groups matching elements in data group

	    query: (no properties), the data_group defines the keys expected (the query is the key values after the ?)

	    body: (no properties), the data_group defines the body expected

		header: is a regex matching part of the header with  names of the regex groups mapping to the properties
	               of the data group.
	 */
	public $properties = '';



	/**
	 * @var GKA_DataGroup[]|string $data_groups -
	 *  defined here or kid
	 *  elements must match the regex group names of the value
	 *  zero or one elements only
	 */
	public $data_groups = [];
}
		

/**
 * Class GKA_Output, parents are api
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_API
 */
class GKA_Output extends GKA_Root
{

	/**
	 * @var int $http_code - response code
	 */
	public $http_code = 0;

	/**
	 * @var GKA_DataGroup[]|string[] $data_groups
	 *   defined here or kid
	 *   zero or 1 groups
	 */
	public $data_groups = [];

	/**
	 * @var GKA_Header[]|string[] $headers array of zero or more headers
	 */
	public $headers = [];
}




/**
 * Class GKA_SQL_Part
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_Use_Part
 * @see GKA_Element
 *
 * the selects are an extra data group output elements
 * the inputs for each section is up to two database groups, and any input element
 */
class GKA_SQL_Part extends GKA_Root
{

	/**
	 * @var string $text
	 * describes what the part does,
	 * and adds details mentioning operations and constants
	 */
	public $text = '';


	/**
	 * @var string $sql_part_enum - select,from,joins,where,limit,offset,ordering
	 */
	public $sql_part_enum = '';

	/**
	 * @var string|GKA_Kid|null $db_element - KID format
	 * must be from any data group that is of database type
	 */
	public $db_element = '';

	/**
	 * @var string|GKA_Kid|null $reference_table_element - KID format
	 * must be from any data group that is of database type
	 */
	public $reference_db_element = '';

	/**
	 * @var string|GKA_Kid|null $outside_element - KID format
	 * must be from a data group which is part of the input of this use case part
	 */
	public $outside_element = '';


	/**
	 * @var integer $rank
	 * organizes the statements for display
	 */
	public $rank = 0;


}




/**
 * Class GKA_Use_Part_Connection
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_Use_Part
 * @see GKA_Use_Case
 */
class GKA_Use_Part_Connection extends GKA_Root
{

	/**
	 * @var string|GKA_Kid|null $parent_part
	 *  a reference to the start of the connection
	 *  this part must be in the same use case as the destination
	 */
	public $source_part = '';

	/**
	 * @var string|GKA_Kid|null $parent_part
	 *  a reference to the start of the connection
	 *  this part must be in the same use case as the source
	 */
	public $destination_part = '';

	/**
	 * @var int|null $rank
	 *   - optional ranking for display purposes
	 */
	public $rank = 0;

}



/**
 * Class GKA_Use_Part
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_Use_Part_Connection
 * @see GKA_Use_Case
 * @see GKA_API
 * @see GKA_DataGroup
 * @see GKA_SQL_Part
 */
class GKA_Use_Part extends GKA_Root
{


	/**
	 * @var integer $ref_id -
	 * any number supplied to tag this, needs to be unique for the use case
	 */
	public $ref_id = 0;

	/**
	 * @var string|GKA_Kid|null $in_api -
	 * if the input is an api
	 *  this is a reference only
	 */
	public $in_api = '';


	/**
	 * @var GKA_DataGroup[]|GKA_Kid[]|string[] $in_data_groups
	 * 0 or 1 for the in data group
	 */
	public $in_data_groups = [];


	/**
	 * @var GKA_DataGroup[]GKA_Kid[]|string[] $out_data_group
	 * 0 or 1 for the out data group
	 */
	public $out_data_groups = [];



	/**
	 * @var GKA_SQL_Part[]|string[]  $sql_parts
	 * 0 or more sql parts
	 * only if this is child of a use case for an api
	 */
	public $sql_parts = [];


	/**
	 * @var string[]|GKA_Kid[] $source_connections
	 * 0 or more connections between the parts, this shows the parts
	 * which originate from here
	 * This is read only, set by the server
	 */
	public $source_connections = [];
}




/**
 * Class GKA_Use_Case
 * @package gokabam_api
 * @see GKA_Everything
 * @see GKA_Use_Part
 * @see GKA_Use_Part_Connection
 * @see GKA_API
 * @see GKA_API_Version
 */
class GKA_Use_Case extends GKA_Root
{

	/**
	 * @var GKA_Use_Part[]|string[] $use_parts -
	 * zero or more use case parts
	 */
	public $use_parts = [];


	/**
	 * @var GKA_Use_Part_Connection[]
	 * zero or more connections between the parts
	 */
	public $connections = [];


}


/**
 * Class GKA_API
 * @package gokabam_api
 * @see GKA_Header
 * @see GKA_Input
 * @see GKA_Output
 * @see GKA_Use_Case
 * @see GKA_Everything
 *  - the http calls that make up the api
 *  they have different inputs, outputs, headers and use cases
 */
class GKA_API extends GKA_Root
{
	/**
	 * @var string $text - this is the name of the api call
	 */
	public $text = '';

	/**
	 * @var string $method
	 * must be only get|put|post|delete|options|head|patch|trace
	 * default is 'get'
	 */
	public $method = '';

	/**
	 * @var GKA_Input[]|string[] $inputs array of zero or more inputs
	 */
	public $inputs = [];

	/**
	 * @var GKA_Output[]|string[] $outputs array of zero or more outputs
	 */
	public $outputs = [];
	/**
	 * @var GKA_Header[]|string[] $headers array of zero or more headers
	 */
	public $headers = [];

	/**
	 * @var GKA_Use_Case[]|string[] $use_cases
	 */
	public $use_cases = [];
}



/**
 * Class GKA_ServerData
 * @package gokabam_api
 * @see GKA_Everything
 */
class GKA_ServerData {
	/**
	 * @var string $server_time - human readable time
	 */
	public $server_time = '';

	/**
	 * @var string $server_timezone - human readable timezone
	 */
	public $server_timezone = '';

	/**
	 * @var int $server_timestamp - unix timestamp
	 */
	public $server_timestamp = 0;

	/**
	 * WP nonce
	 * @var string|null $ajax_nonce
	 */
	public $ajax_nonce = '';
}
		 
/*


     *      string      'trace_as_string' =>
     *      array|null  'chained' => an array of exceptions chained to this one, with the same info as above
		  */
class GKA_Exception_Info {
	/**
	 * @var string  $hostname ,  the name of the machine the exception occurred on
	 */
	public $hostname = '';

	/**
	 * @var string $machine_id , mac address or similar id
	 */
	public $machine_id = '';

	/**
	 * @var string $caller_ip_address ,the ip address of the browser caller, else will be null
	 */
	public $caller_ip_address = '';

	/**
	 * @var string $branch, the git branch
	 */
	public $branch = '';

	/**
	 * @var string $last_commit_hash, the sha1 hash of the last commit made on the code throwing the exception
	 */
	public $last_commit_hash = '';

	/**
	 * @var boolean $is_commit_modified, true if the code has been changed since the last commit
	 */
	public $is_commit_modified = '';

	/**
	 * @var string[]|null $argv, array of arguments if this is called from the command line
	 */
	public $argv = [];

	/**
	 * @var string $request_method, usually post or get
	 */
	public $request_method = '';

	/**
	 * @var array|null $post_super,  the post vars, if set
	 */
	public $post_super = [];

	/**
	 * @var array|null $get_super,  the get vars, if set
	 */
	public $get_super = [];

	/**
	 * @var array|null $cookies_super,  the cookies
	 */
	public $cookies_super = [];

	/**
	 * @var array|null $server_super,  the server info
	 */
	public $server_super = [];

	/**
	 * @var string $message, what is this all about
	 */
	public $message = '';

	/**
	 * @var string $class_of_exception, the name of the exception class
	 */
	public $class_of_exception = '';

	/**
	 * @var string $code_of_exception , exception code
	 */
	public $code_of_exception = '';

	/**
	 * @var string $file_name, the file the exception occurred in
	 */
	public $file_name = '';

	/**
	 * @var string  $line, line number in the file of the exception
	 */
	public $line = '';


	/**
	 * @var string $class, if the exception occurred inside a class, it will be listed here
	 */
	public $class = '';

	/**
	 * @var string $function_name, if the exception occurred inside a function, it will be listed here
	 */
	public $function_name = '';

	/**
	 * @var string $trace_as_string, the trace in an easier to read string format
	 */
	public $trace_as_string = '';

	/**
	 * @var GKA_Exception_Info[] $chained, any chained exceptions
	 */
	public $chained = [];

}

/**
 * Class GKA_Everything
 * @package gokabam_api
 * @see GKA_Element
 * @see GKA_DataGroup
 * @see GKA_DataExample
 * @see GKA_Header
 * @see GKA_Output
 * @see GKA_Input
 * @see GKA_API
 * @see GKA_Family
 * @see GKA_API_Version
 * @see GKA_Version
 * @see GKA_SQL_Part
 * @see GKA_Use_Part_Connection
 * @see GKA_Use_Part
 * @see GKA_Use_Case
 * @see GKA_Word
 * @see GKA_Tag
 * @see GKA_Journal
 * @see GKA_ServerData
 * @see GKA_Exception_Info
 */
class GKA_Everything
{
	/**
	 * @var string $api_action - update|save|report|get|init
	 */
	public $api_action = '';

	/**
	 * @var int|null $begin_timestamp - needed for report, and filled if for get
	 */
	public $begin_timestamp = 0;

	/**
	 * @var int|null $begin_timestamp - needed for report, and filled if for get
	 */
	public $end_timestamp = 0;


	/**
	 * @var string $message - talks about the overall operation. Will be success or an error message
	 */
	public $message = '';

	/**
	 * @var bool $is_valid - if no large errors then is true, otherwise is false and message will have error message
	 */
	public $is_valid = true;

	/**
	 * @var GKA_Exception_Info|null - if error then this will contain exception information
	 */
	public $exception_info = null;


	/**
	 * @var GKA_Word[] $words
	 */
	public $words = [];

	/**
	 * @var GKA_Version[] $versions
	 */
	public $versions = [];

	/**
	 * @var GKA_Journal[] $journals
	 */
	public $journals = [];

	/**
	 * @var GKA_Tag[] $tags
	 */
	public $tags = [];

	/**
	 * @var GKA_API_Version[] $api_versions
	 */
	public $api_versions = [];

	/**
	 * @var GKA_Family[]|string[] $families array of zero or more families
	 */
	public $families = [];


	/**
	 * @var GKA_Header[]|string[] $headers array of zero or more headers
	 */
	public $headers = [];



	/**
	 * @var GKA_API[]|string[] $apis array of zero or more api
	 */
	public $apis = [];


	/**
	 * @var GKA_Input[]|string[] $inputs array of zero or more inputs
	 */
	public $inputs = [];

	/**
	 * @var GKA_Output[]|string[] $outputs array of zero or more outputs
	 */
	public $outputs = [];


	/**
	 * @var GKA_Use_Case[]|string[] $use_cases
	 */
	public $use_cases = [];


	/**
	 * @var GKA_Use_Part[]|string[] $use_parts
	 */
	public $use_parts = [];


	/**
	 * @var GKA_Use_Part_Connection[]|string[] $use_part_connections
	 */
	public $use_part_connections = [];

	/**
	 * @var GKA_SQL_Part[]|string[]  $sql_parts
	 */
	public $sql_parts = [];


	/**
	 * data groups which are type regular are here
	 * @var GKA_DataGroup[]|string[] $data_groups
	 */
	public $data_groups = [];


	/**
	 * data groups which are type database_table are here, as they are not a dependency
	 * @var GKA_DataGroup[]|string[] $table_groups
	 */
	public $table_groups = [];


	/**
	 * @var GKA_DataExample[]|string[] $examples
	 */
	public $examples = [];


	/**
	 * @var GKA_Element[]|string[] $elements
	 */
	public $elements = [];

	/**
	 * @var mixed[] $library  if kids are used instead of classes in the class arrays, then this is a hash of them here
	 */
	public $library = [];

	/**
	 * read only
	 * @var string $pass_through_data - anything the caller wants to put here is passed back without looking at it
	 */
	public $pass_through_data = '';


	/**
	 * readonly
	 * @var GKA_ServerData|null $server
	 */
	public $server = null;

	/**
	 * read only
	 * objects that were deleted in the time range provided, if not time range, then not filled in
	 * @var GKA_Kid[]|string[]
	 */
	public $deleted_kids = [];


	/**
	 * read only
	 * a list of the users
	 * users are also put in the library
	 * @var GKA_User[] $users
	 */
	public $users = [];
}










