<?php
namespace gokabam_api;

class Fill_GKA_Version {

	/**
	 * @param GKA_Version $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @param integer $first_ts
	 * @param integer $last_ts
	 * @return GKA_Version
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb, $first_ts, $last_ts) {


		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.is_deleted,
				
				a.version,
				a.git_commit_id,
				a.git_tag,
				a.website_url,
				a.post_id,
				a.git_repo_url,
				
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
			FROM gokabam_api_versions a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			LEFT JOIN gokabam_api_versions v_first ON v_first.id = p_first.version_id
			LEFT JOIN gokabam_api_versions v_last ON v_last.id = p_last.version_id
			WHERE a.id = ? AND a.is_deleted = 0 AND ( (UNIX_TIMESTAMP(a.updated_at) between  ? and ?) OR (UNIX_TIMESTAMP(a.created_at) between  ? and ?) )",
			['iiiii',$root->kid->primary_id,$first_ts,$last_ts,$first_ts,$last_ts],
			MYDB::RESULT_SET,
			"@sey@primary.gka_version.filler.php"
		);

		if (empty($res)) {
			return null;
		}

		$data = $res[0];

		$filler_manager->root_fill_helper($root,$data);

		///////// Finished with standard root fill ///////////////////



		$root->text = $data->version;
		$root->git_commit_id = $data->git_commit_id;
		$root->git_tag = $data->git_tag;
		$root->website_url = $data->website_url;
		$root->post_id = $data->post_id;
		$root->git_repo_url = $data->git_repo_url;
		if ($root->post_id && $root->post_id > 0) {
			$root->post_title = get_the_title($root->post_id);
			$root->post_url = get_permalink($root->post_id);
		}



		return $root;

	}
}