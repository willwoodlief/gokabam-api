<?php
namespace gokabam_api;
require_once realpath(dirname(__FILE__)) . '/../vendor/autoload.php';
require_once realpath(dirname(__FILE__)) . '/../lib/ErrorLogger.php';
require_once realpath(dirname(__FILE__)) . '/../lib/DBSelector.php';

# use Symfony\Component\Yaml\Yaml;



/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */

	const DB_VERSION = 0.135;


	/**
	 * @throws \Exception
	 */
	public static function activate() {
		global $wpdb,$GokabamGoodies;


		//check to see if any tables are missing
		$b_force_create = false;
		$tables_to_check= [
				ErrorLogger::getDBTableName(),
				'gokabam_api_object',
				'gokabam_api_object_history',
				'gokabam_api_versions',
				'gokabam_api_journal_containers',
				'gokabam_api_journal_container_tags',
				'gokabam_api_journals',
				'gokabam_api_journal_tags',
				'gokabam_api_data_elements',
				'gokabam_api_data_element_objects',
				'gokabam_api_data_groups',
				'gokabam_api_data_group_members',
				'gokabam_api_data_group_examples',
				'gokabam_api_data_converters',
				'gokabam_api_data_converter_parts',
				'gokabam_api_api_versions',
				'gokabam_api_family',
				'gokabam_api_apis',
				'gokabam_api_inputs',
				'gokabam_api_outputs',
				'gokabam_api_output_headers',
				'gokabam_api_use_cases',
				'gokabam_api_use_case_parts'
			]; //
		foreach ($tables_to_check as $table_name) {

			//check if table exists
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$b_force_create = true;
			}
		}

		$installed_ver = floatval( get_option( "_".strtolower( PLUGIN_NAME) ."_db_version" ));


		if ( ($b_force_create) || ( Activator::DB_VERSION > $installed_ver) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$charset_collate = $wpdb->get_charset_collate();



			//object
			$sql = "CREATE TABLE `gokabam_api_object` (
              id int NOT NULL AUTO_INCREMENT,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              user_id int DEFAULT NULL,
              primary_key int not null,
              table_name varchar(50),
              PRIMARY KEY  (id),
              KEY user_id_key (user_id),
              KEY primary_key_key (primary_key),
              KEY created_at_ts_key (created_at_ts),
              KEY table_name_key (table_name)
              ) $charset_collate;";

			dbDelta( $sql );


			$sql = "CREATE TABLE `gokabam_api_object_history` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              user_id int DEFAULT NULL,
              column_name varchar(50) not null ,
              value mediumtext DEFAULT NULL ,
              PRIMARY KEY  (id),
              UNIQUE KEY object_id_key (object_id),
              KEY column_name_key (column_name),
              KEY created_at_key (created_at)
              ) $charset_collate;";

			dbDelta( $sql );




			//create the db table
			$sql = ErrorLogger::getDBTableString();
			dbDelta( $sql );



			//versions
			$sql = "CREATE TABLE `gokabam_api_versions` (
              id int NOT NULL AUTO_INCREMENT,
              version varchar(255) not null ,
              version_name varchar(255) DEFAULT NULL ,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              commit_id varchar(255) DEFAULT NULL ,
              tag varchar(255) DEFAULT NULL,
              version_notes mediumtext DEFAULT NULL,
              PRIMARY KEY  (id),
              UNIQUE KEY version_key (version),
              KEY version_name_key (version_name)
              ) $charset_collate;";

			dbDelta( $sql );


			//journal containers
			$sql = "CREATE TABLE `gokabam_api_journal_containers` (
              id int NOT NULL AUTO_INCREMENT,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              container_notes text default NULL,
              PRIMARY KEY  (id),
              KEY created_at_key (created_at)
              ) $charset_collate;";

			dbDelta( $sql );



			//journal container tags
			$sql = "CREATE TABLE `gokabam_api_journal_container_tags` (
              id int NOT NULL AUTO_INCREMENT,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              journal_container_id int not null,
              tag varchar(255) default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY tag_key (tag)
              ) $charset_collate;";

			dbDelta( $sql );


			//journals
			$sql = "CREATE TABLE `gokabam_api_journals` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              version_id int not null,
              entry mediumtext DEFAULT NULL,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY created_at_ts_key (created_at_ts),
              KEY version_id_key (version_id)
              ) $charset_collate;";

			dbDelta( $sql );

			//journal tags
			$sql = "CREATE TABLE `gokabam_api_journal_tags` (
              id int NOT NULL AUTO_INCREMENT,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              journal_id int not null,
              tag varchar(255) default null,
              PRIMARY KEY  (id),
              KEY journal_id_key (journal_id),
              KEY tag_key (tag)
              ) $charset_collate;";

			dbDelta( $sql );




			$sql = "CREATE TABLE `gokabam_api_data_elements` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              is_object tinyint DEFAULT 0 not null,
              is_array tinyint DEFAULT 0 not null,
              array_data_element_id int DEFAULT null comment 'if not null, then this is an array',
              array_items_are_unique tinyint DEFAULT 0 not null, 
              data_min int default null comment 'only if numeric data or array',
              data_max int default null comment 'only if numeric data or array',
              data_multiple int default null comment 'only if numeric data', 
              data_type varchar(255) not null,
              data_name varchar(255) not null,
              enum_values mediumtext default null,
              data_description text default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY is_array_key (is_array), 
              KEY array_items_are_unique_key (array_items_are_unique),
              KEY is_object_key (is_object),
              KEY array_data_element_id_key (array_data_element_id),
              KEY data_type_key (data_type)
              ) $charset_collate;";

			dbDelta( $sql );



			$sql = "CREATE TABLE `gokabam_api_data_element_objects` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              data_element_id int NOT NULL,
              field_type_element_id  int NOT NULL,
              field_properties varchar(255) default null comment 'optional,required',
              field_name varchar(255) default null,
              field_description mediumtext default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY data_element_id_key (data_element_id),
              KEY field_type_element_id_key (field_type_element_id),
              KEY field_properties_key (field_properties)
              ) $charset_collate;";

			dbDelta( $sql );



			$sql = "CREATE TABLE `gokabam_api_data_groups` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              group_type varchar(255) default null comment 'database_table,params,data,user',
              group_category text default null comment 'a | delimited list of categories to visually display this',
              group_name varchar(255) default null,
              group_description mediumtext default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY group_type_key (group_type)
              ) $charset_collate;";

			dbDelta( $sql );


			$sql = "CREATE TABLE `gokabam_api_data_group_members` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              group_id int not null,
              data_element_id int not null,
              rank int not null default 0,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY group_id_key (group_id),
              KEY data_element_id_key (data_element_id),
              KEY rank_key (rank)
              ) $charset_collate;";

			dbDelta( $sql );


			$sql = "CREATE TABLE `gokabam_api_data_group_examples` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              group_id int not null,
              json_example mediumtext not null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY group_id_key (group_id)
              ) $charset_collate;";

			dbDelta( $sql );

			$sql = "CREATE TABLE `gokabam_api_data_converters` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              converter_type varchar(255) default null comment 'algorithm,placeholder',
              converter_description mediumtext not null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY converter_type_key (converter_type)
              ) $charset_collate;";

			dbDelta( $sql );



			$sql = "CREATE TABLE `gokabam_api_data_converter_parts` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              data_converter_id int not null,
			  in_data_element_id int not null,
			  out_data_element_id int not null,
              rank int not null default 0,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY data_converter_id_type_key (data_converter_id),
              KEY in_data_element_id_key (in_data_element_id),
              KEY out_data_element_id_key (out_data_element_id),
              KEY rank_key (rank)
              ) $charset_collate;";

			dbDelta( $sql );


			// gokabam_api_api_versions

			$sql = "CREATE TABLE `gokabam_api_api_versions` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              version_id int not null ,
              api_version varchar(255) DEFAULT NULL ,
              api_version_name varchar(255) DEFAULT NULL ,
              api_version_notes mediumtext DEFAULT NULL,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              UNIQUE KEY api_version_key (api_version),
              UNIQUE KEY api_version_name_key (api_version_name),
              KEY version_id_key (version_id)
              ) $charset_collate;";

			dbDelta( $sql );



			$sql = "CREATE TABLE `gokabam_api_family` (
              id int NOT NULL AUTO_INCREMENT,
              api_version int not null,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,	
              family_name varchar(255) default null,
              family_blurb varchar(255) default null,
              family_description mediumtext default null,
              PRIMARY KEY  (id),
              KEY api_version_key (api_version),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY family_name_key (family_name),
              KEY family_blurb_key (family_blurb)
              ) $charset_collate;";

			dbDelta( $sql );


			//call in and out mime types are json
			$sql = "CREATE TABLE `gokabam_api_apis` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              method_call varchar(255) not null comment 'POST, GET, etc',
              api_version_id int not null,
              api_family_id int not null,
              api_name varchar(255) default null,
              api_blurb varchar(255) default null,
              api_overview mediumtext default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY api_version_id_key (api_version_id),
              KEY api_family_id_key (api_family_id),
              KEY api_name_key (api_name),
              KEY api_blurb_key (api_blurb)
              ) $charset_collate;";

			dbDelta( $sql );

			$sql = "CREATE TABLE `gokabam_api_inputs` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              api_id int not null,
              is_required tinyint DEFAULT 0 not null,
              origin varchar(255) not null comment 'url,query,body,header',
              source_name varchar(255) default null comment 'if header then name of header, if url then pattern in url', 
              source_mime varchar(255) default null comment 'has to map to json',
              in_data_group_id int default null,
              input_overview mediumtext default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY api_id_key (api_id),
              KEY is_required_key (is_required),
              KEY origin_key (origin),
              KEY source_name_key (source_name),
              KEY in_data_group_id_key (in_data_group_id)
              ) $charset_collate;";

			dbDelta( $sql );

			$sql = "CREATE TABLE `gokabam_api_outputs` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              api_id int not null,
              http_return_code int not null,
              out_data_group_id int default null,
              output_overview mediumtext default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY api_id_key (api_id),
              KEY http_return_code_key (http_return_code),
              KEY out_data_group_id_key (out_data_group_id)
              ) $charset_collate;";

			dbDelta( $sql );

			$sql = "CREATE TABLE `gokabam_api_output_headers` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              api_id int default null comment 'optionally associated with any return only for this api call',
              api_output_id int default null  comment 'optinally tied to a specific return type',
              out_data_group_id int default null,
              output_header_overview mediumtext default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY api_id_key (api_id),
              KEY api_output_id_key (api_output_id),
              KEY out_data_group_id_key (out_data_group_id)
              ) $charset_collate;";

			dbDelta( $sql );




			$sql = "CREATE TABLE `gokabam_api_use_cases` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              api_version_id int not null,
              api_family_id int not null,
              use_case_name varchar(255) default null,
              use_case_overview mediumtext default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY api_version_id_key (api_version_id),
              KEY api_family_id_key (api_family_id),
              KEY use_case_name_key (use_case_name)
              ) $charset_collate;";

			dbDelta( $sql );


			$sql = "CREATE TABLE `gokabam_api_use_case_parts` (
              id int NOT NULL AUTO_INCREMENT,
              journal_container_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              deleted_at_ts int default null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              user_case_id int not null,
              is_starting_point tinyint not null default 0,
              is_ending_point tinyint not null default 0,
              in_data_group_example_id int default null comment 'if starting point, this can be the data it gives to the next step(s)',
              in_api_input_id int default null comment 'more than one in can be used, but must have a parent going to each one',
              source_for_api_input int default null comment 'another user case part same use case id which will feed its output to the header',
              out_api_ouput_id int default null comment 'if chosen all other outs are null',
              out_api_output_header_id  int default null comment 'if chosen all other outs are null',
              out_data_group_id  int default null comment 'if chosen all other outs are null',
              out_conversion_group_id  int default null comment 'if chosen all other outs are null',
              use_case_part_name varchar(255) default null,
              use_case_part_overview mediumtext default null,
              PRIMARY KEY  (id),
              KEY journal_container_id_key (journal_container_id),
              KEY is_deleted_key (is_deleted),
              KEY user_case_id_key (user_case_id),
              KEY is_starting_point_key (is_starting_point),
              KEY is_ending_point_key (is_ending_point),
              KEY in_data_group_example_id_key (in_data_group_example_id),
              KEY in_api_input_id_key (in_api_input_id),
              KEY out_api_ouput_id_key (out_api_ouput_id),
              KEY out_api_output_header_id_key (out_api_output_header_id),
              KEY out_data_group_id_key (out_data_group_id),
              KEY out_conversion_group_id_key (out_conversion_group_id),
              KEY source_for_api_input_key (source_for_api_input)
              ) $charset_collate;";

			dbDelta( $sql );

			$mydb = DBSelector::getConnection('wordpress');
			$check = $mydb->execSQL("SHOW KEYS FROM  gokabam_api_journals WHERE Key_name='entry_key';");
			if (empty($check)) {
				$mydb->execSQL("ALTER TABLE  gokabam_api_journals ADD FULLTEXT entry_key (entry);");
			}

			update_option( "_".strtolower( PLUGIN_NAME) ."_db_version" , Activator::DB_VERSION );
		}


	}



}
