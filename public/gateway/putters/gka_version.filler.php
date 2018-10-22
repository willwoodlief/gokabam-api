<?php
namespace gokabam_api;

class Fill_GKA_Version {

	/**
	 * @param GKA_Version $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @return GKA_Version
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb) {


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
				UNIX_TIMESTAMP(p_last.created_at) as last_ts
			FROM gokabam_api_versions a 
			LEFT JOIN gokabam_api_page_loads p_first ON p_first.id = a.initial_page_load_id
			LEFT JOIN gokabam_api_page_loads p_last ON p_last.id = a.last_page_load_id
			WHERE a.id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@primary.gka_version.filler.php"
		);

		if (empty($res)) {
			$class = get_class($root);
			throw new FillException("Did not find an object for $class, primary id of {$root->kid->primary_id}");
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

		return $root;

	}
}