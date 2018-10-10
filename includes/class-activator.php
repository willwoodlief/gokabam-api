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

	const DB_VERSION = 0.183;
	/*
	 * Change Log
	 * .180     gokabam_api_page_loads now has user roles and name, microtime, and more git info
	 * .181     use case parts cannot have duplicate ranks
	 * .182     added connection table for use case parts, this is a full object which can be annotated and tagged and
	 *          journaled. It also updates the parts when they are connected. Took out the old children string from the parts
	 * .183     Added reason and error_log_id to page_loads
	 */


	/**
	 * @throws \Exception
	 */
	public static function activate() {
		global $wpdb;
		$mydb = DBSelector::getConnection('wordpress');
		$table_prefix = $wpdb->base_prefix;

		$b_safety_swith = false; // turn to true while working on this
		if ($b_safety_swith) return ;

		//check to see if any tables are missing
		$b_force_create = false;

		$installed_ver = floatval( get_option( "_".strtolower( PLUGIN_NAME) ."_db_version" ));


		if ( ($b_force_create) || ( Activator::DB_VERSION > $installed_ver) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$charset_collate = $wpdb->get_charset_collate();
			$mydb->dropTriggersLike('gokabam_api');

			//create the db table
			$sql = ErrorLogger::getDBTableString();
			dbDelta( $sql );

###########################################################################################################################################
/*
 *              gokabam_api_objects
 *
 *
 */
###########################################################################################################################################
			//object
			$sql = "CREATE TABLE `gokabam_api_objects` (
              id int NOT NULL AUTO_INCREMENT,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              primary_key int default null,
              da_table_name varchar(50) not null,
              PRIMARY KEY  (id),
              KEY primary_key_key (primary_key),
              KEY created_at_key (created_at),
              KEY table_name_key (da_table_name),
              UNIQUE KEY one_per_key (primary_key,da_table_name)
              ) $charset_collate;";

			dbDelta( $sql );



###########################################################################################################################################
/*
 *              gokabam_api_history
 *
 *
 */
###########################################################################################################################################


			//history
			$sql = "CREATE TABLE `gokabam_api_history` (
              id int NOT NULL AUTO_INCREMENT,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              reference_name varchar(255) default null,	
              stored_json mediumtext,
              PRIMARY KEY  (id),
              KEY created_at_key (created_at)
              ) $charset_collate;";

			dbDelta( $sql );

###########################################################################################################################################
/*
 *              gokabam_api_page_loads
 *
 *
 */
###########################################################################################################################################

			// gokabam_api_page_loads
			$sql = "CREATE TABLE `gokabam_api_page_loads` (
              id int NOT NULL AUTO_INCREMENT,
              user_id int default null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              start_micro_time double default null comment 'php micro time',
              stop_micro_time double default null comment 'can be filled in afterwards',
              ip VARBINARY(16) default null,
              is_git_dirty tinyint default 0,
              error_log_id int default null comment 'optional link to an error report',
              git_commit_hash varchar(50) default null,
              git_branch varchar(50) default null,
              person_name varchar(255) default null,
              user_roles varchar(255) default null,
              reason text default null,
              PRIMARY KEY  (id),
              KEY created_at_key (created_at),
              KEY user_id_key (user_id),
              KEY start_micro_time_key (start_micro_time),
              KEY stop_micro_time_key (stop_micro_time)
              ) $charset_collate;";

			dbDelta( $sql );

			$error_log_table_name = $table_prefix . 'gokabam_api_error_logs';
			if (!$mydb->foreignKeyExists('fk_page_load_can_reference_error_log_id')) {
				/** @noinspection SqlResolve */
				$mydb->execute( "ALTER TABLE gokabam_api_page_loads ADD CONSTRAINT fk_page_load_can_reference_error_log_id 
										FOREIGN KEY (error_log_id) REFERENCES $error_log_table_name(id);" );
			}


###########################################################################################################################################
/*
 *              gokabam_api_change_log
 *

 */
###########################################################################################################################################

			//edit logs
			$sql = "CREATE TABLE `gokabam_api_change_log` (
					id int NOT NULL AUTO_INCREMENT,
					target_object_id int not null,
					page_load_id int default null,
					is_tags tinyint default null comment 'togged if dependent tags changed',
					is_words tinyint default null comment 'togged if dependent words changed',
					is_element_objects tinyint default null comment 'togged if dependent objects changed',
					is_elements tinyint default null comment 'togged if dependent elements changed',
					is_group_members tinyint default null comment 'togged if dependent members changed',
					is_groups tinyint default null comment 'togged if dependent groups changed',
					is_examples tinyint default null comment 'togged if dependent examples changed',
					is_families tinyint default null comment 'togged if dependent changed',
					is_apis tinyint default null comment 'togged if dependent changed',
					is_headers tinyint default null comment 'togged if dependent changed',
					is_inputs tinyint default null comment 'togged if dependent changed',
					is_outputs tinyint default null comment 'togged if dependent changed',
					is_sql_parts tinyint default null comment 'togged if dependent changed',
					is_use_case_parts tinyint default null comment 'toggled if dependent use case parts changed',
					is_use_case_part_connection tinyint default null comment 'toggled if dependent use case part connections changed',
					edit_action varchar(6) not null DEFAULT 'edit' comment 'insert|edit|delete',
					created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY  (id),
					KEY object_id_key (target_object_id),
					KEY page_load_id_key (page_load_id),
					KEY created_at_key (created_at),
					KEY edit_action_key (edit_action)
              ) $charset_collate;";

			dbDelta( $sql );

			if (!$mydb->foreignKeyExists('fk_change_log_has_target_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_change_log ADD CONSTRAINT fk_change_log_has_target_object_id 
										FOREIGN KEY (target_object_id) REFERENCES gokabam_api_objects(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_change_log_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_change_log ADD CONSTRAINT fk_change_log_has_page_load_id 
										FOREIGN KEY (page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}



###########################################################################################################################################
/*
 *              gokabam_api_change_log_edit_history
 *
 *
 */
###########################################################################################################################################
			//edit history

			$sql = "CREATE TABLE `gokabam_api_change_log_edit_history` (
              id int NOT NULL AUTO_INCREMENT,
              change_log_id int not null,
              da_edited_column_name varchar(35) not null,
              da_edited_old_column_value mediumtext default null comment 'only non framework columns',
              PRIMARY KEY  (id),
              KEY change_log_id_key (change_log_id),
              KEY da_column_name (da_edited_column_name),
              UNIQUE KEY keep_it_down_key (change_log_id,da_edited_column_name)
              ) $charset_collate;";

			dbDelta( $sql );

			if (!$mydb->foreignKeyExists('fk_edit_history_has_change_log_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_change_log_edit_history ADD CONSTRAINT fk_edit_history_has_change_log_id 
										FOREIGN KEY (change_log_id) REFERENCES gokabam_api_change_log(id);' );
			}





###########################################################################################################################################
/*
 *              gokabam_api_words
 *  stores multilingual text as well as front end json data
 *
 */
###########################################################################################################################################

			//words
			$sql = "CREATE TABLE `gokabam_api_words` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              target_object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        	  iso_639_1_language_code char(2) not null default 'en',
        	  word_code_enum varchar(15) not null comment 'name,title,blurb,description,overview,data',
              da_words mediumtext DEFAULT NULL,
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              PRIMARY KEY  (id),
              UNIQUE KEY object_id_key (object_id),
              KEY target_object_id_key (target_object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY iso_639_1_language_code_key (iso_639_1_language_code),
              KEY word_code_enum_key (word_code_enum),
              UNIQUE KEY only_one_here_key (target_object_id,iso_639_1_language_code,word_code_enum)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_words_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_words ADD CONSTRAINT fk_words_has_object_id 
										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_words_has_target_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_words ADD CONSTRAINT fk_words_has_target_object_id 
										FOREIGN KEY (target_object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_words_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_words ADD CONSTRAINT fk_words_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

###########################################################################################################################################
/*
 *              gokabam_api_versions
 *
 *
 */
###########################################################################################################################################

			//versions
			$sql = "CREATE TABLE `gokabam_api_versions` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              version varchar(255) not null ,
              git_commit_id varchar(255) DEFAULT NULL ,
              git_tag varchar(255) DEFAULT NULL,
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              UNIQUE KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              UNIQUE KEY version_key (version)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_versions_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_versions ADD CONSTRAINT fk_versions_has_object_id 
										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_versions_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_versions ADD CONSTRAINT fk_versions_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}







###########################################################################################################################################
/*
 *              gokabam_api_journals
 *
 *
 */
###########################################################################################################################################
			//journals
			$sql = "CREATE TABLE `gokabam_api_journals` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              target_object_id int not null,
              last_page_load_id int default null,
              version_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              entry mediumtext DEFAULT NULL comment 'this is not meant to be multilingual so stores here',
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY target_object_id_key (target_object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY is_deleted_key (is_deleted),
              KEY created_at_key (created_at),
              KEY version_id_key (version_id)
              ) $charset_collate;";

			dbDelta( $sql );

			$check = $mydb->execSQL("SHOW KEYS FROM  gokabam_api_journals WHERE Key_name='entry_key';");
			if (empty($check)) {
				$mydb->execSQL("ALTER TABLE  gokabam_api_journals ADD FULLTEXT entry_key (entry);");
			}

			if (!$mydb->foreignKeyExists('fk_journals_has_target_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_journals ADD CONSTRAINT fk_journals_has_target_object_id
 										FOREIGN KEY (target_object_id) REFERENCES gokabam_api_objects(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_journals_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_journals ADD CONSTRAINT fk_journals_has_object_id 
										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_journals_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_journals ADD CONSTRAINT fk_journals_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_journals_has_version_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_journals ADD CONSTRAINT fk_journals_has_version_id 
										FOREIGN KEY (version_id) REFERENCES gokabam_api_versions(id);' );
			}






###########################################################################################################################################
/*
 *              gokabam_api_data_elements
 *
 table for primitives with their constraints
a primitive has a base of
	string : pattern|format
	integer: min, max, multiple
	number :precision
	boolean

prim:
	base_type: string|integer|number|boolean|object|array
	format: date|date-time|password|byte|binary|email|uri|float|double|int32|int8|int64|use_pattern
	pattern: if this is used, then the string representation has to fit this
	precision: only if number
	multiples: only if integer
	min  number value or length of string
	max: number value or length of string
	is_nullable: any
	is_optional: any
	field_name:

//if is array then this is the number of allowed types
collection:
	parent: prim
	child: prim
	rank:
	is_optional:


if is parent, can never be a child
and if child, can never be a parent
Note: if need nesting of equivalent things over and under then make a duplicate

 */
###########################################################################################################################################
			$sql = "CREATE TABLE `gokabam_api_data_elements` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              is_nullable tinyint DEFAULT 0 not null comment 'any type can use this',
              data_min int default null comment 'if integer or number means min value, if array means min length, if string means min size',
              data_max int default null comment 'if integer or number means min value, if array means min length, if string means min size',
              data_multiple float default null comment 'only if numeric data, the data should only be multiples of this', 
              data_precision int default null comment 'only for numeric data',
              base_type_enum varchar(10) not null comment 'string|integer|number|boolean|object|array',
              format_enum varchar(10) not null comment 'date|date-time|password|byte|binary|email|uri|float|double|int32|int8|int64|use_pattern',
              pattern varchar(255) default null comment 'if this is used, then the string representation has to fit this',
              data_type_name varchar(255) not null comment 'single word type',
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_element_objects varchar(255) default null comment 'checksum for all objects in this element',
              md5_checksum_words varchar(255) default null,
              default_value text default null comment 'a single default value is optional',
              enum_values mediumtext default null comment 'if this is filled out, then element is a string enumeration',
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY is_deleted_key (is_deleted),
              KEY base_type_enum_key (base_type_enum),
              KEY format_enum_key (format_enum)	
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_data_elements_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_elements ADD CONSTRAINT fk_data_elements_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_elements_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_elements ADD CONSTRAINT fk_data_elements_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}





###########################################################################################################################################
/*
*              gokabam_api_data_element_objects
*

*/
###########################################################################################################################################

			$sql = "CREATE TABLE `gokabam_api_data_element_objects` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              target_data_element_id int NOT NULL,
              description_data_element_id  int NOT NULL,
              last_page_load_id int default null,
              rank int default 0 NOT NULL comment 'how this is ordered in the viewing',
              is_optional tinyint DEFAULT 0 not null comment 'if this element can be skipped in the array type or object',	
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              radio_group varchar(255) default null comment 'anything in the name group can only have one thing picked',
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY is_deleted_key (is_deleted),
              KEY target_data_element_id_key (target_data_element_id),
              KEY description_data_element_id_key (description_data_element_id),
              KEY is_optional_key (is_optional),
              KEY rank_key (rank)
              ) $charset_collate;";

			dbDelta( $sql );




			if (!$mydb->foreignKeyExists('fk_data_element_objects_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_element_objects ADD CONSTRAINT fk_data_element_objects_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_element_objects_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_element_objects ADD CONSTRAINT fk_data_element_objects_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_element_objects_has_target_data_element_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_element_objects ADD CONSTRAINT fk_data_element_objects_has_target_data_element_id 
										FOREIGN KEY (target_data_element_id) REFERENCES gokabam_api_data_elements(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_element_objects_has_description_data_element_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_element_objects ADD CONSTRAINT fk_data_element_objects_has_description_data_element_id 
										FOREIGN KEY (description_data_element_id) REFERENCES gokabam_api_data_elements(id);' );
			}



			//triggers are below the data groups



###########################################################################################################################################
/*
*              gokabam_api_data_groups
*
*
*/
###########################################################################################################################################


			$sql = "CREATE TABLE `gokabam_api_data_groups` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              group_type_enum varchar(20) default null comment 'database_table,regular',
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_group_members varchar(255) default null ,
              md5_checksum_elements varchar(255) default null comment 'checksum of the element it contains',
              md5_checksum_examples varchar(255) default null ,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY is_deleted_key (is_deleted),
              KEY group_type_enum_key (group_type_enum)
              ) $charset_collate;";

			dbDelta( $sql );

			if (!$mydb->foreignKeyExists('fk_data_groups_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_groups ADD CONSTRAINT fk_data_groups_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_groups_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_groups ADD CONSTRAINT fk_data_groups_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}




###########################################################################################################################################
/*
*              gokabam_api_data_group_members
*
*
*/
###########################################################################################################################################


			$sql = "CREATE TABLE `gokabam_api_data_group_members` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              group_id int not null,
              data_element_id int not null,
              rank int not null default 0,
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY is_deleted_key (is_deleted),	
              KEY group_id_key (group_id),
              KEY data_element_id_key (data_element_id),
              KEY rank_key (rank)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_data_group_members_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_group_members ADD CONSTRAINT fk_data_group_members_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_group_members_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_group_members ADD CONSTRAINT fk_data_group_members_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_group_members_has_group_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_group_members ADD CONSTRAINT fk_data_group_members_has_group_id 
										FOREIGN KEY (group_id) REFERENCES gokabam_api_data_groups(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_data_group_members_has_in_element_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_group_members ADD CONSTRAINT fk_data_group_members_has_in_element_id 
										FOREIGN KEY (data_element_id) REFERENCES gokabam_api_data_elements(id);' );
			}



###########################################################################################################################################
/*
*              gokabam_api_data_group_examples
*
*
*/
###########################################################################################################################################


			$sql = "CREATE TABLE `gokabam_api_data_group_examples` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              group_id int not null,
              json_example mediumtext not null,
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY is_deleted_key (is_deleted),
              KEY group_id_key (group_id)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_data_group_examples_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_group_examples ADD CONSTRAINT fk_data_group_examples_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_group_examples_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_group_examples ADD CONSTRAINT fk_data_group_examples_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_data_group_examples_has_group_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_data_group_examples ADD CONSTRAINT fk_data_group_examples_has_group_id 
										FOREIGN KEY (group_id) REFERENCES gokabam_api_data_groups(id);' );
			}



###########################################################################################################################################
/*
*              gokabam_api_api_versions
*
*
*/
###########################################################################################################################################


			// gokabam_api_api_versions

			$sql = "CREATE TABLE `gokabam_api_api_versions` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              api_version varchar(255) DEFAULT NULL ,
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_families varchar(255) default null,
              md5_checksum_headers varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              UNIQUE KEY api_version_key (api_version)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_api_versions_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_api_versions ADD CONSTRAINT fk_api_versions_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_versions_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_api_versions ADD CONSTRAINT fk_api_versions_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}


###########################################################################################################################################
/*
*              gokabam_api_family
*
*
*/
###########################################################################################################################################



			$sql = "CREATE TABLE `gokabam_api_family` (
              id int NOT NULL AUTO_INCREMENT,
              api_version_id int not null,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              hard_code_family_name varchar(255) not null,	
              md5_checksum varchar(255) default null, 
              md5_checksum_tags varchar(255) default null,
              md5_checksum_apis varchar(255) default null,
              md5_checksum_headers varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY api_version_key (api_version_id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY is_deleted_key (is_deleted),
			  KEY hard_code_family_name_key (hard_code_family_name)	
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_api_family_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_family ADD CONSTRAINT fk_api_family_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_family_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_family ADD CONSTRAINT fk_api_family_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_api_family_has_api_version_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_family ADD CONSTRAINT fk_api_family_has_api_version_id 
										FOREIGN KEY (api_version_id) REFERENCES gokabam_api_api_versions(id);' );
			}


###########################################################################################################################################
/*
*              gokabam_api_apis
*
*
*/
###########################################################################################################################################


			//call in and out mime types are json
			$sql = "CREATE TABLE `gokabam_api_apis` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              api_family_id int not null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              method_call_enum varchar(255) not null comment 'get,put,post,delete,options,head,patch,trace',
              api_name varchar(255) default null comment 'the code name, not the decription name',
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_inputs varchar(255) default null,
              md5_checksum_outputs varchar(255) default null,
              md5_checksum_headers varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY is_deleted_key (is_deleted),
              KEY api_family_id_key (api_family_id),
              KEY api_name_key (api_name)
              ) $charset_collate;";

			dbDelta( $sql );

			if (!$mydb->foreignKeyExists('fk_apis_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_apis ADD CONSTRAINT fk_apis_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_apis_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_apis ADD CONSTRAINT fk_apis_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_apis_has_family_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_apis ADD CONSTRAINT fk_apis_has_family_id 
										FOREIGN KEY (api_family_id) REFERENCES gokabam_api_family(id);' );
			}


###########################################################################################################################################
/*
*              gokabam_api_inputs
*

	an api can have 0 or more inputs
*/
###########################################################################################################################################



			$sql = "CREATE TABLE `gokabam_api_inputs` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              api_id int not null comment '',
              in_data_group_id int default null comment 'the data maps to what is defined in this',
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              is_required tinyint DEFAULT 0 not null comment 'not supported right now, all inputs are required',
              origin_enum varchar(255) not null comment 'url,query,body,header',
              source_name varchar(255) default null comment 'if header then name of header, if query name of key, if url then pattern in url, if body and not null then top level json key', 
              source_body varchar(255) default null comment 'not supported right now, everything is assumed json',
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_groups varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY api_id_key (api_id),
              KEY in_data_group_id_key (in_data_group_id),
              KEY is_deleted_key (is_deleted),
              KEY is_required_key (is_required),
              KEY origin_enum_key (origin_enum),
              KEY source_name_key (source_name)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_api_inputs_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_inputs ADD CONSTRAINT fk_api_inputs_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_inputs_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_inputs ADD CONSTRAINT fk_api_inputs_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_inputs_has_api_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_inputs ADD CONSTRAINT fk_api_inputs_has_api_id 
										FOREIGN KEY (api_id) REFERENCES gokabam_api_apis(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_inputs_has_group_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_inputs ADD CONSTRAINT fk_api_inputs_has_group_id 
										FOREIGN KEY (in_data_group_id) REFERENCES gokabam_api_data_groups(id);' );
			}


###########################################################################################################################################
/*
*              gokabam_api_outputs
*
*
*/
###########################################################################################################################################


			$sql = "CREATE TABLE `gokabam_api_outputs` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              api_id int not null,
              http_return_code int not null,
              out_data_group_id int default null,
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_groups varchar(255) default null,
              md5_checksum_headers varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY api_id_key (api_id),
              KEY out_data_group_id_key (out_data_group_id),
              KEY http_return_code_key (http_return_code),
              KEY is_deleted_key (is_deleted)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_api_outputs_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_outputs ADD CONSTRAINT fk_api_outputs_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_outputs_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_outputs ADD CONSTRAINT fk_api_outputs_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_outputs_has_api_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_outputs ADD CONSTRAINT fk_api_outputs_has_api_id 
										FOREIGN KEY (api_id) REFERENCES gokabam_api_apis(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_outputs_has_group_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_outputs ADD CONSTRAINT fk_api_outputs_has_group_id 
										FOREIGN KEY (out_data_group_id) REFERENCES gokabam_api_data_groups(id);' );
			}

###########################################################################################################################################
/*
*              gokabam_api_output_headers
*
*
*/
###########################################################################################################################################



			$sql = "CREATE TABLE `gokabam_api_output_headers` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              api_version_id int default null comment 'optionally associated with any return only for this version',
              api_family_id int default null comment 'optionally associated with any return only for this family',	
              api_id int default null comment 'optionally associated with any return only for this api call',
              api_output_id int default null  comment 'optinally tied to a specific return type',
              out_data_group_id int default null comment 'for non static headers',
              header_name varchar(255) not null comment 'the name of the header',
              header_value text not null comment 'the contents/value of the header can have regex groups with names that match the out data group',
              header_value_regex text default null comment 'not supported, the value has the placeholders',
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_groups varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY api_id_key (api_id),
              KEY out_data_group_id_key (out_data_group_id),
              KEY api_family_id_key (api_family_id),
              KEY api_version_key (api_version_id),
              KEY api_output_id_key (api_output_id),
              KEY is_deleted_key (is_deleted)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_api_output_headers_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_output_headers ADD CONSTRAINT fk_api_output_headers_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_output_headers_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_output_headers ADD CONSTRAINT fk_api_output_headers_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_output_headers_has_api_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_output_headers ADD CONSTRAINT fk_api_output_headers_has_api_id 
										FOREIGN KEY (api_id) REFERENCES gokabam_api_apis(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_output_headers_has_group_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_output_headers ADD CONSTRAINT fk_api_output_headers_has_group_id 
										FOREIGN KEY (out_data_group_id) REFERENCES gokabam_api_data_groups(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_output_headers_has_family_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_output_headers ADD CONSTRAINT fk_api_output_headers_has_family_id 
										FOREIGN KEY (api_family_id) REFERENCES gokabam_api_family(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_output_headers_has_api_version_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_output_headers ADD CONSTRAINT fk_api_output_headers_has_api_version_id 
										FOREIGN KEY (api_version_id) REFERENCES gokabam_api_api_versions(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_output_headers_has_api_ouput_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_output_headers ADD CONSTRAINT fk_api_output_headers_has_api_ouput_id 
										FOREIGN KEY (api_output_id) REFERENCES gokabam_api_outputs(id);' );
			}



###########################################################################################################################################
/*
*              gokabam_api_use_cases
*
*
*/
###########################################################################################################################################



			$sql = "CREATE TABLE `gokabam_api_use_cases` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              belongs_to_api_version_id int default null comment 'used if api id is not set (becomes a general use case)',
              belongs_to_api_id int default null comment 'if selected then only belongs to the api, but if this is null then just belongs to the version',
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_use_case_parts varchar(255) default null comment 'parts',
              md5_checksum_apis varchar(255) default null comment 'changes of api it belongs to',
              md5_checksum_families varchar(255) default null comment 'changes of family it belongs to',
              md5_checksum_words varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY api_version_id_key (belongs_to_api_version_id),
              KEY api_id_key (belongs_to_api_id),
              KEY is_deleted_key (is_deleted)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_api_use_case_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_cases ADD CONSTRAINT fk_api_use_case_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_cases ADD CONSTRAINT fk_api_use_case_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_has_api_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_cases ADD CONSTRAINT fk_api_use_case_has_api_id 
										FOREIGN KEY (belongs_to_api_version_id) REFERENCES gokabam_api_apis(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_api_use_case_has_api_version_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_cases ADD CONSTRAINT fk_api_use_case_has_api_version_id 
										FOREIGN KEY (belongs_to_api_id) REFERENCES gokabam_api_api_versions(id);' );
			}




###########################################################################################################################################
/*
 *


				sql



					table of sql.parts
						 (select,from,join,where,limit,offset,ordering)
						select -> table_element_id
						from   -> table_element_id
						joins  ->  start_table_element_id, end_table_element_id, ranking
						where  ->   table_element_id, description, ranking,outside_element_id or constant
						limits ->   outside_element_id or constant
						offset ->   outside_element_id or constant
						ordering ->  table_element_id, direction (use constant column to mark direction), ranking

						table structure
						  sql_id, sql_part,table_element_id,reference_table_element_id,outside_element_id,constant,ranking

							trigger rules:
								 sql_id is the use case part id, the use case must be a sql statement
								 only elements in groups that have the designation database table can assigned in the table element id
								 only elements in the use case inputs can be assigned in the outside element id

						Note: when the gui is displaying the database tables, the foreign keys are calculated only from here
								and show the direction the fk is going
						enum: select,from,joins,where,limit,offset,ordering

*              gokabam_api_use_case_parts
*
*how it works:
	these are used to show the flow inside an api, to show how the outside interacts with one particular api, to show how a system of api work

1) inside an api there are algorithms and database operations

	inside an api there are two building blocks which can be snapped together in any way
		algorithms have four parts
					an input group
					a description of what is happening, this is not connected to any groups right now just words
					an output group

					the algorithm is a use case part by itself

		sql statement
				a database table is a data group, there can be several tables
					a db group has a list of elements which can connect one way to another db group


				database operations reference one or more groups that are marked as database tables


				list of select (table group.element)
				list of from (list of table groups)
				list of joins starting with the from and including all the table groups in select
					if not organized with all connections enabled will show up as broken in gui
				list of where (table group.element) with text besides each where describing it and element from input group
				limits mapped to element from input group
				offsets mapped to element from input group
				ordering (list of by table group.element and direction)

				so the inputs in the database block is a group which is used as part of the statement
				and the output is a group made from the select statement (using the elements of the table groups selected)

				all the database stuff is stored as an sql parts table which is a child of the use case part which describes the sql statement
				and has a description

		both of these

			has a list of 0 to many children
				can have 0 to many children (each child needs to have its inputs met by at least a subset of the parent's output)
				children can be recursive as algorithms can have many children and not all paths are taken at same time
					(so can show loops)
			are marked by an input group
			have an output group

		* the gui will show if there are any missing things between the api inputs and the total of the block inputs
		* children can have more than one parent

		a group of these in the api must have outputs that match all the api outputs and headers
			there can be extra outputs, but at a minimal there needs to be a flow from in to out which covers all elements
			* a to do list will show up if there is missing
		note:
			* if the combined inputs of the inner workings of the api do not match the inputs of the api itself, a note will show up with a to do list
			* if the combined outputs of the inner workings of the api do not match all the outputs of the api itself, a note will show up with a to do list

outside an api
		all the blocks act in the same way:
			they have their display properties and names set by tags
			the process is started by a group data example
			but inputs after that can be defined by one or more:
					1) an arbitrary group id
					2) an api input
				if one or more inputs need to be merged then children can have more than one parent

			outputs are defined as below, one or the other must be used
				1) an arbitrary group
				2) an api output, but this is only if an api was input

			a list of 0 to many children: each child must have inputs that match a subset of any combination of parent output

Note for all types:
	if the children need inputs not covered by the outputs, it is not enforced by the db, but instead will be shown on screen to fix
	inputs are different for the internal api stuff vs the general
		internal always does groups
		general always starts with use cases and then goes to groups

table structure for all types
	in_data_group
	in_data_group_example
	in_api (all inputs to this api)

	out_api (all outputs from this api)
	out_data_group

	children (text field delimited by |  trigger makes sure only valid ids from other parts in the same use case are in there,
				and make sure a use case part cannot change its parent, parents can be shared )
	name
	blurb
	description
*/
###########################################################################################################################################



			$sql = "CREATE TABLE `gokabam_api_use_case_parts` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              use_case_id int not null comment 'use case that this belongs to',
              in_data_group_id int default null comment 'used by any type',
              in_api_id int default null comment 'can only be used by generic_node, locks out other inputs',
              out_data_group_id  int default null comment 'all types can use this, if there is an in_api then this cannot be used because that apis output will be used instead ',
              rank int default 0 comment 'used to help organize this outside the db',
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,  
              md5_checksum varchar(255) default null,
              md5_checksum_sql_parts varchar(255) default null,
              md5_checksum_groups varchar(255) default null,
              md5_checksum_apis varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_use_case_connection varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id), 
              KEY last_page_load_id_key (last_page_load_id), 
              KEY user_case_id_key (use_case_id) ,	
              KEY in_data_group_id_key (in_data_group_id), 
              KEY in_api_id_key (in_api_id), 
              KEY out_data_group_id_key (out_data_group_id), 
              KEY is_deleted_key (is_deleted),
              UNIQUE KEY no_dupe_ranks (rank,use_case_id)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_api_use_case_part_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts ADD CONSTRAINT fk_api_use_case_part_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_part_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts ADD CONSTRAINT fk_api_use_case_part_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_part_has_api_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts ADD CONSTRAINT fk_api_use_case_part_has_api_id 
										FOREIGN KEY (in_api_id) REFERENCES gokabam_api_apis(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_part_has_use_case_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts ADD CONSTRAINT fk_api_use_case_part_has_use_case_id 
										FOREIGN KEY (use_case_id) REFERENCES gokabam_api_use_cases(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_api_use_case_part_has_group_in_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts ADD CONSTRAINT fk_api_use_case_part_has_group_in_id 
										FOREIGN KEY (in_data_group_id) REFERENCES gokabam_api_data_groups(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_part_has_group_out_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts ADD CONSTRAINT fk_api_use_case_part_has_group_out_id 
										FOREIGN KEY (out_data_group_id) REFERENCES gokabam_api_data_groups(id);' );
			}

###############################################################################
#
#           gokabam_api_use_case_part_connections
			// describes the connections between the use parts
#
###############################################################################


			$sql = "CREATE TABLE `gokabam_api_use_case_part_connections` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              parent_use_case_part_id int not null comment 'use case part which is the parent',
              child_use_case_part_id int not null comment 'use case part which is the child, both of these must be in the same use case',
              rank int default 0 comment 'used to help organize this outside the db, does not need to be set and defaults to 0',
              is_deleted tinyint DEFAULT 0 not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,	  
              md5_checksum varchar(255) default null,
              md5_checksum_words varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id), 
              KEY last_page_load_id_key (last_page_load_id), 
              KEY parent_use_case_part_id_key (parent_use_case_part_id) ,	
              KEY child_use_case_part_id_key (child_use_case_part_id), 
              KEY rank_key (rank),
              KEY is_deleted_key (is_deleted)
              ) $charset_collate;";

			dbDelta( $sql );

			if (!$mydb->foreignKeyExists('fk_api_use_case_parts_connection_has_parent_part_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_part_connections ADD CONSTRAINT fk_api_use_case_parts_connection_has_parent_part_id 
										FOREIGN KEY (parent_use_case_part_id) REFERENCES gokabam_api_use_case_parts(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_parts_connection_has_child_part_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_part_connections ADD CONSTRAINT fk_api_use_case_parts_connection_has_child_part_id 
										FOREIGN KEY (child_use_case_part_id) REFERENCES gokabam_api_use_case_parts(id);' );
			}


###########################################################################################################################################
/*
*              gokabam_api_use_case_parts_sql
*
*               see notes above:
 *          use_case_part_id, sql_part_enum,table_element_id,reference_table_element_id,outside_element_id,constant,ranking
*/
###########################################################################################################################################

			$sql = "CREATE TABLE `gokabam_api_use_case_parts_sql` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              last_page_load_id int default null,
              use_case_part_id int not null comment 'use case that this belongs to',
              sql_part_enum varchar(10) not null comment 'select,from,joins,where,limit,offset,ordering',
              table_element_id int default null comment 'element from group id that has type marked as db_table (is not part of use case)',
              reference_table_element_id int default null comment 'element from group id that has type marked as db_table (is not part of use case)',
              outside_element_id int default null comment 'element from group id that is an input of the use case part this belongs to',
              ranking int default null comment 'orders these within their subcategories like where, joins',
              is_deleted tinyint DEFAULT 0 not null,
              constant_value text default null comment 'when a number or value (instead of an element) is needed in the where,limit or offset. If used cannot use reference element', 
              md5_checksum varchar(255) default null,
              md5_checksum_tags varchar(255) default null,
              md5_checksum_words varchar(255) default null comment 'this includes the operation description',
              md5_checksum_elements varchar(255) default null,
              PRIMARY KEY  (id),
              KEY object_id_key (object_id), 
              KEY last_page_load_id_key (last_page_load_id), 
              KEY use_case_part_id_key (use_case_part_id) ,
              KEY sql_part_enum_key (sql_part_enum) ,	
              KEY table_element_id_key (table_element_id), 
              KEY outside_element_id_key (outside_element_id),
              KEY reference_table_element_id_key (reference_table_element_id) ,
              KEY ranking_key (ranking), 
              KEY is_deleted_key (is_deleted)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_api_use_case_parts_sql_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts_sql ADD CONSTRAINT fk_api_use_case_parts_sql_has_object_id
 										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_parts_sql_has_page_load_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts_sql ADD CONSTRAINT fk_api_use_case_parts_sql_has_page_load_id 
										FOREIGN KEY (last_page_load_id) REFERENCES gokabam_api_page_loads(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_parts_sql_has_use_case_part_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts_sql ADD CONSTRAINT fk_api_use_case_parts_sql_has_use_case_part_id 
										FOREIGN KEY (use_case_part_id) REFERENCES gokabam_api_use_case_parts(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_parts_sql_has_table_element_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts_sql ADD CONSTRAINT fk_api_use_case_parts_sql_has_table_element_id 
										FOREIGN KEY (table_element_id) REFERENCES gokabam_api_data_elements(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_api_use_case_parts_sql_has_ref_table_element_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts_sql ADD CONSTRAINT fk_api_use_case_parts_sql_has_ref_table_element_id 
										FOREIGN KEY (reference_table_element_id) REFERENCES gokabam_api_data_elements(id);' );
			}

			if (!$mydb->foreignKeyExists('fk_api_use_case_parts_sql_has_outside_element_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_use_case_parts_sql ADD CONSTRAINT fk_api_use_case_parts_sql_has_outside_element_id 
										FOREIGN KEY (outside_element_id) REFERENCES gokabam_api_data_elements(id);' );
			}






###########################################################################################################################################
			/*
			*              Triggers
			*
			*
			*/
###########################################################################################################################################


/*
use case parts
    changes use cases

use case parts sql
	changes use case parts
	restricts updates in use case parts

version

family
    changes version

apis
    changes family
    changes use case parts

inputs
    changes apis


outputs
    changes apis


headers
    changes outputs
    changes apis
    changes family
    changes version

------^^^^^^-------------
data_groups
    changes inputs
    changes output
    changes headers
    changes use case parts

data_group_members
    changes  groups


data_elements
    changes group members
    use case parts sql

data_element_objects
   changes  elements

data_group_examples
    changes  groups
    changes use case parts
-----------------------
 */




###########################################################################################################################################
/*
 *                  TAGS
 *
 *              gokabam_api_tags
 *
 *
 */
###########################################################################################################################################

			//the tags
			$sql = "CREATE TABLE `gokabam_api_tags` (
              id int NOT NULL AUTO_INCREMENT,
              object_id int not null,
              target_object_id int NOT NULL comment 'tags this object',
              last_page_load_id int default null,
              is_deleted tinyint DEFAULT 0 not null comment 'because deleted tags still around must allow for duplicates',
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
              md5_checksum varchar(255) default null,
              tag_label varchar(255) not null comment 'not mulitlingual',
              tag_value text default null comment 'not mulitlingual',
              PRIMARY KEY  (id),
              KEY object_id_key (object_id),
              KEY target_object_id_key (target_object_id),
              KEY last_page_load_id_key (last_page_load_id),
              KEY tag_label_key (tag_label)
              ) $charset_collate;";

			dbDelta( $sql );


			if (!$mydb->foreignKeyExists('fk_tags_has_target_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_tags ADD CONSTRAINT fk_tags_has_target_object_id
 										FOREIGN KEY (target_object_id) REFERENCES gokabam_api_objects(id);' );
			}


			if (!$mydb->foreignKeyExists('fk_tags_has_object_id')) {
				$mydb->execute( 'ALTER TABLE gokabam_api_tags ADD CONSTRAINT fk_tags_has_object_id 
										FOREIGN KEY (object_id) REFERENCES gokabam_api_objects(id);' );
			}



			//NOW UPDATE THE TRIGGERS !
			$mydb->execute_nested_sql_files(PLUGIN_PATH.'includes/triggers');

			update_option( "_".strtolower( PLUGIN_NAME) ."_db_version" , Activator::DB_VERSION );
		}


	}



}
