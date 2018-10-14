<?php
namespace gokabam_api;
require_once( realpath( dirname( __FILE__ )  . '/../lib/DBSelector.php') );

class GoKabamGoodies {

	protected $current_version_id = null;
	protected $current_version_name = null;
	protected $mydb = null;
	protected $page_load_id = null;


	public function __construct( ) {
		try {

			$this->mydb = DBSelector::getConnection('wordpress');
			$sql = <<<SQL
            SELECT id,version FROM gokabam_api_versions where 1 ORDER BY id desc limit 1 ;
SQL;
			$res = $this->mydb->execSQL($sql);
			if (!empty($res)) {
				$row = $res[0];
				$this->current_version_id = $row->id;
				$this->current_version_name = $row->version;
			}
		} catch (\Exception $e) {
			ErrorLogger::saveException($e);
		}

	}

	public function get_current_version_id() { return $this->current_version_id;}
	public function get_current_version_name() { return $this->current_version_name;}
	public function get_mydb() { return $this->mydb;}
	public function get_page_load_id() { return $this->page_load_id; }
	public function set_page_load_id($page_load_id) { $this->page_load_id = $page_load_id;}
}