<?php
namespace gokabam_api;

require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/Input.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/DBSelector.php') );

class ApiVersionHome {

	public static $post_name = 'API Version Home';
	static public  function get_regex() {
		# /([\d.]+)/
		return '#/gokabam_api/api/version/([\d.]+)#i';
	}

	static public  function get_slug() {
		return 'gokabam_api/api/version';
	}

	static public  function get_name() {
		return 'API Version Home';
	}

	static public  function get_title($request_uri) {
		ErrorLogger::unused_params($request_uri);
		return self::$post_name;
	}

	static public  function get_template() {
		return 'full-width';
	}

	/**
	 * @param array $data_from_post
	 * @param string $request_uri
	 * @return string
	 */
	static public function get_page(array $data_from_post ,$request_uri) {
		ErrorLogger::unused_params($data_from_post,$request_uri);

		ob_start();
		require_once( realpath( dirname( __FILE__ ) ) . '/api_version_home.view.php' );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	}

	static public function post_page($url,array &$data_from_post) {
		ErrorLogger::unused_params($data_from_post,$url);

		try {
			$version_id = Input::get('gokabam_current_version_id', Input::THROW_IF_EMPTY);
			$family_blurb = Input::get('gokabam_new_family_blurb', Input::THROW_IF_EMPTY);
			$family_name = Input::get('gokabam_new_family_name', Input::THROW_IF_EMPTY);
			$family_notes = Input::get('gokabam_family_description', Input::THROW_IF_EMPTY);
			$api_version_id =Input::get('gokabam_api_version_id', Input::THROW_IF_EMPTY);


			$current_ts = time();
			$mydb = DBSelector::getConnection('wordpress');

			//create the container for the journals
			$sql = <<<SQL
            INSERT INTO gokabam_api_journal_containers (container_notes) VALUES (?);
SQL;
			$notes = "For new API Family";
			$container_id = $mydb->execSQL($sql, array('s', $notes), MYDB::LAST_ID, '@sey@ApiVersion::create-constainer');

			//add in the description as the first note
			$sql = <<<SQL
            INSERT INTO gokabam_api_journals (journal_container_id,version_id,entry,created_at_ts) VALUES (?,?,?,?);
SQL;


			/** @noinspection PhpUnusedLocalVariableInspection */
			$journal_id = $mydb->execSQL($sql,
				array(
						'issi',
						$container_id,
						$version_id,
						$family_notes,
						$current_ts
				),
				MYDB::LAST_ID,
				'@sey@ApiVersion::create-api-journal-entry'
			);



			//create the api version
			$sql = <<<SQL
            INSERT INTO gokabam_api_family 
            (journal_container_id,api_version,created_at_ts,family_name,family_blurb,family_description)
             VALUES (?,?,?,?,?,?);
SQL;
			$mydb->execSQL($sql,
						array(
							'iiisss',
							$container_id,
							$api_version_id,
							$current_ts,
							$family_name,
							$family_blurb,
							$family_notes
							),
					 MYDB::LAST_ID,
				'@sey@ApiVersion::create-api-family');
		} catch (\Exception $e) {
			ErrorLogger::saveException($e);
		}


	}

	static public function enqueue_styles($plugin_name,$plugin_version) {
		$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/api/api_version_home/api_version_home.css');
		wp_enqueue_style($plugin_name . '_gk_api_versions', $path, array(), $plugin_version, 'all');
	}

	static public function enqueue_scripts($plugin_name,$plugin_version) {
		$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/api/api_version_home/api_version_home.js');
		wp_enqueue_script($plugin_name. '_gk_api_versions', $path, array('jquery'), $plugin_version, false);
	}
}