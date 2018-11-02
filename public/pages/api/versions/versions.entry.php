<?php
namespace gokabam_api;

require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/Input.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/DBSelector.php') );

class Versions {

	public static $post_name = 'GoKabam Ideas';
	static public  function get_regex() {
		# /([\d.]+)/
		return '#/gokabam_api/versions#i';
	}

	static public  function get_slug() {
		return 'gokabam_api/versions';
	}

	static public  function get_name() {
		return 'versions';
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
		require_once( realpath( dirname( __FILE__ ) ) . '/versions.view.php' );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	}

	static public function post_page($url,array &$data_from_post) {
		ErrorLogger::unused_params($data_from_post,$url);

		die("This url is GET only");

	}

	static public function enqueue_styles($plugin_name,$plugin_version) {
	//	$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/api/versions/versions.css');
		$path = plugins_url('gokam-api/public/pages/api/versions/versions.css');
		wp_enqueue_style($plugin_name . '_gk_versions', $path, array(), $plugin_version, 'all');
	}

	static public function enqueue_scripts($plugin_name,$plugin_version) {
		$path = plugins_url('gokam-api/public/pages/api/versions/versions.js');
		wp_enqueue_script($plugin_name. '_gk_versions', $path, array('jquery'), $plugin_version, false);
	}
}