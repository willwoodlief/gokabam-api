<?php
namespace gokabam_api;
require_once realpath(dirname(__FILE__)) . '/../vendor/autoload.php';
require_once realpath(dirname(__FILE__)) . '/../lib/ErrorLogger.php';

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

	const DB_VERSION = 0.129;


	/**
	 * @throws \Exception
	 */
	public static function activate() {
		global $wpdb;


		//check to see if any tables are missing
		$b_force_create = false;
		$tables_to_check= [
				'gokabam_api_versions',
				ErrorLogger::getDBTableName(),
				'gokabam_api_api_versions',
				'gokabam_api_apis'
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


			//do response table

			$sql = "CREATE TABLE `gokabam_api_versions` (
              id int NOT NULL AUTO_INCREMENT,
              version varchar(255) not null ,
              version_name varchar(255) DEFAULT NULL ,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              commit_id varchar(255) DEFAULT NULL ,
              tag varchar(255) DEFAULT NULL,
              version_notes text DEFAULT NULL,
              PRIMARY KEY  (id),
              UNIQUE KEY version_key (version),
              KEY version_name_key (version_name)
              ) $charset_collate;";

			dbDelta( $sql );

			//create the db table
			$sql = ErrorLogger::getDBTableString();
			dbDelta( $sql );

			// gokabam_api_api_versions

			$sql = "CREATE TABLE `gokabam_api_api_versions` (
              id int NOT NULL AUTO_INCREMENT,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              version_id int not null ,
              api_version varchar(255) DEFAULT NULL ,
              api_version_name varchar(255) DEFAULT NULL ,
              api_version_notes text DEFAULT NULL,
              PRIMARY KEY  (id),
              UNIQUE KEY api_version_key (api_version),
              UNIQUE KEY api_version_name_key (api_version_name),
              KEY version_id_key (version_id)
              ) $charset_collate;";

			dbDelta( $sql );

			//Table of api:id,version_id(from above),is_deleted, deleted_date,created_date
			$sql = "CREATE TABLE `gokabam_api_apis` (
              id int NOT NULL AUTO_INCREMENT,
              api_version_id int not null,
              from_api_version_id int DEFAULT null,
              version_id int not null,
              created_at_ts int not null,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              is_deleted int DEFAULT 0 not null ,
              deleted_at_ts int DEFAULT null,
              PRIMARY KEY  (id),
              KEY api_version_id_key (api_version_id),
              KEY from_api_version_id_key (from_api_version_id),
              KEY version_id_key (version_id),
              KEY created_at_ts_key (created_at_ts)
              ) $charset_collate;";

			dbDelta( $sql );


			update_option( "_".strtolower( PLUGIN_NAME) ."_db_version" , Activator::DB_VERSION );
		}


	}



}
