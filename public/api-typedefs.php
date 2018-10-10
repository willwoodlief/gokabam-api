<?php
namespace gokabam_api;

/*
word
			parent: [kid] is anything,not null, cannot be another word or tag
			type:   name,title,blurb,description,overview,data
			language: 2 letter language code, but if data this is not processed as text to read
			text: non empty string
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert
			delete: null or missing or true. If true, then kid must be entered also */

			class GKA_Word
			{
				/**
				 * @var string|null $kid
				 */
				public $kid = '';

				/**
				 * @var string|null $status
				 */
				public $status = '';
				/**
				 * @var bool $delete
				 */
				public $delete = false;

				/**
				 * @var string|null $parent - kid format
				 */
				public $parent = '';

				/**
				 * @var string $type name,title,blurb,description,overview,data
				 */
				public $type = '';

				/**
				 * @var string $language 2 letter language code, but if data this is not processed as text to read
				 */
				public $language = '';

				/**
				 * @var string $text
				 */
				public $text = '';
			}

/*version
			parent: null
			text: non empty string, this is the internal name of the version
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert
			delete: null or missing or true. If true, then kid must be entered also */

			class GKA_Version
			{
				/**
				 * @var string|null $kid - kid format
				 */
				public $kid = '';

				/**
				 * @var string|null $status
				 */
				public $status = '';
				/**
				 * @var bool $delete
				 */
				public $delete = false;

				/**
				 * @var string|null $parent - kid format
				 */
				public $parent = '';

				/**
				 * @var string $text
				 */
				public $text = '';
			}

/*journal
			parent: [kid] is anything, not null, cannot be another journal,tag or word
			text: non empty string
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert
			delete: null or missing or true. If true, then kid must be entered also */

			class GKA_Journal
			{
				/**
				 * @var string|null $kid - kid format
				 */
				public $kid = '';


				/**
				 * @var string|null $status
				 */
				public $status = '';
				/**
				 * @var bool $delete
				 */
				public $delete = false;

				/**
				 * @var string|null $parent - kid format
				 */
				public $parent = '';

				/**
				 * @var string $text
				 */
				public $text = '';
			}
/* tag
			parent: [kid] is anything, not null, cannot be another tag or word
			text: non empty string
			value: null or non empty string
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert
			delete: null or missing or true. If true, then kid must be entered also */
			class GKA_Tag
			{
				/**
				 * @var string|null $kid - kid format
				 */
				public $kid = '';


				/**
				 * @var string|null $status
				 */
				public $status = '';
				/**
				 * @var bool $delete
				 */
				public $delete = false;

				/**
				 * @var string|null $parent - kid format
				 */
				public $parent = '';
				/**
				 * @var string $text, the key of the tag, what is seen
				 */
				public $text = '';

				/**
				 * @var string $value the hidden meaning or hint behind the tag
				 */
				public $value = '';
			}

/* data_element
	kid:  this is supplied after the server creates it
	status: (put here on return ) also kid will be filled out if this is an insert
	note: these are not used to hold an actual value, just define type and what kind of values they could hold
	type: string|integer|number|boolean|object|array
	text: the element name
	is_nullable: if this can be not filled in , optional
	rank: order of display, can be null
	properties: based on the type, listed below
		string:
			pattern: null or string
			enum_values: null or array of allowed values
			format: null or date|date-time|password|byte|binary|email|uri|use_pattern
			length: the maximum length
		integer
			min: null or minimum amount
			max: null or maximum amount
			multiple: must be multiples of this number
			format:  int32|int8|int64 default int32
		number:
			min: null or minimum amount
			max: null or maximum amount
			precision: significant digits
			format:float|double, default double
		boolean:
			no options
		object:
			array of data elements, defined here as a data element
				additional properties for all object members
					radio_group: if given a non null value, only one thing in this group can be used at the same time

		array:
			min: the minimum number of elements
			max: the maximum number of elements
			array of data element types, defined here as a data element
				additional properties for all data elements here:

					radio_group: if given a non null value, only one thing in this group can be used in the array */


		class GKA_Element
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';


			/**
			 * @var string|null $status
			 */
			public $status = '';

			/**
			 * @var bool $delete
			 */
			public $delete = false;

			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';

			/**
			 * @var string $text - the element name
			 */
			public $text = '';

			/**
			 * @var bool $is_nullable - if this can be not filled in , optional
			 */
			public $is_nullable = false;

			/**
			 * @var string $type - string|integer|number|boolean|object|array
			 */
			public $type = '';

			/**
			* @var string|null $enum_values
			*/
			public $pattern = '';

			/**
			 * @var string|null $enum_values
			 */
			public $enum_values = '';

			/**
			 * @var string $format
			 */
			public $format = '';

			/**
			 * @var int $length
			 */
			public $length = 0;

			/**
			 * @var int $min
			 */
			public $min = 0;

			/**
			 * @var int $max
			 */
			public $max = 0;

			/**
			 * @var integer $multiple
			 */
			public $multiple = 0;

			/**
			 * @var float|null $precision - only if type is
			 */
			public $precision = 0.0;

			/**
			 * @var int|null $rank -  shows display order
			 */
			public $rank = 0;

			/**
			 * @var string|null $radio_group -  if given a non null value, only one thing in this group can be used in object or array
			 */
			public $radio_group = '';

			/**
			 * @var GKA_Element[]|string[] $data_elements - if type is array or object
			 */
			public $data_elements = [];

		}
/*
 * data_examples
 *      holds example for a data group, can be tagged and annotated
 */

		class GKA_DataExample
		{
			/**
			 * @var string|null $kid
			 */
			public $kid = '';


			/**
			 * @var string|null $status
			 */
			public $status = '';
			/**
			 * @var bool $delete
			 */
			public $delete = false;

			/**
			 * @var string|null $parent
			 */
			public $parent = '';
			/**
			 * @var string $example, json  of the example
			 */
			public $example = '';

		}

/*
	data_group
			parent: null, database, put in definitions below, or [kid] of: input,output,header
				note: null parent means will be used as reference and actually copied when part of a definition of something else
			examples: null or array of json values that must match the members in a group
			* if the examples do not match the members, or new members do not match the examples, an error is thrown
			members: null or array of data_elements (defined here)
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert
			delete: null or missing or true. If true, then kid must be entered also
		 */

		class GKA_DataGroup
		{
			/**
			 * @var string|null $kid
			 */
			public $kid = '';


			/**
			 * @var string|null $status
			 */
			public $status = '';
			/**
			 * @var bool $delete
			 */
			public $delete = false;

			/**
			 * @var string|null $parent
			 */
			public $parent = '';
			/**
			 * @var GKA_DataExample[]|string[] $examples, array of zero or more full json examples of this data type
			 */
			public $examples = [];

			/**
			 * @var GKA_Element[]|string[] $members array of zero or more members
			 */
			public $members = [];
		}

/* header
			parent: api,family,version,output (its part of the definitions above)
			name: string header name
			value: the contents/value of the header can have regex groups with names that match the out data group
			data_group: null or elements must match the regex group names of the value
			kid: null or this is an update and not an insert, if update only non null values changed */

		class GKA_Header
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';


			/**
			 * @var string|null $status
			 */
			public $status = '';
			/**
			 * @var bool $delete
			 */
			public $delete = false;

			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';
			/**
			 * @var string $name - string header name
			 */
			public $name = '';

			/**
			 * @var string $value - the contents/value of the header can have regex groups with names that match the out data group
			 */
			public $value = '';

			/**
			 * @var GKA_DataGroup|string|null $data_group -  null or defined here or kid,elements must match the regex group names of the value
			 */
			public $data_group = null;
		}

/*
api_version
			parent: null
			text: non empty string, this is the internal name of the version
			headers: null or an array of headers (defined here ) (see definition under api)
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert
			delete: null or missing or true. If true, then kid must be entered also */

		class GKA_API_Version
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';
		
		
			/**
			 * @var string|null $status
			 */
			public $status = '';
			/**
			 * @var bool $delete
			 */
			public $delete = false;
		
			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';
			/**
			 * @var string $text - this is the internal name of the version
			 */
			public $text = '';
		
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
		 api_family
			parent: api_version
			text: non empty string, this is the internal name of the family
			headers: null or an array of headers (defined here ) (see definition under api)
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert
			delete: null or missing or true. If true, then kid must be entered also */
		
		class GKA_Family
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';
		
		
			/**
			 * @var string|null $status
			 */
			public $status = '';
			/**
			 * @var bool $delete
			 */
			public $delete = false;
		
			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';
			/**
			 * @var string $text, this is the internal name of the family
			 */
			public $text = '';
		
			/**
			 * @var GKA_Header[]|string[] $headers array of zero or more headers
			 */
			public $headers = [];



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

		class GKA_Input
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';
		
		
			/**
			 * @var string|null $status
			 */
			public $status = '';
			/**
			 * @var bool $delete
			 */
			public $delete = false;
		
			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';
			/**
			 * @var string $origin - url,query,body,header
			 */
			public $origin = '';
		
			/**
			 * @var string|null $properties - based on origin 
			 */
			public $properties = '';
		
			/**
			 * @var GKA_DataGroup|string|null $data_group -  null or defined here or kid,elements must match the regex group names of the value
			 */
			public $data_group = null;
		}
		
/* output
			parent: api (its part of the api definition above)
			http_code: a number
			data_group: can be any (defined here or by kid)
			headers: null or array of headers (defined here)
			kid: null or this is an update and not an insert, if update only non null values changed */

		class GKA_Output
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';
		
		
			/**
			 * @var string|null $status
			 */
			public $status = '';
			/**
			 * @var bool $delete
			 */
			public $delete = false;
		
			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';
			/**
			 * @var int $http_code - response code
			 */
			public $http_code = 0;

			/**
			 * @var GKA_DataGroup|string|null $data_group-  null or defined here or kid
			 */
			public $data_group = null;
			
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
		class GKA_SQL_Part
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';

			/**
			 * @var string|null $status
			 */
			public $status = '';

			/**
			 * @var bool $delete
			 */
			public $delete = false;

			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';

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
			 */
			public $reference_element = '';

			/**
			 * @var string|null $outside_element - KID format
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
class GKA_Use_Part_Connection
{
	/**
	 * @var string|null $kid - kid format
	 */
	public $kid = '';

	/**
	 * @var string|null $status
	 */
	public $status = '';

	/**
	 * @var bool $delete
	 */
	public $delete = false;

	/**
	 * @var string $parent_part - kid format OR use ref id for new things that have no kid yet
	 */
	public $parent_part = '';

	/**
	 * @var string $child_part - kid format OR use ref id for new things that have no kid yet
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
		class GKA_Use_Part
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';

			/**
			 * @var string|null $status
			 */
			public $status = '';

			/**
			 * @var bool $delete
			 */
			public $delete = false;

			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';

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
			 * @var GKA_SQL_Part[]|string[]  $sql_parts 0 or more sql parts, only if this is child of a use case for an api
			 */
			public $sql_parts = '';

			/**
			 * @var string|null -  describes what the part does, and adds details mentioning operations and constants
			 */
			public $text = null;
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
		class GKA_Use_Case
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';

			/**
			 * @var string|null $status
			 */
			public $status = '';

			/**
			 * @var bool $delete
			 */
			public $delete = false;

			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';

			/**
			 * @var GKA_Use_Part[]|string[] $use_parts -  zero or more use case parts
			 */
			public $use_parts = [];

			/**
			 * @var GKA_Use_Part_Connection[] - 0 or more connections between the parts
			 */
			public $connections = [];

		}

/*
		 api
			parent: api_family
			text: non empty string, this is the way to call the api (the function name)
			kid: null or this is an update and not an insert, if update only non null values changed
			status: (put here on return ) also kid will be filled out if this is an insert
			delete: null or missing or true. If true, then kid must be entered also
			inputs
			outputs
			headers
		 */

		class GKA_API
		{
			/**
			 * @var string|null $kid - kid format
			 */
			public $kid = '';
		
		
			/**
			 * @var string|null $status
			 */
			public $status = '';
			/**
			 * @var bool $delete
			 */
			public $delete = false;
		
			/**
			 * @var string|null $parent - kid format
			 */
			public $parent = '';
			/**
			 * @var string $text - this is the name of the api call
			 */
			public $text = '';

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
 * @todo http://php.net/manual/en/jsonserializable.jsonserialize.php
 */
class GKA_Everything
{
	/**
	 * @var string $action - update|save|report|get
	 */
	public $action = '';

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
	 * @var string $message - talks about the overall operation
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
	 * @var mixed[] $library  if kids are used instead of classes in the class arrays, then this is a hash of them here
	 */
	public $library = [];

	/**
	 * @var string $pass_through_data - anything the caller wants to put here is passed back without looking at it
	 */
	public $pass_through_data = '';
}










