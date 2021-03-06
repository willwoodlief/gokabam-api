<?php
namespace gokabam_api;


require_once( PLUGIN_PATH . '/lib/Input.php' );
require_once( PLUGIN_PATH .'/lib/ErrorLogger.php' );
require_once( PLUGIN_PATH .'/lib/DBSelector.php' );


class ParsePart {

	protected static  $keys_to_check = ['kid','parent','ref_id','in_api','delete'];
	protected static  $reference_table = 'gokabam_api_use_case_parts';

	/**
	 * @param ParserManager $manager
	 * @param mixed $input
	 * @param GKA_Kid|null $parent
	 * @return GKA_Use_Part[]
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	static function parse($manager, $input, $parent) {



		if (!is_array($input)) {
			return [];
		}

		/**
		 * @var GKA_Use_Part[] $ret
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


			if (sizeof($beer->in_data_groups) > 1) {
				throw new ApiParseException("Use Case Part Can only have 0 or 1 inputs data group");
			}

			if (sizeof($beer->in_data_groups) > 1) {
				throw new ApiParseException("Use Case Part Can only have 0 or 1 inputs data group");
			}
			
			

			//set the kids for the connections going out from here
			$res = $manager->mydb->execSQL("SELECT id,object_id from gokabam_api_use_case_part_connections WHERE parent_use_case_part_id = ?",
				['i',$beer->kid->primary_id],
				MYDB::ROWS_AFFECTED,
				'@sey@ParseUseCasePart::parse->update(in_data_group_id)'
			);
			if ($res) {
				foreach ($res as $row) {
					$con_id = $row->id;
					$conKid = new GKA_Kid();
					$conKid->primary_id = $con_id;
					$conKid->table = 'gokabam_api_use_case_part_connections';
					$conKid->object_id = $row->object_id;
					$manager->kid_talk->fill_kids_in($conKid);
					$beer->source_connections[] = $conKid;
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
	 * @return GKA_Use_Part
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	protected static function convert($manager, $node,$parent) {

		$classname = get_called_class();
		$db_thing = null;
		if (is_array($node)) {
			$db_thing = new GKA_Use_Part();
		} else {
			if (is_string($node)) {
				/**
				 * @var $db_thing GKA_Use_Part
				 */
				$db_thing = $manager->recon->spring($node);
				if (strcmp(self::$reference_table,$db_thing->kid->table) !== 0) {
					throw new ApiParseException("wrong class, expected ".self::$reference_table ." This is a " . get_class($db_thing) );
				}
				if ($db_thing->parent && $db_thing->parent->object_id && ($parent->object_id == $db_thing->parent->object_id)) {
					return null;
				}
				$db_thing->parent = $parent; //overwrite earlier parent
				$db_thing->kid = null ; //make it so a copy is made, and not an update of the original
				return $db_thing;
			}
		}

		if (empty($db_thing)) {
			throw new ApiParseException("Invalid Entry: need a valid kid id, or a hash");
		}

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

		if (empty($db_thing->parent)  && !$parent) {
			throw new ApiParseException("Parent needs to be filled in for " . $db_thing->kid);
		}
		//copy over pass through
		if (array_key_exists('pass_through',$node)) {
			$db_thing->pass_through = $node['pass_through'];
		}

		if (array_key_exists('md5_checksum',$node)) {
			$db_thing->md5_checksum = $node['md5_checksum'];
		}

		$db_thing->kid = $manager->kid_talk->generate_or_refresh_primary_kid($db_thing->kid,self::$reference_table);

		if (empty($db_thing->parent)) {
			$db_thing->parent = $parent;
		}

		if ( $db_thing->parent &&
		     is_object($db_thing->parent) &&
		     ( strcmp(get_class($db_thing->parent),'gokabam_api\GKA_Kid') === 0 )
		){
			// do not set parent to anything else
		} else {
			$db_thing->parent = $manager->kid_talk->convert_parent_string_kid( $db_thing->parent, $db_thing->kid, self::$reference_table );
		}

		//convert the string kids to object kids
		$db_thing->in_api = $manager->kid_talk->generate_or_refresh_primary_kid(
			$db_thing->in_api,'gokabam_api_apis');


		switch ($db_thing->parent->table) {
			case 'gokabam_api_use_cases' : {
				break;
			}
			default:{
					throw new ApiParseException("input parent must be a api");
				}
		}

		return $db_thing;

	}

	/**
	 * creates this if the kid is empty, or updates it if not empty
	 * @param ParserManager $manager
	 * @param GKA_Use_Part $db_thing
	 *
	 * @return GKA_Use_Part
	 * @throws ApiParseException
	 * @throws SQLException
	 */
	protected static function manage($manager, $db_thing) {

		$last_page_load_id = $manager->last_load_id;
		$api_id = null;
		if ($db_thing->in_api) {
			$api_id = $db_thing->in_api->primary_id;
		}


		if (empty($db_thing->kid)) {
			//check delete flag to see if something messed up
			if ($db_thing->delete) {
				throw new ApiParseException("Cannot put a delete flag on a new object, the kid was empty");
			}


			//create this
			$new_id = $manager->mydb->execSQL(
				"INSERT INTO gokabam_api_use_case_parts(
						use_case_id,
						in_api_id,
						rank,
						last_page_load_id,
						initial_page_load_id
						) VALUES(?,?,?,?,?)",
				[
					'iiiii',
					$db_thing->parent->primary_id,
					$api_id,
					$db_thing->ref_id,
					$last_page_load_id,
					$last_page_load_id
				],
				MYDB::LAST_ID,
				'@sey@ParseUseCasePart::manage->insert'
			);

			$db_thing->kid = $manager->kid_talk->generate_or_refresh_primary_kid(
				$db_thing->kid,self::$reference_table,$new_id,null);

		} else {
			//update this
			$id = $db_thing->kid->primary_id;

			if (empty($id)) {
				throw new ApiParseException("Internal code did not generate an id for update");
			}
			$manager->kid_talk->md5_check($db_thing->kid,$db_thing->md5_checksum);


			$manager->mydb->execSQL(
				"UPDATE gokabam_api_use_case_parts 
					  SET 
						use_case_id = ?,
						in_api_id = ?,
						rank = ?,
						is_deleted = ?,
						last_page_load_id = ?
					   WHERE id = ? ",
				[
					'iiiiii',
					$db_thing->parent->primary_id,
					$api_id,
					$db_thing->ref_id,
					$last_page_load_id,
					$last_page_load_id,
					$id
				],
				MYDB::ROWS_AFFECTED,
				'@sey@ParseUseCasePart::manage->update'
			);
		}
		$db_thing->status = true; //right now we do not do much with status
		return $db_thing;
	}
}