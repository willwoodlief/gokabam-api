<?php
namespace gokabam_api;
require_once( realpath( dirname( __FILE__ )  . '/../../lib/DBSelector.php') );
require_once PLUGIN_PATH . 'public/gateway/filler-manager.php';
require_once PLUGIN_PATH . 'public/gateway/kid.php';
class GoKabamGoodies {

	/**
	 * @var integer|null $current_version_id
	 */
	protected $current_version_id = null;

	/**
	 * @var string|null $current_version_name
	 */
	protected $current_version_name = null;

	/**
	 * @var MYDB|null $mydb
	 */
	protected $mydb = null;

	/**
	 * @var null|integer $page_load_id
	 */
	protected $page_load_id = null;

	/**
	 * @var KidTalk|null $kid_talk
	 */
	protected $kid_talk = null;

	/**
	 * @var FillerManager|null $filler_manager
	 */
	protected $filler_manager = null;


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

			$this->kid_talk = new KidTalk( $this->mydb );
			$this->filler_manager = new FillerManager($this);
		} catch (\Exception $e) {
			ErrorLogger::saveException($e);
		}

	}

	public function get_current_version_id() { return $this->current_version_id;}
	public function get_current_version_name() { return $this->current_version_name;}
	public function get_mydb() { return $this->mydb;}

	public function get_kid_talk() { return $this->kid_talk;}

	public function get_filler_manager() { return $this->filler_manager;}
	public function get_page_load_id() { return $this->page_load_id; }
	public function set_page_load_id($page_load_id) { $this->page_load_id = $page_load_id;}
}