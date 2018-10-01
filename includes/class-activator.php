<?php
namespace gokabam_api;
require_once realpath(dirname(__FILE__)) . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;



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

	const DB_VERSION = 0.1;


	/**
	 * @throws \Exception
	 */
	public static function activate() {
		global $wpdb;


		//check to see if any tables are missing
		$b_force_create = false;
		$tables_to_check= ['gokabam_api_version'];
		foreach ($tables_to_check as $tb) {
			$table_name = "{$wpdb->base_prefix}$tb";
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

			$sql = "CREATE TABLE `{$wpdb->base_prefix}gokabam_api_version` (
              id int NOT NULL AUTO_INCREMENT,
              version float not null ,
              commit_id varchar(255) DEFAULT NULL ,
              tag varchar(255) DEFAULT NULL,
              PRIMARY KEY  (id),
              UNIQUE KEY version_key (version)
              ) $charset_collate;";

			dbDelta( $sql );
			update_option( "_".strtolower( PLUGIN_NAME) ."_db_version" , Activator::DB_VERSION );
		}


	}



}
