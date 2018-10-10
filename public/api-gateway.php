<?php

namespace gokabam_api;
require_once  'gokabam.goodies.php';
require_once  PLUGIN_PATH.'lib/Input.php';
require_once  PLUGIN_PATH.'lib/ErrorLogger.php';





/**
 * Api Gateway
 *  The public facing part of the api, here the top commands are routed down to where they are processed
 *
 */
class ApiGateway {

	/**
	 * @var MYDB|null $mydb
	 */
	protected $mydb = null;

	/**
	 * @var integer|null $latest_version_id
	 */
	protected $latest_version_id = null;

	/**
	 * ApiGateway constructor.
	 *
	 * @param MYDB $mydb
	 * @param integer $version_id
	 */
	public function __construct( $mydb, $version_id ) {

		$this->mydb = $mydb;
		$this->latest_version_id     = $version_id;
	}

	/**
	 * Reads from post, if any commands that are recognized will do them and return result
	 * if error or not sufficient information will return an error result
	 * This function does all create, update and delete, as well as gets status and initial data
	 * @return array
	 */
	public function all() {
		$ret = ['is_valid' => true];
		$action = null;
		try {
			$action = Input::get( 'action', Input::THROW_IF_EMPTY );

			switch ($action) {
				case 'create':
					{
						$class = Input::get( 'class', Input::THROW_IF_EMPTY );
						$parent = Input::get( 'parent', Input::THROW_IF_MISSING );
						if (empty($parent)) {$parent = null;}
						$params = Input::get( 'params', Input::THROW_IF_EMPTY );
						if (!is_array($params)) {
							throw new \InvalidArgumentException("Params needs to be an object, not just a scalar");
						}
						$ret['data'] = $this->create($class,$parent,$params);
						break;
					}
				default: {
					throw new \InvalidArgumentException("No case for action: [$action]");
				}
			}
			$ret['action'] = $action;
		} catch (\Exception $e) {
			$exception_info = ErrorLogger::saveException($e);
			$ret = ['is_valid' => false, 'message' => $e->getMessage(), 'exception'=>$exception_info, 'action' =>$action ]	;
		}
		return $ret;

	}
	/*
	 * @param string $what <p>
	 *   one of:

	to get a status (from start) or a change log (between two times)
		will be same update and insert array

	server can also download a full status this way

	server can upload a version of this , and save it under a name. Can display it by name, instead
		so display all current, display history, and status same request
		display history is read only though

		web page will be able to upload a json file to save
		will also be able to download to computer a save

	to update or insert or delete
	below can be an array of one or more things (can mix types):

action: update|save|report|get
	update: will create or update based on what is here
	save: will take the information here, and save it under the name
	get: will retrieve a named save, it will put the time it was saved under the begin timestamp
	report: will list the changes, insertions and deletions for between the two timestamps
begin_timestamp: null or timestamp
end_timestamp: null or timestamp
save_name: null or name
operation_status: an overview of the entire operation
words: array 0 or more
word
	parent: [kid] is anything,not null, cannot be another word or tag
	type:   name,title,blurb,description,overview,data
	language: 2 letter language code, but if data this is not processed as text to read
	text: non empty string
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert
	delete: null or missing or true. If true, then kid must be entered also


versions:
version
	parent: null
	text: non empty string, this is the internal name of the version
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert
	delete: null or missing or true. If true, then kid must be entered also

journals:
journal
	parent: [kid] is anything, not null, cannot be another journal,tag or word
	text: non empty string
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert
	delete: null or missing or true. If true, then kid must be entered also


tags:
tag
	parent: [kid] is anything, not null, cannot be another tag or word
	text: non empty string
	value: null or non empty string
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert
	delete: null or missing or true. If true, then kid must be entered also

data_groups:
data_group
	parent: null, database, put in definitions below, or [kid] of: input,output,header
		note: null parent means will be used as reference and actually copied when part of a definition of something else
	examples: null or array of json values that must match the members in a group
	* if the examples do not match the members, or new members do not match the examples, an error is thrown
	members: null or array of data_elements (defined here)
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert
	delete: null or missing or true. If true, then kid must be entered also
	members: null or array of data_elements (defined here)
		data_element
			kid:  this is supplied after the server creates it
			status: (put here on return ) also kid will be filled out if this is an insert
			note: these are not used to hold an actual value, just define type and what kind of values they could hold
			type: string|integer|number|boolean|object|array
			text: the element name
			is_nullable: if this can be not filled in , optional
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
							rank: order of display, can be null
				array:
					min: the minimum number of elements
					max: the maximum number of elements
					array of data element types, defined here as a data element
						additional properties for all data elements here:
							rank: order of display, can be null
							radio_group: if given a non null value, only one thing in this group can be used in the array


api_versions:
api_version
	parent: null
	text: non empty string, this is the internal name of the version
	headers: null or an array of headers (defined here ) (see definition under api)
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert
	delete: null or missing or true. If true, then kid must be entered also

api_families:
api_family
	parent: api_version
	text: non empty string, this is the internal name of the family
	headers: null or an array of headers (defined here ) (see definition under api)
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert
	delete: null or missing or true. If true, then kid must be entered also

apis:
api
	parent: api_family
	text: non empty string, this is the way to call the api (the function name)
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert
	delete: null or missing or true. If true, then kid must be entered also
	inputs:
		input
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
			kid: null or this is an update and not an insert, if update only non null values changed
	outputs:
		output
			parent: api (its part of the api definition above)
			http_code: a number
			data_group: can be any (defined here or by kid)
			headers: null or array of headers (defined here)
			kid: null or this is an update and not an insert, if update only non null values changed
	headers:
		header
			parent: api,family,version,output (its part of the definitions above)
			name: string header name
			value: the contents/value of the header can have regex groups with names that match the out data group
			data_group: null or elements must match the regex group names of the value
			kid: null or this is an update and not an insert, if update only non null values changed

use_cases:
version_use_case
	parent: version kid
	parts: array of parts defined here
		in_data_group: null or defined here or kid of any inside parent
		out_data_group: null or defined here or kid of any inside parent
		in_api_id: only if in_data_group is not defined
		ref_id: any number supplied
		children: array of ref_ids
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert

api_use_case
	parent: api kid
	parts: array of use_case_part defined here
		in_data_group: null or defined here or kid of any inside parent
		out_data_group: null or defined here or kid of any inside parent
		ref_id: any number supplied
		children: array of ref_ids
		sql_part: if out_data_group is null can use sql, an array of zero or more
			sql_part_enum: select,from,joins,where,limit,offset,ordering
			table_element: kid of element from any db table group
			reference_element: kid of element from any db table group
			outside_element: reference id, from any input group in this use case
			text: describes what the part does, and adds details mentioning operations and constants
	kid: null or this is an update and not an insert, if update only non null values changed
	status: (put here on return ) also kid will be filled out if this is an insert


	@return will return the data entered, but with [kid] for each thing generated as well as status key for each create or update
	 *
	 * </p>
	 * @param string|null $parent <p>
	 *      hash id of the parent, or null
	 * </p>
	 * @param array|null $params <p>
	 *
	 * </p>
	 *
	 * @return array
	 */
	protected function create($what,$parent,$params) {
		$ret = [];



		return $ret;
	}

	protected function update($kid,$params) {
		$ret = [];



		return $ret;
	}
}
