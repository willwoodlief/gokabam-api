<?php
namespace gokabam_api;

class Fill_GKA_Use_Part_Connection {

	/**
	 * @param GKA_Use_Part_Connection $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @param integer $first_ts
	 * @param integer $last_ts
	 * @return GKA_Use_Part_Connection
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb, $first_ts, $last_ts) {

		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.is_deleted,
						
				a.parent_use_case_part_id,
				a.child_use_case_part_id,
				a.rank,
			  	
				a.md5_checksum,
				a.initial_page_load_id,
				a.last_page_load_id,
				p_first.version_id as initial_version,
				p_last.version_id as last_version,
				p_first.user_id as initial_user,
				p_last.user_id as last_user,
				UNIX_TIMESTAMP(p_first.created_at) as initial_ts,
				UNIX_TIMESTAMP(p_last.created_at) as last_ts
			FROM gokabam_api_use_case_part_connections a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			WHERE a.id = ? AND a.is_deleted = 0 AND UNIX_TIMESTAMP(p_last.created_at) between ? and ?",
			['iii',$root->kid->primary_id,$first_ts,$last_ts],
			MYDB::RESULT_SET,
			"@sey@primary.gka_use_part_connection.filler.php"
		);

		if (empty($res)) {
			return null;
		}

		$data = $res[0];

		$filler_manager->root_fill_helper($root,$data);

		///////// Finished with standard root fill ///////////////////


		$root->rank = $data->rank;


		$pos              = new GKA_Kid();
		$pos->primary_id  = $data->parent_use_case_part_id;
		$pos->table       = 'gokabam_api_use_case_parts';
		$root->source_part_kid = $pos;

		$pos              = new GKA_Kid();
		$pos->primary_id  = $data->child_use_case_part_id;
		$pos->table       = 'gokabam_api_use_case_parts';
		$root->destination_part_kid = $pos;





		return $root;

	}
}