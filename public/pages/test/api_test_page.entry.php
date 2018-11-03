<?php

namespace gokabam_api;

require_once( PLUGIN_PATH . 'lib/Input.php'  );
require_once( PLUGIN_PATH . 'lib/ErrorLogger.php'  );
require_once( PLUGIN_PATH . 'lib/DBSelector.php'  );


class ApiTestPage {
	static public function get_regex() {
		# /([\d.]+)/
		return '#/gokabam_api/test#i';
	}

	static public function get_slug() {
		return 'gokabam_api/test';
	}

	static public function get_name() {
		return 'API Test';
	}

	static public function get_title( $request_uri ) {
		ErrorLogger::unused_params( $request_uri );

		return 'API Test';
	}

	static public function get_template() {
		return 'full-width';
	}

	/**
	 * @param array $data_from_post
	 * @param string $request_uri
	 *
	 * @return string
	 */
	static public function get_page( array $data_from_post, $request_uri ) {
		ErrorLogger::unused_params( $data_from_post, $request_uri );


//		$id = 'none';
//		if ( preg_match( '#unique/(\d+)#', $url, $m ) ) {
//			$id = $m[1];
//		}

		ob_start();
		require_once( realpath( dirname( __FILE__ ) ) . '/api_test_page.view.php' );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	}

	static public function post_page( $url, array &$data_from_post ) {
		ErrorLogger::unused_params( $data_from_post, $url );

		try {
			throw new \Exception("Post not set up for the Test page");
		} catch ( \Exception $e ) {
			ErrorLogger::saveException( $e );
		}


	}

	static public function enqueue_styles( $plugin_name, $plugin_version ) {



		wp_enqueue_style( 'ace-diff', PLUGIN_URL. 'node_modules/ace-diff/dist/ace-diff.min.css', array(), '1.0', 'all' );

		$path = get_home_url( null, 'wp-content/plugins/gokabam-api/public/pages/test/api_test_page.css' );
		wp_enqueue_style( $plugin_name . '_gk_api_test', $path, array(), $plugin_version, 'all' );
	}

	static public function enqueue_scripts( $plugin_name, $plugin_version ) {
		wp_deregister_script( 'jquery' );
		// Change the URL if you want to load a local copy of jQuery from your own server.
		wp_register_script( 'jquery', "https://code.jquery.com/jquery-3.1.1.min.js", array(), '3.3.1' );



		wp_enqueue_script( 'ace', "https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/ace.js", array(), '1.4.1' );

		$path = get_home_url( null, 'wp-content/plugins/gokabam-api/node_modules/ace-diff/dist/ace-diff.min.js' );
		wp_enqueue_script( 'ace-diff', $path, array('ace'), $plugin_version, false   );


		$path = get_home_url( null, 'wp-content/plugins/gokabam-api/public/pages/test/api_test_page.js' );
		wp_enqueue_script( $plugin_name . '_gk_api_test', $path, array( 'jquery' ), $plugin_version, false );


	}
}