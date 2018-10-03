<?php
namespace gokabam_api;

require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/Input.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/DBSelector.php') );

class ApiVersionsPage {
	static public  function get_regex() {
		# /([\d.]+)/
		return '#/gokabam_api/api/versions#i';
	}

	static public  function get_slug() {
		return 'gokabam_api/api/versions';
	}

	static public  function get_name() {
		return 'API Versions';
	}

	static public  function get_title($request_uri) {
		ErrorLogger::unused_params($request_uri);
		return 'API Versions';
	}

	static public  function get_template() {
		return 'single';
	}

	/**
	 * @param array $data_from_post
	 * @param string $request_uri
	 * @return string
	 */
	static public function get_page(array $data_from_post ,$request_uri) {
		ErrorLogger::unused_params($data_from_post,$request_uri);


//		$id = 'none';
//		if ( preg_match( '#unique/(\d+)#', $url, $m ) ) {
//			$id = $m[1];
//		}

		ob_start();
		require_once( realpath( dirname( __FILE__ ) ) . '/api_versions_page.view.php' );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	}

	static public function post_page($url,array &$data_from_post) {
		ErrorLogger::unused_params($data_from_post,$url);

		try {
			$version_id = Input::get('current_version_id', Input::THROW_IF_EMPTY);
			$version_number = Input::get('api_version_number', Input::THROW_IF_EMPTY);
			$version_name = Input::get('api_version_name', Input::THROW_IF_EMPTY);
			$version_notes = Input::get('api_version_notes', Input::THROW_IF_EMPTY);
			$version_ts = time();
			$mydb = DBSelector::getConnection('wordpress');
			$sql = <<<SQL
            INSERT INTO gokabam_api_api_versions (version_id,api_version,api_version_name,api_version_notes,created_at_ts) VALUES (?,?,?,?,?);
SQL;
			$mydb->execSQL($sql, array('isssi', $version_id,$version_number, $version_name,$version_notes,$version_ts), MYDB::LAST_ID, 'Tests::lock_resources');
		} catch (\Exception $e) {
			ErrorLogger::saveException($e);
		}


	}

	static public function enqueue_styles($plugin_name,$plugin_version) {
		$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/version/api_versions/api_versions_page.css');
		wp_enqueue_style($plugin_name . '_gk_api_versions', $path, array(), $plugin_version, 'all');
	}

	static public function enqueue_scripts($plugin_name,$plugin_version) {
		$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/version/api_versions/api_versions_page.js');
		wp_enqueue_script($plugin_name. '_gk_api_versions', $path, array('jquery'), $plugin_version, false);
	}
}