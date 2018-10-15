<?php
//todo place to access db is currently in top level groups is this ok?
namespace gokabam_api;

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
	 * @var GKA_Kid|string|null $kid
	   @see GKA_Root
	 */

	public $kid = '';
	/**
	 * @var string $version
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
	public $user_id = 0;
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
	 * @var string|null $enum_values
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
	 * @var int $min
	 * error if set to non zero for object or boolean
	 * type string is min character length
	 * type integer and number the min value
	 * type array is the min number of elements
	 * default 0
	 */
	public $min = 0;

	/**
	 * @var int $max
	 * error if set to non zero for object or boolean
	 * default 0
	 * type string is max character length
	 * type integer and number the max value
	 * type array is the max number of elements
	 */
	public $max = 0;

	/**
	 * @var float $multiple
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
		
/* input
			parent: api (its part of the api definition above)
			origin: url,query,body,header
			properties, based on origin:
				url:
					pattern: regex with the names of the regex groups matching elements in data group, if not match then error
				query:
					(no properties), the data_group defines the keys expected
				body:
					(no properties), the data_group defines the body expected
				header:
					name: the name of the header
					value: text or regex with regex groups matching the data group, or error
			data_group: can be any, unless url or header is selected, then must match them (defined here or by kid)
			kid: null or this is an update and not an insert, if update only non null values changed */

		/**
		 * Class GKA_Input
		 * @package gokabam_api
		 * @see GKA_Everything
		 */
		class GKA_Input extends GKA_Root
		{

			/**
			 * @var string $origin - url,query,body,header
			 */
			public $origin = '';
		
			/**
			 * @var string|null $properties - based on origin 
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
		
/* output
			parent: api (its part of the api definition above)
			http_code: a number
			data_group: can be any (defined here or by kid)
			headers: null or array of headers (defined here)
			kid: null or this is an update and not an insert, if update only non null values changed */

		/**
		 * Class GKA_Output
		 * @package gokabam_api
		 * @see GKA_Everything
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
			public $data_groups = null;
			
			/**
			 * @var GKA_Header[]|string[] $headers array of zero or more headers
			 */
			public $headers = [];
		}


/*
sql_part:
			sql_part_enum: select,from,joins,where,limit,offset,ordering
			table_element: kid of element from any db table group
			reference_element: kid of element from any db table group
			outside_element: reference id, from any input group in this use case
			text: describes what the part does, and adds details mentioning operations and constants
 */

		/**
		 * Class GKA_SQL_Part
		 * @package gokabam_api
		 * @see GKA_Everything
		 * @see GKA_Use_Part
		 */
		class GKA_SQL_Part extends GKA_Root
		{

			/**
			 * @var string $sql_part_enum - select,from,joins,where,limit,offset,ordering
			 */
			public $sql_part_enum = '';

			/**
			 * @var string $table_element - KID format
			 */
			public $table_element = '';

			/**
			 * @var string|null $reference_element - KID format
			 * only kid and not definition here
			 */
			public $reference_element = '';

			/**
			 * @var string|null $outside_element - KID format
			 * only kid and not definition here
			 */
			public $outside_element = '';

			/**
			 * @var string|null -  describes what the part does, and adds details mentioning operations and constants
			 */
			public $text = null;
		}

/*
		use_case_part_connection
				parent_use_case_part_id
				child_use_case_part_id
				rank
		*/

		/**
		 * Class GKA_Use_Part_Connection
		 * @package gokabam_api
		 * @see GKA_Everything
		 */
class GKA_Use_Part_Connection extends GKA_Root
{

	/**
	 * @var GKA_Use_Part|string $parent_part - kid format OR use ref id for new things that have no kid yet
	 */
	public $parent_part = '';

	/**
	 * @var GKA_Use_Part|string $child_part - kid format OR use ref id for new things that have no kid yet
	 */
	public $child_part = '';

	/**
	 * @var int|null $rank - optional ranking
	 */
	public $rank = '';

}



/*

	use_case_part
				in_data_group:
				out_data_group:
				ref_id: any number supplied
				children: array of ref_ids
				sql_parts:

		 */

		/**
		 * Class GKA_Use_Part
		 * @package gokabam_api
		 * @see GKA_Everything
		 * @see GKA_Use_Part_Connection
		 * @see GKA_Use_Case
		 */
		class GKA_Use_Part extends GKA_Root
		{


			/**
			 * @var GKA_DataGroup|string|null $in_data_group -  null or  defined here or any KID inside parent
			 */
			public $in_data_group = null;


			/**
			 * @var GKA_API|null $in_api -  if the input is an api
			 */
			public $in_api = null;

			/**
			 * @var GKA_DataGroup|string|null $out_data_group -  null or  defined here or any KID inside parent
			 */
			public $out_data_group = null;

			/**
			 * @var integer $ref_id - any number supplied to tag this, needs to be unique for the use case
			 */
			public $ref_id = '';


			/**
			 * @var GKA_SQL_Part[]|string[]  $sql_parts
			 * 0 or more sql parts
			 * only if this is child of a use case for an api
			 */
			public $sql_parts = [];

			/**
			 * @var string|null -  describes what the part does, and adds details mentioning operations and constants
			 */
			public $text = null;


			/**
			 * @var GKA_Use_Part_Connection[]|string[] - 0 or more connections between the parts
			 */
			public $connections = [];
		}


/*

use_case
			parent: version kid
			parts: array of parts defined here
				in_data_group: null or defined here or kid of any inside parent
				out_data_group: null or defined here or kid of any inside parent
				in_api_id: only if in_data_group is not defined
				ref_id: any number supplied
				children: array of ref_ids
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert

 */

		/**
		 * Class GKA_Use_Case
		 * @package gokabam_api
		 * @see GKA_Everything
		 */
		class GKA_Use_Case extends GKA_Root
		{

			/**
			 * @var GKA_Use_Part[]|string[] $use_parts -
			 * zero or more use case parts
			 */
			public $use_parts = [];

			/**
			 * @var GKA_Use_Part_Connection[] - 0 or more connections between the parts
			 */
			public $connections = [];

		}


/**
 * Class GKA_API
 * @package gokabam_api
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





/*
 * Some server data
 * */

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
}
		 

/*
 action: update|save|report|get
	update: will create or update based on what is here
	save: will take the information here, and save it under the name
	get: will retrieve a named save, it will put the time it was saved under the begin timestamp
	report: will list the changes, insertions and deletions for between the two timestamps
begin_timestamp: null or timestamp
end_timestamp: null or timestamp
save_name: null or name
message: an overview of the entire operation
is_valid:
exception_info:
words: array 0 or more

versions:

journals:
tags:
api_versions
*/



/**
 * Class GKA_Everything
 * @package gokabam_api
 *
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
	 * @var string|null $save_name - if action is save then need a save name, if get will need it also
	 */
	public $save_name = '';

	/**
	 * @var string $message - talks about the overall operation. Will be success or an error message
	 */
	public $message = '';

	/**
	 * @var bool $is_valid - if no large errors then is true, otherwise is false and message will have error message
	 */
	public $is_valid = true;

	/**
	 * @var null|object - if error then this will contain exception information
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
	 * @var GKA_SQL_Part[]|string[]  $sql_parts
	 */
	public $sql_parts = [];


	/**
	 * @var GKA_DataGroup[]|string[] $data_groups
	 */
	public $data_groups = null;


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
	 * @var string $pass_through_data - anything the caller wants to put here is passed back without looking at it
	 */
	public $pass_through_data = '';

	/**
	 * WP Action
	 * @var string $action
	 */
	public $action = '';

	/**
	 * WP nonce
	 * @var string|null $_ajax_nonce
	 */
	public $_ajax_nonce = '';

	/**
	 * @var GKA_ServerData|null $server
	 */
	public $server = null;
}










