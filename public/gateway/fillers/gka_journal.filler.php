<?php
namespace gokabam_api;

class Fill_GKA_Journal {

	/**
	 * @param GKA_Journal $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @param integer $first_ts
	 * @param integer $last_ts
	 * @return GKA_Journal
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb, $first_ts, $last_ts) {


		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.target_object_id,
				a.is_deleted,
				
				a.entry,
				
				a.md5_checksum,
				a.initial_page_load_id,
				a.last_page_load_id,
				p_first.version_id as initial_version,
				p_last.version_id as last_version,
				p_first.user_id as initial_user,
				p_last.user_id as last_user,
				UNIX_TIMESTAMP(p_first.created_at) as initial_ts,
				UNIX_TIMESTAMP(p_last.created_at) as last_ts
			FROM gokabam_api_journals a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			WHERE a.id = ? AND a.is_deleted = 0 AND UNIX_TIMESTAMP(p_last.created_at) between ? and ?",
			['iii',$root->kid->primary_id,$first_ts,$last_ts],
			MYDB::RESULT_SET,
			"@sey@primary.gka_journal.filler.php"
		);

		if (empty($res)) {
			return null;
		}

		$data = $res[0];

		$filler_manager->root_fill_helper($root,$data);

		///////// Finished with standard root fill ///////////////////

		$parent = new GKA_Kid();
		$parent->object_id = $data->target_object_id;
		$root->parent = $parent;
		$root->text = $data->tag_label;



		return $root;

	}
}