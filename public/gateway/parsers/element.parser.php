<?php
namespace gokabam_api;


require_once( PLUGIN_PATH . '/lib/Input.php' );
require_once( PLUGIN_PATH .'/lib/ErrorLogger.php' );
require_once( PLUGIN_PATH .'/lib/DBSelector.php' );


class ParseElement {

	protected static  $keys_to_check = ['kid','parent','delete','text','type','format','pattern','is_nullable',
		'is_optional','enum_values','default_value','min','max','multiple','precision','rank','radio_group'];
	/*


	data_type_name	=>  text
	base_type_enum  =>  type
	format_enum     =>  format
	pattern         =>  pattern
	is_nullable     =>  is_nullable
	is_optional     =>  is_optional
	enum_values     =>  enum_values
	default_value   =>  default_value
	data_min        =>  min
	data_max        =>  max
	data_multiple   =>  multiple
	data_precision  =>  precision
	rank            =>  rank
	radio_group     =>  radio_group

	data_elements

	 */

	protected static  $reference_table = 'gokabam_api_data_elements';

	/**
	 * @param ParserManager $manager
	 * @param mixed $input
	 * @param GKA_Kid|null $parent
	 * @return GKA_Element[]
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	static function parse($manager, $input, $parent) {


		if (!is_array($input)) {
			return [];
		}

		/**
		 * @var GKA_Element[] $ret
		 */
		$ret = [];

		foreach ($input as $node) {
			$beer = self::convert($manager,$node,$parent);
			$beer = self::manage($manager,$beer);
			//if this were a type which had children, then do each child array found in node by passing it to the
			// correct parser , along with node, and putting the returns on the member in beer

			$manager->add_to_finalize_roots($beer);
			//add it to the things going out, the callee may not be finished with
			// it, but this is going to be processed after they are done


			//node may have other things to process
			$sub_parser_manager = new ParserManager(
				$manager->kid_talk,
				$manager->mydb,
				null,
				$manager->last_load_id,
				$node,
				$beer->kid,
				$manager
			);
			//when this returns all the sub processing is done, and the children are created
			foreach ($sub_parser_manager->processed_array as $key => $top_node) {
				//if beer has that property, and its  empty, then move it over
				if (property_exists($beer, $key)) {
					if ( is_array($beer->$key) && empty($beer->$key) && is_array($top_node) && (!empty($top_node)) ) {
						$beer->$key = $top_node;
					}
				}
			}


			$ret[] = $beer;
		}


		return $ret;

	}

	/**
	 * @param ParserManager $manager
	 * @param array $node
	 * @param GKA_Kid|null $parent
	 * @return GKA_Element
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	protected static function convert($manager, $node,$parent) {

		$classname = get_called_class();
		$db_thing = new GKA_Element();
		foreach (self::$keys_to_check as $what) {
			if (!array_key_exists($what,$node)) {
				$problem = JsonHelper::toString($node);
				throw new ApiParseException("missing key in input: $classname cannot find $what in $problem");
			}

			if (is_string($node[$what])) {
				$node[$what] = trim($node[$what]);
			}

			if( is_string($node[$what]) && !is_numeric($node[$what]) && empty($node[$what])) {$node[$what]=null;}

			$db_thing->$what = $node[$what];
		}
		if (is_null($db_thing->delete)) {$db_thing->delete = 0;}

		//validation !
		$debug_help = '';
		if ($db_thing->kid  ) {
			if (is_string($db_thing->kid)) {
				$debug_help = "[Element kid of ". $db_thing->kid . "]";
			} else {
				if (is_object($db_thing->kid)) {
					$debug_help = "[Element kid of ". $db_thing->kid->kid . "]";
				}
			}
		}

		//$type - string|integer|number|boolean|object|array  -- this is caught by the trigger, but will make life funner
		// if we weed it out now
		switch ($db_thing->type) {
			case 'string':
			case 'integer':
			case 'number':
			case 'boolean':
			case 'object':
			case 'array': {
				break;
			}
			default: {
				throw new ApiParseException("Element type must be string|integer|number|boolean|object|array $debug_help");
			}
		}



		if (!empty($db_thing->format)) {
			switch ($db_thing->type) {

				case 'string': {
					switch ($db_thing->format) {
						case 'date':
						case 'date-time':
						case 'password':
						case 'byte':
						case 'binary':
						case 'email':
						case 'uri':
						case 'use_pattern': {
							break;
						}
						default: {
							throw new ApiParseException("Element string type can only be date|date-time|password|byte|binary|email|uri|use_pattern $debug_help");
						}
					}
					break;
				}

				case 'integer': {
					switch ($db_thing->format) {
						case 'int32':
						case 'int8':
						case 'int64':
						case 'use_pattern': {
							break;
						}
						default: {
							throw new ApiParseException("Element integer type can only be int8|int32|int64|use_pattern $debug_help");
						}
					}
					break;
				}

				case 'number': {
					switch ($db_thing->format) {
						case 'float':
						case 'double':
						case 'use_pattern': {
							break;
						}
						default: {
							throw new ApiParseException("Element integer type can only be float|double|use_pattern $debug_help");
						}
					}
					break;
				}

				case 'boolean':
				case 'object':
				case 'array':
				default: {
					throw new ApiParseException("Element format option cannot be used with boolean, object or array types [{$db_thing->type}] $debug_help");
				}
			}
		}

		if (!empty($db_thing->pattern)) {
			switch ($db_thing->type) {
				case 'string':
				case 'integer':
				case 'number': {
					break;
				}
				case 'boolean':
				case 'object':
				case 'array':
				default: {
					throw new ApiParseException("Element pattern can only be used with  string|integer|number $debug_help");
				}
			}
		}

		if (!empty($db_thing->pattern)) {
			if (strcmp($db_thing->format,'use_pattern') !== 0) {
				throw new ApiParseException("Element pattern can only be set if the format is use_pattern $debug_help");
			}
		}


		if (!empty($db_thing->enum_values)) {
			switch ($db_thing->type) {
				case 'string':
				case 'integer':
				case 'number': {
					break;
				}
				case 'boolean':
				case 'object':
				case 'array':
				default: {
					throw new ApiParseException("Element enum values can only be used with  string|integer|number $debug_help");
				}
			}
		}


		if (!empty($db_thing->default_value)) {
			switch ($db_thing->type) {
				case 'string':
				case 'integer':
				case 'number':
				case 'boolean': {
					break;
				}
				case 'object':
				case 'array':
				default: {
					throw new ApiParseException("Element default_value  can only be used with  string|integer|number|boolean $debug_help");
				}
			}
		}


		if (!empty($db_thing->min)) {
			switch ($db_thing->type) {
				case 'string':
				case 'integer':
				case 'number':
				case 'array': {
					break;
				}
				case 'object':
				case 'boolean':
				default: {
					throw new ApiParseException("Element min  can only be used with  string|integer|number|array $debug_help");
				}
			}
		}

		if (!empty($db_thing->max)) {
			switch ($db_thing->type) {
				case 'string':
				case 'integer':
				case 'number':
				case 'array': {
					break;
				}
				case 'object':
				case 'boolean':
				default: {
					throw new ApiParseException("Element max  can only be used with  string|integer|number|array $debug_help");
				}
			}
		}

		if (!empty($db_thing->multiple)) {
			switch ($db_thing->type) {
				case 'integer':
				case 'number': {
					break;
				}
				case 'string':
				case 'array':
				case 'object':
				case 'boolean':
				default: {
					throw new ApiParseException("Element multiple  can only be used with  integer|number| $debug_help");
				}
			}
		}

		if (!empty($db_thing->precision)) {
			switch ($db_thing->type) {
				case 'number': {
					break;
				}
				case 'integer':
				case 'string':
				case 'array':
				case 'object':
				case 'boolean':
				default: {
					throw new ApiParseException("Element precision  can only be used with number $debug_help");
				}
			}
		}



		//copy over pass through
		if (array_key_exists('pass_through',$node)) {
			$db_thing->pass_through = $node['pass_through'];
		}

		$db_thing->kid = $manager->kid_talk->generate_or_refresh_primary_kid($db_thing->kid,self::$reference_table);

		if (empty($db_thing->parent)) {
			$db_thing->parent = $parent;
		}

		if ( $db_thing->parent &&
		     is_object($db_thing->parent) &&
		     ( strcmp(get_class($db_thing->parent),"gokabam_api\GKA_Kid") === 0 )
		){
			// do not set parent to anything else
		} else {
			$db_thing->parent = $manager->kid_talk->convert_parent_string_kid( $db_thing->parent, $db_thing->kid, self::$reference_table );
		}


		return $db_thing;

	}

	/**
	 * creates this if the kid is empty, or updates it if not empty
	 * @param ParserManager $manager
	 * @param GKA_Element $db_thing
	 *
	 * @return GKA_Element
	 * @throws ApiParseException
	 * @throws SQLException
	 */
	protected static function manage($manager, $db_thing) {

		$last_page_load_id = $manager->last_load_id;
		switch ($db_thing->parent->table) {
			case 'gokabam_api_data_elements' : {
				$group_id = null;
				$parent_element_id = $db_thing->parent->primary_id;
				break;
			}
			case 'gokabam_api_data_groups' : {
				$group_id = $db_thing->parent->primary_id;
				$parent_element_id = null;
				break;
			}
			default:
				{
					throw new ApiParseException("parent must be an element or a group");
				}
		}

		if (empty($db_thing->kid)) {
			//check delete flag to see if something messed up
			if ($db_thing->delete) {
				throw new ApiParseException("Cannot put a delete flag on a new object, the kid was empty");
			}


			//create this


			$new_id = $manager->mydb->execSQL(
				"INSERT INTO gokabam_api_data_elements(
						last_page_load_id,
						initial_page_load_id,
						group_id,
						parent_element_id,
						data_type_name,
						base_type_enum,
						format_enum,
						pattern,
						is_nullable,
						is_optional,
						enum_values,
						default_value,
						data_min,
						data_max,
						data_multiple,
						data_precision,
						rank,
						radio_group		
						) 
						VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
				[
					'iiiissssiissiisiis',
					$last_page_load_id,
					$last_page_load_id,
					$group_id,
					$parent_element_id,
					$db_thing->type ,
					$db_thing->format ,
					$db_thing->pattern ,
					$db_thing->is_nullable ,
					$db_thing->is_optional ,
					$db_thing->enum_values ,
					$db_thing->default_value ,
					$db_thing->min ,
					$db_thing->max ,
					$db_thing->multiple ,
					$db_thing->precision ,
					$db_thing->rank ,
					$db_thing->radio_group ,
				],
				MYDB::LAST_ID,
				'@sey@ParseElement::manage->insert'
			);

			$db_thing->kid = $manager->kid_talk->generate_or_refresh_primary_kid(
				$db_thing->kid,self::$reference_table,$new_id,null);

		} else {
			//update this
			$id = $db_thing->kid->primary_id;

			if (empty($id)) {
				throw new ApiParseException("Internal code did not generate an id for update");
			}
			$manager->mydb->execSQL(
				"UPDATE gokabam_api_data_elements SET 
 						group_id = ? ,
						parent_element_id = ?,
						data_type_name = ?,
						base_type_enum = ?,
						format_enum = ?,
						pattern = ?,
						is_nullable = ?,
						is_optional = ?,
						enum_values = ?,
						default_value = ?,
						data_min = ?,
						data_max = ?,
						data_multiple = ?,
						data_precision = ?,
						rank = ?,
						radio_group  = ?	
 					  WHERE id = ? ",
				[
					'iiiissssiissiisiisi',
					$last_page_load_id,
					$last_page_load_id,
					$group_id,
					$parent_element_id,
					$db_thing->type ,
					$db_thing->format ,
					$db_thing->pattern ,
					$db_thing->is_nullable ,
					$db_thing->is_optional ,
					$db_thing->enum_values ,
					$db_thing->default_value ,
					$db_thing->min ,
					$db_thing->max ,
					$db_thing->multiple ,
					$db_thing->precision ,
					$db_thing->rank ,
					$db_thing->radio_group ,
					$id
				],
				MYDB::ROWS_AFFECTED,
				'@sey@ParseElement::manage->update'
			);
		}
		$db_thing->status = true; //right now we do not do much with status
		return $db_thing;
	}
}