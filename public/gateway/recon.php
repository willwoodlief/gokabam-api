<?php

namespace gokabam_api;

require_once    PLUGIN_PATH.'public/gateway/api-typedefs.php';
require_once    PLUGIN_PATH.'public/gateway/kid.php';
require_once    PLUGIN_PATH.'vendor/autoload.php';




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
		global $GokabamGoodies;

		$this->mydb = $mydb;
		$this->kid_talk = $GokabamGoodies->get_kid_talk();
	}


	/**
	 * returns an object retrieved from the kid
	 *  @param GKA_Kid|string $kid_input
	 * @param integer $extraction_level default 1
	 *      1, just the object
	 *      2, kids filled in of its immediate children
	 *      3, kids filled in of words, tags and journals
	 *  @return GKA_Root

	 *  @throws ApiParseException
	 * @throws SQLException
	 */
	public function spring($kid_input,$extraction_level=1) {
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
		$obj = $this->create_object($kid,$raw_data,$extraction_level);

		if ($extraction_level > 2) {
			//add in tags , words, and journals
			$res = $this->mydb->execSQL(
				"SELECT  w.id as pk ,w.object_id as ok, 'gokabam_api_words' as da_table
  						FROM gokabam_api_words w WHERE w.target_object_id = ?
                    UNION
  					  SELECT  t.id as pk ,t.object_id as ok, 'gokabam_api_tags' as da_table
  						FROM gokabam_api_tags t WHERE t.target_object_id = ?
  					UNION
  					  SELECT  t.id as pk ,t.object_id as ok, 'gokabam_api_journals' as da_table
  						FROM gokabam_api_journals t WHERE t.target_object_id = ?		
  						
  						",
				['iii',$kid->object_id,$kid->object_id,$kid->object_id],
				MYDB::RESULT_SET,
				"@sey@Recon::parse->spring_tags_words_journals({$kid->table})"
			);
			if ($res) {
				foreach ($res as $row) {
					switch ($row->da_table) {
						case 'gokabam_api_tags' : {
							$hid = new GKA_Kid();
							$hid->object_id = $row->ok;
							$hid->primary_id = $row->pk;
							$hid->table = $row->da_table;
							$obj->tags[] = $hid;
							break;
						}
						case 'gokabam_api_journals' : {
							$hid = new GKA_Kid();
							$hid->object_id = $row->ok;
							$hid->primary_id = $row->pk;
							$hid->table = $row->da_table;
							$obj->journals[] = $hid;
							break;
						}
						case 'gokabam_api_words' : {
							$hid = new GKA_Kid();
							$hid->object_id = $row->ok;
							$hid->primary_id = $row->pk;
							$hid->table = $row->da_table;
							$obj->words[] = $hid;
							break;
						}
						default: {
							throw new ApiParseException("Unrecognized da_table in extration level 2");
						}
					}
				}
			}
		}

		//fill in all kids fully
		//there are no deeply nested arrays here, so can just look at all the properties,
		// see if they are
		//   GKA_Kid object
		//   array of GKA_kid
		//   object that has a property named kid which is of type GKA_Kid
		// for each found edit that object in place

		$objects_to_help = [];
		foreach ($obj as $key => $value) {

			if (is_object($value)) {
				if ( strcmp(get_class($value),"gokabam_api\GKA_Kid") === 0 ) {
					$objects_to_help[] = $value;
				} else {
					if (property_exists($value,'kid')) {
						if ( strcmp(get_class($value->kid),"gokabam_api\GKA_Kid") === 0 ) {
							$objects_to_help[] = $value->kid;
						}
					}
				}
			}
			if (is_array($value)) {
				foreach ($value as $array_key => $array_value) {
					if ( strcmp(get_class($array_value),"gokabam_api\GKA_Kid") === 0 ) {
						$objects_to_help[] = $array_value;
					} else {
						if (property_exists($array_value,'kid')) {
							if ( strcmp(get_class($array_value->kid),"gokabam_api\GKA_Kid") === 0 ) {
								$objects_to_help[] = $array_value->kid;
							}
						}
					}
				}
			}
		}

		foreach ($objects_to_help as $pkid) {
			$this->kid_talk->fill_kids_in($pkid);
		}
		return $obj;

	}

	/**
	 *  @param GKA_Kid $kid
	 * @param integer $extraction_level default 1
	 *      1, just the object
	 *      2, kids filled in of its immediate children
	 *      3, kids filled in of words, tags and journals
	 *  @return object
	 * @throws SQLException
	 * @throws ApiParseException
	 */
	protected function get_raw_data($kid,$extraction_level=1) {
		$id = $kid->primary_id;
		ErrorLogger::unused_params($extraction_level);
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
						post_id,
						git_commit_id,
						git_tag,
						git_repo_url,
						website_url
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
			case 'gokabam_api_use_cases':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								belongs_to_api_version_id,
								belongs_to_api_id
	                             FROM gokabam_api_use_cases WHERE id = ?",
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
			case 'gokabam_api_use_case_parts':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								use_case_id,
								in_api_id,
								rank
	                             FROM gokabam_api_use_case_parts WHERE id = ?",
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
			case 'gokabam_api_use_case_part_connections':
			{
				$res = $this->mydb->execSQL(
					"SELECT 
							id,
							is_deleted,
							use_case_id,
							last_page_load_id,
							initial_page_load_id,
							parent_use_case_part_id,
							child_use_case_part_id,
							rank 
		                             FROM gokabam_api_use_case_part_connections WHERE id = ?",
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

			case 'gokabam_api_use_case_parts_sql':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								use_case_part_id ,
								last_page_load_id ,
								sql_part_enum ,
								table_element_id ,
								reference_table_element_id ,
								outside_element_id ,
								ranking ,
								constant_value 
 							 FROM gokabam_api_use_case_parts_sql WHERE id = ?",
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
			case 'gokabam_api_outputs':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
						id,
						is_deleted,
						api_id,
						http_return_code,
						out_data_group_id	
 							 FROM gokabam_api_outputs WHERE id = ?",
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
			case 'gokabam_api_inputs':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
						id,
						is_deleted,
						api_id,
						origin_enum,
						regex_string,
						in_data_group_id	
 							 FROM gokabam_api_inputs WHERE id = ?",
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
			case 'gokabam_api_apis':
				{
					$res = $this->mydb->execSQL(
						"SELECT 
								id,
								is_deleted,
								api_family_id,
								method_call_enum,
								api_name
 							 FROM gokabam_api_apis WHERE id = ?",
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
			case 'gokabam_api_data_groups': {
				$res = $this->mydb->execSQL(
					"SELECT 
								g.id,
								g.is_deleted,
								g.group_type_enum,
								IF(w.id,w.id,0) as is_use_case_in,
								IF(p.id,p.id,0) as is_use_case_out,
								IF(o.id,o.id,0) as is_output,
								IF(i.id,i.id,0) as is_input,
								IF(h.id,h.id,0) as is_header
 							 FROM gokabam_api_data_groups g
 							  LEFT JOIN gokabam_api_output_headers h ON h.out_data_group_id = g.id 
 							  LEFT JOIN gokabam_api_inputs i ON i.in_data_group_id = g.id 
 							  LEFT JOIN gokabam_api_outputs o ON o.out_data_group_id = g.id 
 							  LEFT JOIN gokabam_api_use_case_parts p ON p.out_data_group_id = g.id 
 							  LEFT JOIN gokabam_api_use_case_parts w ON w.in_data_group_id = g.id  
 							  WHERE g.id = ?",
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
			case 'gokabam_api_data_group_examples': {
				$res = $this->mydb->execSQL(
					"SELECT 
								id,
								is_deleted,
								json_example,
								group_id
 							 FROM gokabam_api_data_group_examples WHERE id = ?",
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
	 * @param object $data
	 * @param integer $extraction_level 1, how many levels to get the information for nested objects
	 *      default level 1
	 *      1, just the object
	 *      2, kids filled in of its immediate children
	 *      3, kids filled in of words, tags and journals
	 * @return GKA_Root
	 * @throws SQLException
	 * @throws ApiParseException
	 */
	protected function create_object($kid,$data,$extraction_level=1) {
		ErrorLogger::unused_params($extraction_level);
		switch ($kid->table) {
			case 'gokabam_api_words':
				{
					$obj = new GKA_Word();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->type = $data->word_code_enum;
					$obj->language = $data->iso_639_1_language_code;
					$obj->text = $data->da_words;

					return $obj;
				}
			case 'gokabam_api_versions':
				{
					$obj = new GKA_Version();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->text = $data->version ;
					$obj->post_id = $data->post_id ;
					$obj->git_commit_id = $data->git_commit_id ;
					$obj->git_tag = $data->git_tag ;
					$obj->git_repo_url = $data->git_repo_url ;
					$obj->website_url = $data->website_url ;
					return $obj;

				}
			case 'gokabam_api_tags':
				{
					$obj = new GKA_Tag();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->text = $data->tag_label ;
					$obj->value = $data->tag_value ;
					$obj->parent = new GKA_Kid();
					$obj->parent->object_id = $data->target_object_id ;

					return $obj;

				}
			case 'gokabam_api_journals':
				{
					$obj = new GKA_Journal();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->text = $data->entry ;
					$obj->parent = new GKA_Kid();
					$obj->parent->object_id = $data->target_object_id ;
					return $obj;

				}

			case 'gokabam_api_use_cases':
				{

					$obj = new GKA_Use_Case();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;

					$obj->parent = new GKA_Kid();
					if ($data->belongs_to_api_version_id) {
						$obj->parent->primary_id = $data->belongs_to_api_version_id ;
						$obj->parent->table = 'gokabam_api_api_versions';
					} elseif ($data->belongs_to_api_id) {
						$obj->parent->primary_id = $data->belongs_to_api_id ;
						$obj->parent->table = 'gokabam_api_apis';
					}



					//fill in use case parts

					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_use_case_parts WHERE use_case_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_parts_on_cases(gokabam_api_use_cases)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_use_case_parts';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->use_parts[] = $what->kid;
						}
					}


					//fill in connections

					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_use_case_part_connections WHERE use_case_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_connections_on_cases(gokabam_api_use_cases)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_use_case_part_connections';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->connections[] = $what->kid;
						}
					}




				}

			case 'gokabam_api_use_case_parts':
				{

					$obj = new GKA_Use_Part();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;

					$obj->parent = new GKA_Kid();
					$obj->parent->primary_id = $data->use_case_id ;
					$obj->parent->table = 'gokabam_api_use_cases';

					$obj->in_api = new GKA_Kid();
					$obj->in_api->object_id = $data->in_api ;
					$obj->in_api->table = 'gokabam_api_apis';


					$obj->ref_id = $data->rank ;


					//fill in sql connections

					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_use_case_parts_sql WHERE use_case_part_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_sql_on_parts(gokabam_api_use_case_parts)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_use_case_parts_sql';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->sql_parts[] = $what->kid;
						}
					}

					if ($data->out_data_group_id) {
						$data_group = new GKA_Kid();
						$data_group->table = 'gokabam_api_data_groups';
						$data_group->primary_id = $data->out_data_group_id;
						$this->kid_talk->fill_kids_in($data_group);
						$obj->out_data_groups = [$data_group->kid];
					}

					if ($data->in_data_group_id) {
						$data_group = new GKA_Kid();
						$data_group->table = 'gokabam_api_data_groups';
						$data_group->primary_id = $data->in_data_group_id;
						$this->kid_talk->fill_kids_in($data_group);
						$obj->in_data_groups = [$data_group->kid];
					}

					$res = $this->mydb->execSQL("SELECT id,object_id from gokabam_api_use_case_part_connections WHERE parent_use_case_part_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::ROWS_AFFECTED,
						'@sey@Recon::create_object->get_out_connections_on_parts(gokabam_api_use_case_parts)'
					);
					if ($res) {
						foreach ($res as $row) {
							$con_id = $row->id;
							$conKid = new GKA_Kid();
							$conKid->primary_id = $con_id;
							$conKid->table = 'gokabam_api_use_case_part_connections';
							$conKid->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($conKid);
							$obj->source_connections[] = $conKid;
						}
					}

					return $obj;

				}
			case 'gokabam_api_use_case_part_connections':
			{
				$obj = new GKA_Use_Part_Connection();
				$obj->kid = $kid;
				$obj->delete = $data->is_deleted;

				$obj->parent = new GKA_Kid();
				$obj->parent->primary_id = $data->use_case_id ;
				$obj->parent->table = 'gokabam_api_use_cases';

				$obj->destination_part = new GKA_Kid();
				$obj->destination_part->object_id = $data->child_use_case_part_id ;
				$obj->destination_part->table = 'gokabam_api_use_case_parts';

				$obj->source_part = new GKA_Kid();
				$obj->source_part->object_id = $data->parent_use_case_part_id ;
				$obj->source_part->table = 'gokabam_api_use_case_parts';

				$obj->rank = $data->ranking ;

				return $obj;


			}

			case 'gokabam_api_use_case_parts_sql':
				{
					$obj = new GKA_SQL_Part();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;

					$obj->parent = new GKA_Kid();
					$obj->parent->primary_id = $data->use_case_part_id ;
					$obj->parent->table = 'gokabam_api_use_case_parts';

					$obj->db_element = new GKA_Kid();
					$obj->db_element->object_id = $data->table_element_id ;
					$obj->db_element->table = 'gokabam_api_data_elements';

					$obj->reference_db_element = new GKA_Kid();
					$obj->reference_db_element->object_id = $data->reference_table_element_id ;
					$obj->reference_db_element->table = 'gokabam_api_data_elements';

					$obj->outside_element = new GKA_Kid();
					$obj->outside_element->object_id = $data->outside_element_id ;
					$obj->outside_element->table = 'gokabam_api_data_elements';

					$obj->text = $data->constant_value ;
					$obj->sql_part_enum = $data->sql_part_enum ;
					$obj->rank = $data->ranking ;

					return $obj;

				}
			case 'gokabam_api_output_headers':
				{
					$obj = new GKA_Header();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->name = $data->header_name ;
					$obj->value = $data->header_value ;
					$obj->parent = new GKA_Kid();

					if ($data->api_version_id) {
						$obj->parent->table = 'gokabam_api_api_versions';
						$obj->parent->primary_id = $data->api_version_id;
					}

					if ($data->api_family_id) {
						$obj->parent->table = 'gokabam_api_family';
						$obj->parent->primary_id = $data->api_family_id;
					}

					if ($data->api_id) {
						$obj->parent->table = 'gokabam_api_apis';
						$obj->parent->primary_id = $data->api_id;
					}

					if ($data->api_output_id) {
						$obj->parent->table = 'gokabam_api_outputs';
						$obj->parent->primary_id = $data->api_output_id;
					}

					if ($data->out_data_group_id) {
						$data_group = new GKA_Kid();
						$data_group->table = 'gokabam_api_data_groups';
						$data_group->primary_id = $data->out_data_group_id;
						$this->kid_talk->fill_kids_in($data_group);
						$obj->data_groups = [$data_group->kid];
					}


					return $obj;


				}
			case 'gokabam_api_outputs':
				{
					$obj = new GKA_Output();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->http_code = $data->http_return_code ;
					$obj->parent = new GKA_Kid();
					$obj->parent->table = 'gokabam_api_apis';
					$obj->parent->primary_id = $data->api_id;


					if ($data->out_data_group_id) {
						$data_group = new GKA_Kid();
						$data_group->table = 'gokabam_api_data_groups';
						$data_group->primary_id = $data->in_data_group_id;
						$this->kid_talk->fill_kids_in($data_group);
						$obj->data_groups = [$data_group->kid];
					}

					//fill in headers
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_output_headers WHERE api_output_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_headers_on_outputs(gokabam_api_outputs)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_output_headers';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->headers[] = $what->kid;
						}
					}

					return $obj;
				}
			case 'gokabam_api_inputs':
				{
					$obj = new GKA_Input();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->properties = $data->regex_string ;
					$obj->origin = $data->origin_enum ;
					$obj->parent = new GKA_Kid();
					$obj->parent->table = 'gokabam_api_apis';
					$obj->parent->primary_id = $data->api_id;


					if ($data->in_data_group_id) {
						$data_group = new GKA_Kid();
						$data_group->table = 'gokabam_api_data_groups';
						$data_group->primary_id = $data->in_data_group_id;
						$this->kid_talk->fill_kids_in($data_group);
						$obj->data_groups = [$data_group->kid];
					}

					return $obj;
				}
			case 'gokabam_api_apis':
				{
					$obj = new GKA_API();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->text = $data->api_name ;
					$obj->method = $data->method_call_enum ;
					$obj->parent = new GKA_Kid();
					$obj->parent->table = 'gokabam_api_family';
					$obj->parent->primary_id = $data->api_version_id;


					//fill in inputs
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_inputs WHERE api_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_inputs_on_apis(gokabam_api_apis)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_inputs';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->inputs[] = $what->kid;
						}
					}

					//fill in outputs
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_outputs WHERE api_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_outputs_on_apis(gokabam_api_apis)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_outputs';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->outputs[] = $what->kid;
						}
					}


					//fill in headers
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_output_headers WHERE api_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_headers_on_apis(gokabam_api_apis)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_output_headers';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->headers[] = $what->kid;
						}
					}


					//fill in use cases
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_use_cases WHERE belongs_to_api_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_cases_on_apis(gokabam_api_apis)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_use_cases';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->use_cases[] = $what->kid;
						}
					}

					return $obj;

				}
			case 'gokabam_api_family':
				{
					$obj = new GKA_Family();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->text = $data->hard_code_family_name ;
					$obj->parent = new GKA_Kid();
					$obj->parent->table = 'gokabam_api_api_versions';
					$obj->parent->primary_id = $data->api_version_id;


					//fill in apis
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_apis WHERE api_family_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_apis_on_family(gokabam_api_family)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_apis';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->apis[] = $what->kid;
						}
					}


					//fill in headers
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_output_headers WHERE api_family_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_headers_on_family(gokabam_api_family)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_output_headers';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->headers[] = $what->kid;
						}
					}

					return $obj;

				}
			case 'gokabam_api_data_groups': {

				$obj = new GKA_DataGroup();
				$obj->kid = $kid;
				$obj->delete = $data->is_deleted;
				$obj->type = $data->group_type_enum ;
				$obj->parent = new GKA_Kid();


				if ($data->is_use_case_in) {
					$obj->parent->table = 'gokabam_api_use_case_parts';
					$obj->parent->primary_id = $data->is_use_case_in;
				} elseif ($data->is_use_case_out) {
					$obj->parent->table = 'gokabam_api_use_case_parts';
					$obj->parent->primary_id = $data->is_use_case_out;
				} elseif ($data->is_output) {
					$obj->parent->table = 'gokabam_api_outputs';
					$obj->parent->primary_id = $data->is_output;
				} elseif ($data->is_input) {
					$obj->parent->table = 'gokabam_api_inputs';
					$obj->parent->primary_id = $data->is_input;
				} elseif ($data->is_header) {
					$obj->parent->table = 'gokabam_api_output_headers';
					$obj->parent->primary_id = $data->is_header;
				} else {
					// no parent
					$obj->parent = null;
				}


				//fill in elements
				$res = $this->mydb->execSQL(
					"SELECT 
							id, object_id
                         FROM gokabam_api_data_elements WHERE group_id = ?",
					['i',$obj->kid->primary_id],
					MYDB::RESULT_SET,
					"@sey@Recon::create_object->get_elements_on_group(gokabam_api_data_groups)"
				);

				if ($res) {
					foreach ($res as $row) {
						$what = new GKA_Kid();
						$what->table ='gokabam_api_data_elements';
						$what->primary_id = $row->id;
						$what->object_id = $row->object_id;
						$this->kid_talk->fill_kids_in($what);
						$obj->elements[] = $what->kid;
					}
				}


				//fill in examples
				$res = $this->mydb->execSQL(
					"SELECT 
							id, object_id
                         FROM gokabam_api_data_group_examples WHERE group_id = ?",
					['i',$obj->kid->primary_id],
					MYDB::RESULT_SET,
					"@sey@Recon::create_object->get_examples_on_group(gokabam_api_data_groups)"
				);

				if ($res) {
					foreach ($res as $row) {
						$what = new GKA_Kid();
						$what->table ='gokabam_api_data_group_examples';
						$what->primary_id = $row->id;
						$what->object_id = $row->object_id;
						$this->kid_talk->fill_kids_in($what);
						$obj->examples[] = $what->kid;
					}
				}

				return $obj;


			}
			case 'gokabam_api_data_group_examples': {


				$obj = new GKA_DataExample();
				$obj->kid = $kid;
				$obj->delete = $data->is_deleted;
				$obj->text = $data->json_example ;
				$obj->parent = new GKA_Kid();
				$obj->parent->table = 'gokabam_api_data_groups';
				$obj->parent->primary_id = $data->group_id;

				return $obj;
			}

			case 'gokabam_api_data_elements':
				{
					$obj = new GKA_Element();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->parent = new GKA_Kid();

					if ($data->group_id) {
						$obj->parent->table = 'gokabam_api_data_groups';
						$obj->parent->primary_id = $data->group_id;
					}

					if ($data->parent_element_id) {
						$obj->parent->table = 'gokabam_api_data_elements';
						$obj->parent->primary_id = $data->parent_element_id;
					}

					$obj->text = $data->data_type_name ;
					$obj->type = $data->base_type_enum ;
					$obj->format = $data->format_enum ;
					$obj->pattern = $data->pattern ;
					$obj->is_nullable = $data->is_nullable ;
					$obj->is_optional = $data->is_optional ;
					$obj->enum_values = $data->enum_values ;
					$obj->default_value = $data->default_value ;
					$obj->min = $data->data_min ;
					$obj->max = $data->data_max ;
					$obj->multiple = $data->data_multiple ;
					$obj->precision = $data->data_precision ;
					$obj->rank = $data->rank ;
					$obj->radio_group = $data->radio_group ;
					$obj->elements = [];


					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_data_elements WHERE parent_element_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_element_members(gokabam_api_data_elements)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_data_elements';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->elements[] = $what->kid;
						}
					}


					return $obj;

				}
			case 'gokabam_api_api_versions':
				{
					$obj = new GKA_API_Version();
					$obj->kid = $kid;
					$obj->delete = $data->is_deleted;
					$obj->text = $data->api_version ;



					//fill in headers
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_output_headers WHERE api_version_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_headers_on_api_version(gokabam_api_api_versions)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_output_headers';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->headers[] = $what->kid;
						}
					}



					//fill in families
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_family WHERE api_version_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_families_on_api_version(gokabam_api_api_versions)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_output_headers';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->families[] = $what->kid;
						}
					}

					//fill in use cases
					$res = $this->mydb->execSQL(
						"SELECT 
							id, object_id
                         FROM gokabam_api_use_cases WHERE belongs_to_api_version_id = ?",
						['i',$obj->kid->primary_id],
						MYDB::RESULT_SET,
						"@sey@Recon::create_object->get_cases_on_api_version(gokabam_api_api_versions)"
					);

					if ($res) {
						foreach ($res as $row) {
							$what = new GKA_Kid();
							$what->table ='gokabam_api_use_cases';
							$what->primary_id = $row->id;
							$what->object_id = $row->object_id;
							$this->kid_talk->fill_kids_in($what);
							$obj->use_cases[] = $what->kid;
						}
					}

					return $obj;

				}
			default: {
				throw new ApiParseException("Reconstruct does not have code for {$kid->table}");
			}
		}
	}
}
