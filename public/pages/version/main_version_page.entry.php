<?php
namespace gokabam_api;

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
	 */
	static public function get_page(array $data_from_post ,$request_uri) {

		$token = "cats";
		if ($data_from_post && isset($data_from_post['cats'])) {
			$token = $data_from_post['cats'];
		}

//		$id = 'none';
//		if ( preg_match( '#unique/(\d+)#', $url, $m ) ) {
//			$id = $m[1];
//		}

		ob_start();
		require_once(realpath(dirname(__FILE__)) . '/main_version_page.view.php');
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	}

	static public function post_page($url,array &$data_from_post) {
		$data_from_post['cats'] = 'Mary';
	}
}