<?php
namespace gokabam_api;

require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/Input.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/DBSelector.php') );

class MainVersionPage {
	static public  function get_regex() {
		return '#/gokabam_api/versions#i';
	}

	static public  function get_slug() {
		return 'gokabam_api/versions';
	}

	static public  function get_name() {
		return 'versions main page';
	}

	static public  function get_title() {
		return 'Versions of GoKabam Main Page';
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



//		$id = 'none';
//		if ( preg_match( '#unique/(\d+)#', $url, $m ) ) {
//			$id = $m[1];
//		}

		ob_start();
		require_once( realpath( dirname( __FILE__ ) ) . '/main_version_page.view.php' );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	}

	static public function post_page($url,array &$data_from_post) {
		global $wpdb;

		try {
			$version_number = Input::get('version_number', Input::THROW_IF_EMPTY);
			$version_name = Input::get('version_name', Input::THROW_IF_EMPTY);
			$version_notes = Input::get('version_notes', Input::THROW_IF_EMPTY);
			$version_ts = time();
			$mydb = DBSelector::getConnection('wordpress');
			$table = $wpdb->prefix . 'gokabam_api_version';
			$sql = <<<SQL
            INSERT INTO $table (version,version_name,version_notes,created_at_ts) VALUES (?,?,?,?);
SQL;
			$mydb->execSQL($sql, array('sssi', $version_number, $version_name,$version_notes,$version_ts), MYDB::LAST_ID, 'Tests::lock_resources');
		} catch (\Exception $e) {
			ErrorLogger::saveException($e);
		}


	}

	static public function enqueue_styles($plugin_name,$plugin_version) {
		$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/version/main/main_version_page.css');
		wp_enqueue_style($plugin_name . 'mv', $path, array(), $plugin_version, 'all');
	}

	static public function enqueue_scripts($plugin_name,$plugin_version) {
		$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/version/main/main_version_page.js');
		wp_enqueue_script($plugin_name. 'mv', $path, array('jquery'), $plugin_version, false);
	}
}