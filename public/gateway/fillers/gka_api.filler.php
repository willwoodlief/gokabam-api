<?php
namespace gokabam_api;

class Fill_GKA_API {

	/**
	 * @param GKA_API $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @param integer $first_ts
	 * @param integer $last_ts
	 * @return GKA_API
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb, $first_ts, $last_ts) {

		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.is_deleted,
						
				a.api_family_id,
				a.method_call_enum,
				a.api_name,
			  	
				a.md5_checksum,
				a.initial_page_load_id,
				a.last_page_load_id,
				p_first.version_id as initial_version,
				p_last.version_id as last_version,
				p_first.user_id as initial_user,
				p_last.user_id as last_user,
				UNIX_TIMESTAMP(p_first.created_at) as initial_ts,
				UNIX_TIMESTAMP(p_last.created_at) as last_ts,
				v_first.object_id as initial_version_object_id,
				v_last.object_id as last_version_object_id
			FROM gokabam_api_apis a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			LEFT JOIN gokabam_api_versions v_first ON v_first.id = p_first.version_id
			LEFT JOIN gokabam_api_versions v_last ON v_last.id = p_last.version_id
			WHERE a.id = ? AND a.is_deleted = 0 AND ( (UNIX_TIMESTAMP(a.updated_at) between  ? and ?) OR (UNIX_TIMESTAMP(a.created_at) between  ? and ?) )",
			['iiiii',$root->kid->primary_id,$first_ts,$last_ts,$first_ts,$last_ts],
			MYDB::RESULT_SET,
			"@sey@primary.gka_api.filler.php"
		);

		if (empty($res)) {
			return null;
		}

		$data = $res[0];

		$filler_manager->root_fill_helper($root,$data);

		///////// Finished with standard root fill ///////////////////

		$parent = new GKA_Kid();
		$parent->primary_id = $data->api_family_id;
		$parent->table = 'gokabam_api_family';
		$root->parent = $parent;

		$root->method = $data->method_call_enum;
		$root->text = $data->api_name;


		//get headers
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id
			FROM gokabam_api_output_headers a 
			WHERE a.api_output_id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@headers.gka_api.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->primary_id  = $row->id;
				$pos->table       = 'gokabam_api_output_headers';
				$root->headers[] = $pos;
			}
		}


		//get inputs
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id
			FROM gokabam_api_inputs a 
			WHERE a.api_id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@inputs.gka_api.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->primary_id  = $row->id;
				$pos->table       = 'gokabam_api_inputs';
				$root->inputs[] = $pos;
			}
		}


		//get outputs
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id
			FROM gokabam_api_outputs a 
			WHERE a.api_id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@outputs.gka_api.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->primary_id  = $row->id;
				$pos->table       = 'gokabam_api_outputs';
				$root->outputs[] = $pos;
			}
		}


		//get outputs
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id
			FROM gokabam_api_use_cases a 
			WHERE a.belongs_to_api_id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@use_cases.gka_api.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->primary_id  = $row->id;
				$pos->table       = 'gokabam_api_use_cases';
				$root->use_cases[] = $pos;
			}
		}

		return $root;

	}
}