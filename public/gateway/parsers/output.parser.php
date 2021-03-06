<?php
namespace gokabam_api;


require_once( PLUGIN_PATH . '/lib/Input.php' );
require_once( PLUGIN_PATH .'/lib/ErrorLogger.php' );
require_once( PLUGIN_PATH .'/lib/DBSelector.php' );


class ParseOutput {

	protected static  $keys_to_check = ['kid','parent','http_code','delete'];
	protected static  $reference_table = 'gokabam_api_outputs';

	/**
	 * @param ParserManager $manager
	 * @param mixed $input
	 * @param GKA_Kid|null $parent
	 * @return GKA_Output[]
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	static function parse($manager, $input, $parent) {



		if (!is_array($input)) {
			return [];
		}

		/**
		 * @var GKA_Output[] $ret
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

			if (sizeof($beer->data_groups) > 1) {
				throw new ApiParseException("Output Can only have 0 or 1 data group");
			}


			$ret[] = $beer;
		}


		return $ret;

	}

	/**
	 * @param ParserManager $manager
	 * @param array $node
	 * @param GKA_Kid|null $parent
	 * @return GKA_Output
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	protected static function convert($manager, $node,$parent) {

		$classname = get_called_class();
		$db_thing = null;
		if (is_array($node)) {
			$db_thing = new GKA_Output();
		} else {
			if (is_string($node)) {
				/**
				 * @var $db_thing GKA_Output
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

		switch ($db_thing->parent->table) {
			case 'gokabam_api_apis' : {
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
	 * @param GKA_Output $db_thing
	 *
	 * @return GKA_Output
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
				"INSERT INTO gokabam_api_outputs(
						api_id,
						http_return_code,
						last_page_load_id,
						initial_page_load_id
						) VALUES(?,?,?,?)",
				[
					'iiii',
					$db_thing->parent->primary_id,
					$db_thing->http_code,
					$last_page_load_id,
					$last_page_load_id
				],
				MYDB::LAST_ID,
				'@sey@ParseOutput::manage->insert'
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
				"UPDATE gokabam_api_outputs 
					  SET 
					  api_id = ?,
					  http_return_code = ?,
					  is_deleted = ?,
					  last_page_load_id = ?
					   WHERE id = ? ",
				[
					'iiiii',
					$db_thing->parent->primary_id,
					$db_thing->http_code,
					$db_thing->delete,
					$last_page_load_id,
					$id
				],
				MYDB::ROWS_AFFECTED,
				'@sey@ParseOutput::manage->update'
			);
		}
		$db_thing->status = true; //right now we do not do much with status
		return $db_thing;
	}
}