<?php
namespace gokabam_api;

class Fill_GKA_Use_Part {

	/**
	 * @param GKA_Use_Part $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @return GKA_Use_Part
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb) {

		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.is_deleted,
						
				a.out_data_group_id,
				a.in_data_group_id,
				a.in_api_id,
				a.use_case_id,
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
			FROM gokabam_api_use_case_parts a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			WHERE a.id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@primary.gka_use_part.filler.php"
		);

		if (empty($res)) {
			$class = get_class($root);
			throw new FillException("Did not find an object for $class, primary id of {$root->kid->primary_id}");
		}

		$data = $res[0];

		$filler_manager->root_fill_helper($root,$data);

		///////// Finished with standard root fill ///////////////////

		$parent = new GKA_Kid();
		$parent->primary_id = $data->use_case_id;
		$parent->table = 'gokabam_api_use_cases';
		$root->parent = $parent;

		$root->ref_id = $data->rank;


		$pos              = new GKA_Kid();
		$pos->primary_id  = $data->out_data_group_id;
		$pos->table       = 'gokabam_api_data_groups';
		$root->out_data_groups[] = $pos;

		$pos              = new GKA_Kid();
		$pos->primary_id  = $data->in_data_group_id;
		$pos->table       = 'gokabam_api_data_groups';
		$root->in_data_groups[] = $pos;


		$pos              = new GKA_Kid();
		$pos->primary_id  = $data->in_api_id;
		$pos->table       = 'gokabam_api_apis';
		$root->in_api_kid[] = $pos;


		//get sql
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id
			FROM gokabam_api_use_case_parts_sql a 
			WHERE a.use_case_part_id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@sqls.gka_use_part.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->primary_id  = $row->id;
				$pos->table       = 'gokabam_api_use_case_parts_sql';
				$root->sql_parts[] = $pos;
			}
		}


		//get connections
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				child_use_case_part_id,
				parent_use_case_part_id
				
			FROM gokabam_api_use_case_part_connections a 
			WHERE a.parent_use_case_part_id = ? OR a.child_use_case_part_id = ? AND a.is_deleted = 0",
			['ii',$root->kid->primary_id,$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@connections.gka_use_part.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$im_a_parent = $row->parent_use_case_part_id;
				$im_a_child =  $row->child_use_case_part_id;
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->table       = 'gokabam_api_use_case_part_connections';
				if ($im_a_child) {
					$pos->primary_id  = $im_a_child;
				}
				if ($im_a_parent) {
					$pos->primary_id = $im_a_parent;
				}


				$root->source_connections[] = $pos;
			}
		}

		return $root;

	}
}