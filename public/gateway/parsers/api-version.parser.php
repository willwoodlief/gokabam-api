<?php
namespace gokabam_api;


require_once( PLUGIN_PATH . '/lib/Input.php' );
require_once( PLUGIN_PATH .'/lib/ErrorLogger.php' );
require_once( PLUGIN_PATH .'/lib/DBSelector.php' );


class ParseApiVersion {

	protected static  $keys_to_check = ['kid','text','delete'];
	protected static  $reference_table = 'gokabam_api_api_versions';

	/**
	 * @param ParserManager $manager
	 * @param mixed $input
	 * @param GKA_Kid|null $parent
	 * @return GKA_Version[]
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	static function parse($manager, $input, $parent) {


		if (!is_array($input)) {
			return [];
		}

		/**
		 * @var GKA_Version[] $ret
		 */
		$ret = [];

		foreach ($input as $node) {
			$beer = self::convert($manager,$node,$parent);
			$beer = self::manage($manager,$beer);

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
					if ( is_array($beer->$key) && empty($beer->$key) && is_array($top_node) && (!empty($top_node))) {
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
	 * @return GKA_Version
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	protected static function convert($manager, $node,$parent) {

		if ( !empty($parent) ) {
			throw new ApiParseException("API Version never has a parent, and is always top level");
		}

		if (array_key_exists('parent',$node)) {
			if ( !empty($node['parent']) ) {
				throw new ApiParseException("API Version never has a parent, and is always top level");
			}
		}
		$classname = get_called_class();
		$db_thing = new GKA_Version();
		foreach (self::$keys_to_check as $what) {
			if (!array_key_exists($what,$node)) {
				$problem = JsonHelper::toString($node);
				throw new ApiParseException("missing key in input: $classname cannot find $what in $problem");
			}
			if( is_string($what) && !is_numeric($what) && empty($what)) {$what=null;}
			$db_thing->$what = $node[$what];
		}
		if (is_null($db_thing->delete)) {$db_thing->delete = 0;}
		//copy over pass through
		if (array_key_exists('pass_through',$node)) {
			$db_thing->pass_through = $node['pass_through'];
		}

		$db_thing->kid = $manager->kid_talk->generate_or_refresh_primary_kid($db_thing->kid,self::$reference_table);


		return $db_thing;

	}

	/**
	 * creates this if the kid is empty, or updates it if not empty
	 * @param ParserManager $manager
	 * @param GKA_Version $db_thing
	 * @return GKA_Version
	 * @throws ApiParseException
	 * @throws SQLException
	 */
	protected static function manage($manager, $db_thing) {


		$last_page_load_id = $manager->last_load_id;
		if (empty($db_thing->kid)) {
			//check delete flag to see if something messed up
			if ($db_thing->delete) {
				throw new ApiParseException("Cannot put a delete flag on a new object, the kid was empty");
			}
			//create this
			$new_id = $manager->mydb->execSQL(
				"INSERT INTO gokabam_api_api_versions(
						api_version,
						last_page_load_id,
						initial_page_load_id
						) 
						VALUES(?,?,?)",
					[
						'sii',
						$db_thing->text,
						$last_page_load_id,
						$last_page_load_id
					],
					MYDB::LAST_ID,
					'@sey@ParseAoiVersion::manage->insert'
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
				"UPDATE gokabam_api_api_versions SET 
					api_version = ?,
					is_deleted = ?,
				    last_page_load_id = ?
				     WHERE id = ? ",
					[
						'siii',
						$db_thing->text,
						$db_thing->delete,
						$last_page_load_id,
						$id
					],
					MYDB::ROWS_AFFECTED,
				'@sey@ParseApiVersion::manage->update'
				);
		}
		$db_thing->status = true; //right now we do not do much with status
		return $db_thing;
	}
}