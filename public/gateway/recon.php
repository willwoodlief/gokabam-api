<?php

namespace gokabam_api;

require_once    PLUGIN_PATH.'public/gateway/api-typedefs.php';
require_once    PLUGIN_PATH.'public/gateway/kid.php';
require_once    PLUGIN_PATH.'vendor/autoload.php';

//todo when an object is marked deleted, then mark all dependents as deleted too
//todo need to tie in initial and most recent page load (do next)

class Recon {
	/**
	 * @var MYDB $mydb
	 */
	protected $mydb = null;

	/**
	 * @var KidTalk $kid_talk
	 */
	protected $kid_talk = null;

	public function __construct( $mydb ) {

		$this->mydb = $mydb;
		$this->kid_talk = new KidTalk($mydb);
	}


	/**
	 *  @param GKA_Kid|string $kid_input
	 *  @return GKA_Root
	 *  @throws ApiParseException
	 * @throws SQLException
	 */
	public function spring($kid_input) {
		if (empty($kid_input)) {
			throw new ApiParseException("Kid is empty");
		}
		if (is_string($kid_input)) {
			$kid = $this->kid_talk->convert_parent_string_kid($kid_input);
		} else {
			if (get_class($kid_input)=== "gokabam_api\GKA_Kid") {
				$kid = $kid_input;
			} else {
				throw new ApiParseException("Kid input needs to be a string or a kid");
			}

		}

		$raw_data = $this->get_raw_data($kid);
		$obj = $this->create_object($kid,$raw_data);
		return $obj;

	}

	/**
	 *  @param GKA_Kid $kid
	 *  @return object
	 * @throws SQLException
	 * @throws ApiParseException
	 */
	protected function get_raw_data($kid) {
		$id = $kid->primary_id;

		switch ($kid->table) {
			case 'gokabam_api_words':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
							 id,
							 word_code_enum,
							  iso_639_1_language_code,
							  da_words
 							 FROM gokabam_api_words WHERE id = ?",
						['i',$id],
						MYDB::RESULT_SET,
						"@sey@Recon::parse->get_raw_data({$kid->table})"
						);
					if (!empty($res)) {
						return $res[0];
					} else {
						throw new ApiParseException("Cannot find a record in {$kid->table}/{$kid->primary_id} for {$kid->kid}");
					}

				}
			case 'gokabam_api_versions':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
						id,
						is_deleted,
						version,
						git_commit_id,
						git_tag
 							 FROM gokabam_api_versions WHERE id = ?",
						['i',$id],
						MYDB::RESULT_SET,
						"@sey@Recon::parse->get_raw_data({$kid->table})"
					);
					if (!empty($res)) {
						return $res[0];
					} else {
						throw new ApiParseException("Cannot find a record in {$kid->table}/{$kid->primary_id} for {$kid->kid}");
					}

				}
			case 'gokabam_api_tags':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								tag_label,
								tag_value,
								target_object_id
 							 FROM gokabam_api_tags WHERE id = ?",
						['i',$id],
						MYDB::RESULT_SET,
						"@sey@Recon::parse->get_raw_data({$kid->table})"
					);
					if (!empty($res)) {
						return $res[0];
					} else {
						throw new ApiParseException("Cannot find a record in {$kid->table}/{$kid->primary_id} for {$kid->kid}");
					}

				}
			case 'gokabam_api_journals':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								entry,
								target_object_id
 							 FROM gokabam_api_journals WHERE id = ?",
						['i',$id],
						MYDB::RESULT_SET,
						"@sey@Recon::parse->get_raw_data({$kid->table})"
					);
					if (!empty($res)) {
						return $res[0];
					} else {
						throw new ApiParseException("Cannot find a record in {$kid->table}/{$kid->primary_id} for {$kid->kid}");
					}

				}
			case 'gokabam_api_output_headers':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
						id,
						is_deleted,
						api_output_id,
						api_id,
						api_family_id,
						api_version_id,			
						header_name,
						header_value,
						out_data_group_id	
 							 FROM gokabam_api_output_headers WHERE id = ?",
						['i',$id],
						MYDB::RESULT_SET,
						"@sey@Recon::parse->get_raw_data({$kid->table})"
					);
					if (!empty($res)) {
						return $res[0];
					} else {
						throw new ApiParseException("Cannot find a record in {$kid->table}/{$kid->primary_id} for {$kid->kid}");
					}

				}
			case 'gokabam_api_family':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								api_version_id,
								hard_code_family_name
 							 FROM gokabam_api_family WHERE id = ?",
						['i',$id],
						MYDB::RESULT_SET,
						"@sey@Recon::parse->get_raw_data({$kid->table})"
					);
					if (!empty($res)) {
						return $res[0];
					} else {
						throw new ApiParseException("Cannot find a record in {$kid->table}/{$kid->primary_id} for {$kid->kid}");
					}

				}
			case 'gokabam_api_data_elements':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								group_id,
								parent_element_id,
								data_type_name,
								base_type_enum,
								format_enum,
								pattern,
								is_nullable,
								is_optional,
								enum_values,
								default_value,
								data_min,
								data_max,
								data_multiple,
								data_precision,
								rank,
								radio_group	
 							 FROM gokabam_api_data_elements WHERE id = ?",
						['i',$id],
						MYDB::RESULT_SET,
						"@sey@Recon::parse->get_raw_data({$kid->table})"
					);
					if (!empty($res)) {
						return $res[0];
					} else {
						throw new ApiParseException("Cannot find a record in {$kid->table}/{$kid->primary_id} for {$kid->kid}");
					}

				}
			case 'gokabam_api_api_versions':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								api_version
 							 FROM gokabam_api_api_versions WHERE id = ?",
						['i',$id],
						MYDB::RESULT_SET,
						"@sey@Recon::parse->get_raw_data({$kid->table})"
					);
					if (!empty($res)) {
						return $res[0];
					} else {
						throw new ApiParseException("Cannot find a record in {$kid->table}/{$kid->primary_id} for {$kid->kid}");
					}

				}
			default: {
				throw new ApiParseException("Reconstruct does not have code for {$kid->table}");
			}
		}
	}

	/**
	 * @param GKA_Kid $kid
	 * @param object $raw_data
	 * @return GKA_Root
	 */
	protected function create_object($kid,$raw_data) {
		return null;
	}
}
