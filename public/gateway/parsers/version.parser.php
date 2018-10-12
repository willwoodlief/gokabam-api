<?php
namespace gokabam_api;


require_once( PLUGIN_PATH . '/lib/Input.php' );
require_once( PLUGIN_PATH .'/lib/ErrorLogger.php' );
require_once( PLUGIN_PATH .'/lib/DBSelector.php' );


class ParseVersion {

	protected static  $keys_to_check = ['kid','text','delete','git_tag','git_commit_id','status'];


	/**
	 * @param ParserManager $manager
	 * @param mixed $input
	 * @param null $parent
	 * @return GKA_Version[]
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
		 * @var GKA_Version[] $ret
		 */
		$ret = [];

		foreach ($input as $node) {
			$beer = self::convert($manager,$node);
			$beer = self::manage($manager,$beer);
			//if this were a type which had children, then do each child array found in node by passing it to the
			// correct parser , along with node, and putting the returns on the member in beer
			$ret[] = $beer;

		}

		return $ret;

	}

	/**
	 * @param ParserManager $manager
	 * @param array $node
	 *
	 * @return GKA_Version
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 */
	protected static function convert($manager, $node) {

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
		$db_thing->kid = $manager->kid->generate_or_refresh_kid($db_thing->kid,'gokabam_api_versions');
		return $db_thing;

	}

	/**
	 * creates this if the kid is empty, or updates it if not empty
	 * @param ParserManager $manager
	 * @param GKA_Version $db_thing
	 *
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
				"INSERT INTO gokabam_api_versions(version,git_commit_id,git_tag,last_page_load_id) VALUES(?,?,?,?)",
					['sssi',$db_thing->text,$db_thing->git_commit_id,$db_thing->git_tag,$last_page_load_id],
					MYDB::LAST_ID,
					'ParseVersion::manage->insert'
				);

			$db_thing->kid = $manager->kid->generate_or_refresh_kid(
					$db_thing->kid,'gokabam_api_versions',$new_id,null);

		} else {
			//update this
			$id = $db_thing->kid->primary_id;

			if (empty($id)) {
				throw new ApiParseException("Internal code did not generate an id for update");
			}
			$manager->mydb->execSQL(
				"UPDATE gokabam_api_versions SET version = ?,git_commit_id = ?,git_tag=?,is_deleted = ?, last_page_load_id = ? WHERE id = ? ",
					['sssiii',$db_thing->text,$db_thing->git_commit_id,$db_thing->git_tag,$db_thing->delete,$last_page_load_id,$id],
					MYDB::ROWS_AFFECTED,
				'ParseVersion::manage->update'
				);
		}
		return $db_thing;
	}
}