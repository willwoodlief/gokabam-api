<?php
namespace gokabam_api;

class Fill_GKA_Input {

	/**
	 * @param GKA_Input $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @return GKA_Input
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb) {

		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.is_deleted,
						
				a.api_id,
				
				a.is_required,
				a.in_data_group_id,
				a.origin_enum,
				a.regex_string,
			  	
				a.md5_checksum,
				a.initial_page_load_id,
				a.last_page_load_id,
				p_first.version_id as initial_version,
				p_last.version_id as last_version,
				p_first.user_id as initial_user,
				p_last.user_id as last_user,
				UNIX_TIMESTAMP(p_first.created_at) as initial_ts,
				UNIX_TIMESTAMP(p_last.created_at) as last_ts
			FROM gokabam_api_inputs a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			WHERE a.id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@primary.gka_input.filler.php"
		);

		if (empty($res)) {
			$class = get_class($root);
			throw new FillException("Did not find an object for $class, primary id of {$root->kid->primary_id}");
		}

		$data = $res[0];

		$filler_manager->root_fill_helper($root,$data);

		///////// Finished with standard root fill ///////////////////

		$parent = new GKA_Kid();
		$parent->primary_id = $data->group_id;
		$parent->table = 'gokabam_api_apis';
		$root->parent = $parent;



		$pos              = new GKA_Kid();
		$pos->primary_id  = $data->in_data_group_id;
		$pos->table       = 'gokabam_api_data_groups';
		$root->data_groups[] = $pos;


		$root->origin = $data->origin_enum;
		$root->properties = $data->regex_string;
		$root->origin = $data->source_name;



		return $root;

	}
}