<?php
namespace gokabam_api;


require_once( PLUGIN_PATH . '/lib/Input.php' );
require_once( PLUGIN_PATH .'/lib/ErrorLogger.php' );
require_once( PLUGIN_PATH .'/lib/DBSelector.php' );


class ParseTag {

	protected static  $keys_to_check = ['kid','parent','text','delete','value'];
	protected static  $reference_table = 'gokabam_api_tags';

	/**
	 * @param ParserManager $manager
	 * @param mixed $input
	 * @param null $parent
	 * @return GKA_TAG[]
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	static function parse($manager, $input, $parent = null) {

		ErrorLogger::unused_params($parent);

		if (!is_array($input)) {
			return [];
		}

		/**
		 * @var GKA_TAG[] $ret
		 */
		$ret = [];

		foreach ($input as $node) {
			$beer = self::convert($manager,$node);
			$beer = self::manage($manager,$beer);
			//if this were a type which had children, then do each child array found in node by passing it to the
			// correct parser , along with node, and putting the returns on the member in beer

			$manager->processed_roots[] = $beer;
			//add it to the things going out, the callee may not be finished with
			// it, but this is going to be processed after they are done
			$ret[] = $beer;
		}


		return $ret;

	}

	/**
	 * @param ParserManager $manager
	 * @param array $node
	 *
	 * @return GKA_TAG
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	protected static function convert($manager, $node) {

		$classname = get_called_class();
		$db_thing = new GKA_TAG();
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

		if (empty($db_thing->parent)) {
			throw new ApiParseException("Parent needs to be filled in for " . $db_thing->kid);
		}
		//copy over pass through
		if (array_key_exists('pass_through',$node)) {
			$db_thing->pass_through = $node['pass_through'];
		}

		$db_thing->kid = $manager->kid->generate_or_refresh_primary_kid($db_thing->kid,self::$reference_table);

		$db_thing->parent = $manager->kid->convert_parent_string_kid($db_thing->parent,$db_thing->kid,self::$reference_table);


		return $db_thing;

	}

	/**
	 * creates this if the kid is empty, or updates it if not empty
	 * @param ParserManager $manager
	 * @param GKA_TAG $db_thing
	 *
	 * @return GKA_TAG
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
				"INSERT INTO gokabam_api_tags(tag_label,tag_value,target_object_id,last_page_load_id) VALUES(?,?,?,?)",
					['ssii',$db_thing->text,$db_thing->value,$db_thing->parent->object_id,$last_page_load_id],
					MYDB::LAST_ID,
					'@sey@ParseTag::manage->insert'
				);

			$db_thing->kid = $manager->kid->generate_or_refresh_primary_kid(
					$db_thing->kid,self::$reference_table,$new_id,null);

		} else {
			//update this
			$id = $db_thing->kid->primary_id;

			if (empty($id)) {
				throw new ApiParseException("Internal code did not generate an id for update");
			}
			$manager->mydb->execSQL(
				"UPDATE gokabam_api_tags SET tag_label = ?,tag_value = ?,target_object_id=?,is_deleted = ?, last_page_load_id = ? WHERE id = ? ",
					['ssiiii',$db_thing->text,$db_thing->value,$db_thing->parent->object_id,$db_thing->delete,$last_page_load_id,$id],
					MYDB::ROWS_AFFECTED,
				'@sey@ParseTag::manage->update'
				);
		}
		$db_thing->status = true; //right now we do not do much with status
		return $db_thing;
	}
}