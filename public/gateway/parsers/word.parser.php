<?php
namespace gokabam_api;


require_once( PLUGIN_PATH . '/lib/Input.php' );
require_once( PLUGIN_PATH .'/lib/ErrorLogger.php' );
require_once( PLUGIN_PATH .'/lib/DBSelector.php' );


class ParseWord {

	protected static  $keys_to_check = ['kid','parent','text','delete','language','type'];
	protected static  $reference_table = 'gokabam_api_words';

	/**
	 * @param ParserManager $manager
	 * @param mixed $input
	 * @param GKA_Kid|null $parent
	 * @return GKA_Word[]
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	static function parse($manager, $input, $parent = null) {


		if (!is_array($input)) {
			return [];
		}

		/**
		 * @var GKA_Word[] $ret
		 */
		$ret = [];

		foreach ($input as $node) {
			$beer = self::convert($manager,$node,$parent);
			$beer = self::manage($manager,$beer);
			//if this were a type which had children, then do each child array found in node by passing it to the
			// correct parser , along with node, and putting the returns on the member in beer



			//add it to the things going out, the callee may not be finished with
			// it, but this is going to be processed after they are done
			$manager->add_to_finalize_roots($beer);


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
	 * @return GKA_Word
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	protected static function convert($manager, $node,$parent) {

		$classname = get_called_class();
		$db_thing = null;
		if (is_array($node)) {
			$db_thing = new GKA_Word();
		} else {
			if (is_string($node)) {
				/**
				 * @var $db_thing GKA_Word
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

		if (empty($db_thing->parent) && !$parent) {
			throw new ApiParseException("Parent needs to be filled in @$classname for " . $db_thing->kid);
		}
		//copy over pass through
		if (array_key_exists('pass_through',$node)) {
			$db_thing->pass_through = $node['pass_through'];
		}

		//check language length, if greater than 2 go ahead
		if ( strlen($db_thing->language) !== 2 ) {
			throw new ApiParseException("The language code needs to be exactly two characters long,  @$classname for " . $db_thing->kid);
		}

		if (empty($db_thing->text)) {
			throw new ApiParseException("Words need to have some kind of content, even if just empty spaces");
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



		return $db_thing;

	}

	/**
	 * creates this if the kid is empty, or updates it if not empty
	 * @param ParserManager $manager
	 * @param GKA_Word $db_thing
	 * @return GKA_Word
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
				"INSERT INTO gokabam_api_words(
						word_code_enum,
						iso_639_1_language_code,
						da_words,
						target_object_id,
						last_page_load_id,
						initial_page_load_id
						)
 					  VALUES(?,?,?,?,?,?)",
					[
						'sssiii',
						$db_thing->type,
						$db_thing->language,
						$db_thing->text,
						$db_thing->parent->object_id,
						$last_page_load_id,
						$last_page_load_id
					],
					MYDB::LAST_ID,
					'@sey@ParseWord::manage->insert'
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
				"UPDATE gokabam_api_words SET
 						word_code_enum = ?,
 						iso_639_1_language_code = ?,
 						da_words = ?,
 						target_object_id=?,
 						is_deleted = ?,
 						last_page_load_id = ?
 						 WHERE id = ? ",
					['sssiiii',
						$db_thing->type,
						$db_thing->language,
						$db_thing->text,
						$db_thing->parent->object_id,
						$db_thing->delete,
						$last_page_load_id,
						$id
					],
					MYDB::ROWS_AFFECTED,
				'@sey@ParseWord::manage->update'
				);
		}
		$db_thing->status = true; //right now we do not do much with status
		return $db_thing;
	}
}