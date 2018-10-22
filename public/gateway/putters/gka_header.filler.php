<?php
namespace gokabam_api;

class Fill_GKA_Header {

	/**
	 * @param GKA_Header $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @return GKA_Header
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb) {

		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.is_deleted,
				
				a.header_name,
				a.header_value,
				a.api_family_id,
				a.api_id,
				a.api_output_id,
				a.api_version_id,
				a.out_data_group_id,
			  	
				a.md5_checksum,
				a.initial_page_load_id,
				a.last_page_load_id,
				p_first.version_id as initial_version,
				p_last.version_id as last_version,
				p_first.user_id as initial_user,
				p_last.user_id as last_user,
				UNIX_TIMESTAMP(p_first.created_at) as initial_ts,
				UNIX_TIMESTAMP(p_last.created_at) as last_ts
			FROM gokabam_api_output_headers a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			WHERE a.id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@primary.gka_element.filler.php"
		);

		if (empty($res)) {
			$class = get_class($root);
			throw new FillException("Did not find an object for $class, primary id of {$root->kid->primary_id}");
		}

		$data = $res[0];

		$filler_manager->root_fill_helper($root,$data);

		///////// Finished with standard root fill ///////////////////


		if ($data->api_output_id) {
			$parent = new GKA_Kid();
			$parent->primary_id = $data->api_output_id;
			$parent->table = 'gokabam_api_outputs';
			$root->parent = $parent;
		}

		if ($data->api_version_id) {
			$parent = new GKA_Kid();
			$parent->primary_id = $data->api_version_id;
			$parent->table = 'gokabam_api_versions';
			$root->parent = $parent;
		}

		if ($data->api_family_id) {
			$parent = new GKA_Kid();
			$parent->primary_id = $data->api_family_id;
			$parent->table = 'gokabam_api_family';
			$root->parent = $parent;
		}

		if ($data->api_id) {
			$parent = new GKA_Kid();
			$parent->primary_id = $data->api_id;
			$parent->table = 'gokabam_api_apis';
			$root->parent = $parent;
		}


		$pos              = new GKA_Kid();
		$pos->primary_id  = $data->out_data_group_id;
		$pos->table       = 'gokabam_api_data_groups';
		$root->data_groups[] = $pos;

		$root->value = $data->header_value;
		$root->name = $data->header_name;



		return $root;

	}
}