<?php
namespace gokabam_api;

class Fill_GKA_Element {

	/**
	 * @param GKA_Element $root
	 * @param FillerManager $filler_manager
	 * @param MYDB $mydb
	 * @return GKA_Element
	 * @throws SQLException
	 * @throws FillException
	 */
	public static function fill($root,$filler_manager, $mydb) {


		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id,
				a.is_deleted,
				
				
				a.enum_values,
				a.default_value,
				a.is_nullable,
				a.data_min,
				a.data_max,
				a.data_multiple,
				a.data_precision,
				a.base_type_enum,
				a.format_enum,
				a.pattern,
				a.data_type_name,
				a.group_id,
				a.parent_element_id,
				a.is_optional,
				a.rank,
				a.radio_group,
				
				a.md5_checksum,
				a.initial_page_load_id,
				a.last_page_load_id,
				p_first.version_id as initial_version,
				p_last.version_id as last_version,
				p_first.user_id as initial_user,
				p_last.user_id as last_user,
				UNIX_TIMESTAMP(p_first.created_at) as initial_ts,
				UNIX_TIMESTAMP(p_last.created_at) as last_ts
			FROM gokabam_api_data_elements a 
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

		/*

	data_elements

		 */
		$root->enum_values = $data->enum_values;
		$root->default_value = $data->default_value;
			$root->is_nullable = $data->is_nullable;
			$root->min = $data->data_min;
			$root->max = $data->data_max;
			$root->multiple = $data->data_multiple;
			$root->precision = $data->data_precision;
			$root->type = $data->base_type_enum;
			$root->format = $data->format_enum;
			$root->pattern = $data->pattern;
			$root->text = $data->data_type_name;
			$root->is_optional = $data->is_optional;
			$root->rank = $data->rank;
			$root->radio_group = $data->radio_group;

			if ($data->group_id) {
				$parent = new GKA_Kid();
				$parent->primary_id = $data->group_id;
				$parent->table = 'gokabam_api_data_groups';
				$root->parent = $parent;
			}

			if ($data->parent_element_id) {
				$parent = new GKA_Kid();
				$parent->primary_id = $data->parent_element_id;
				$parent->table = 'gokabam_api_data_elements';
				$root->parent = $parent;
			}

			//get elements
		$res = $mydb->execSQL("
			SELECT 
				a.id,
				a.object_id
			FROM gokabam_api_data_elements a 
			WHERE a.parent_element_id = ? AND a.is_deleted = 0",
			['i',$root->kid->primary_id],
			MYDB::RESULT_SET,
			"@sey@elements.gka_element.filler.php"
		);

		if (!empty($res)) {
			foreach ( $res as $row ) {
				$pos              = new GKA_Kid();
				$pos->object_id   = $row->object_id;
				$pos->primary_id  = $row->id;
				$pos->table       = 'gokabam_api_data_elements';
				$root->elements[] = $pos;
			}
		}

		return $root;

	}
}