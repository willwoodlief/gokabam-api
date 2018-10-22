<?php
namespace gokabam_api;

class Fill_GKA_Use_Case {

	/**
	 * @param GKA_Use_Case $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @return GKA_Use_Case
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb) {

		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.is_deleted,
						
				a.belongs_to_api_id,
				a.belongs_to_api_version_id,
			  	
				a.md5_checksum,
				a.initial_page_load_id,
				a.last_page_load_id,
				p_first.version_id as initial_version,
				p_last.version_id as last_version,
				p_first.user_id as initial_user,
				p_last.user_id as last_user,
				UNIX_TIMESTAMP(p_first.created_at) as initial_ts,
				UNIX_TIMESTAMP(p_last.created_at) as last_ts
			FROM gokabam_api_use_cases a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			WHERE a.id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@primary.gka_use_case.filler.php"
		);

		if (empty($res)) {
			$class = get_class($root);
			throw new FillException("Did not find an object for $class, primary id of {$root->kid->primary_id}");
		}

		$data = $res[0];

		$filler_manager->root_fill_helper($root,$data);

		///////// Finished with standard root fill ///////////////////

		if ($data->belongs_to_api_id) {
			$parent = new GKA_Kid();
			$parent->primary_id = $data->belongs_to_api_id;
			$parent->table = 'gokabam_api_apis';
			$root->parent = $parent;
		}

		if ($data->belongs_to_api_version_id) {
			$parent = new GKA_Kid();
			$parent->primary_id = $data->belongs_to_api_version_id;
			$parent->table = 'gokabam_api_api_versions';
			$root->parent = $parent;
		}





		//get use case parts
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id
			FROM gokabam_api_use_case_parts a 
			WHERE a.use_case_id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@cases.gka_use_case.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->primary_id  = $row->id;
				$pos->table       = 'gokabam_api_use_case_parts';
				$root->use_parts[] = $pos;
			}
		}


		//get connections
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id
				
			FROM gokabam_api_use_case_part_connections a 
			WHERE a.use_case_id = ?  AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@y-connections.gka_use_case.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->table       = 'gokabam_api_use_case_part_connections';
				$pos->primary_id  = $row->id;

				$root->connections[] = $pos;
			}
		}

		return $root;

	}
}