<?php
namespace gokabam_api;

require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/Input.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/DBSelector.php') );

class Poke {

	public static $post_name = 'Go Kabam';
	static public  function get_regex() {
		# /([\d.]+)/
		return '#/gokabam_api/poke/(?P<table>[a-z]*)_(?P<code>[[:alnum:]]*)(?P<hint>[^[:alnum:]][[:alnum:]]*)?#i';
	}

	static public  function get_slug() {
		return 'gokabam_api/poke';
	}

	static public  function get_name() {
		return 'GoKabam!';
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
		require_once( realpath( dirname( __FILE__ ) ) . '/poke.view.php' );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	}

	static public function post_page($url,array &$data_from_post) {
		ErrorLogger::unused_params($data_from_post,$url);

		die("This url is GET only");

	}

	static public function enqueue_styles($plugin_name,$plugin_version) {
		$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/api/poke/poke.css');
		wp_enqueue_style($plugin_name . '_gk_poke', $path, array(), $plugin_version, 'all');
	}

	static public function enqueue_scripts($plugin_name,$plugin_version) {
		$path  = get_home_url(null,  'wp-content/plugins/gokam-api/public/pages/api/poke/poke.js');
		wp_enqueue_script($plugin_name. '_gk_poke', $path, array('jquery'), $plugin_version, false);
	}
}