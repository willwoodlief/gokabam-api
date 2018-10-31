<?php

namespace gokabam_api;
require_once    PLUGIN_PATH.'public/gateway/gokabam.goodies.php';
require_once    PLUGIN_PATH.'public/gateway/api-typedefs.php';
require_once    PLUGIN_PATH.'public/gateway/parser-manager.php';
require_once    PLUGIN_PATH.'vendor/autoload.php';
require_once    PLUGIN_PATH.'public/gateway/kid.php';
require_once    PLUGIN_PATH.'lib/Input.php';
require_once    PLUGIN_PATH.'lib/ErrorLogger.php';
require_once    PLUGIN_PATH.'lib/JsonHelper.php';
require_once    PLUGIN_PATH.'lib/RecursiveClasses.php';





class ApiParseException extends \Exception {}



/**
 * Api Gateway
 *  The public facing part of the api, here the top commands are routed down to where they are processed
 *
 */
class ApiGateway {

	/**
	 * @var MYDB|null $mydb
	 */
	protected $mydb = null;

	/**
	 * @var integer|null $latest_version_id
	 */
	protected $latest_version_id = null;

	/**
	 * @var integer|null the id of the page load
	 */
	protected $page_load_id = null;

	/**
	 * @var KidTalk|null $kid_talk
	 */
	protected $kid_talk = null;

	/**
	 * @var ParserManager|null $parser_manager
	 */
	protected $parser_manager = null;

	/**
	 * @var GKA_User[]|null the user map to pass to fillers
	 */
	protected $user_map = [];

	/**
	 * ApiGateway constructor.
	 *
	 * @param MYDB $mydb
	 * @param integer $version_id
	 * @param GKA_User[] |null
	 */
	public function __construct( $mydb, $version_id,$user_map ) {
		global $GokabamGoodies;

		try {
			$this->user_map = $user_map;
			$this->mydb              = $mydb;
			$this->latest_version_id = $version_id;
			$this->page_load_id      = null;
			$this->kid_talk          = $GokabamGoodies->get_kid_talk();
		} catch (\Exception $e) {
			$info = ErrorLogger::saveException($e);
			wp_send_json(['is_valid' => false,'data'=> $info, 'message' => "initialiation error"]);
			die();
		}
	}

	/**
	 * @return GKA_Everything
	 * @throws JsonException
	 */
	protected function find_action_stuff() {
		$the_json = Input::get( 'gokabam_api_data', Input::THROW_IF_EMPTY );
		if (!is_array($the_json)) {
			$the_json = JsonHelper::fromString($the_json,true,true);
		}
		$everything = new GKA_Everything();
		$everything->api_action = 'init';
		$everything->pass_through_data = null;
		if (is_array($the_json)) {

			if (array_key_exists('api_action',$the_json)) {
				$everything->api_action = $the_json['api_action'];
			}

			if (array_key_exists('pass_through_data',$the_json)) {
				$everything->pass_through_data = $the_json['pass_through_data'];
			}

			if (array_key_exists('begin_timestamp',$the_json)) {
				$everything->begin_timestamp = intval($the_json['begin_timestamp']);
			}

			if (array_key_exists('end_timestamp',$the_json)) {
				$everything->end_timestamp = intval($the_json['end_timestamp']);
			}


		}

		return $everything;


	}
	/**
	 * @param GKA_Everything $init_everything
	 * @return GKA_Everything
	 * @throws ApiParseException
	 * @throws JsonException
	 * @throws SQLException
	 * @throws FillException
	 */
	protected function update_everything($init_everything){
		global $GokabamGoodies;
		$the_json = Input::get( 'gokabam_api_data', Input::THROW_IF_EMPTY );
		if (!is_array($the_json)) {
			$the_json = JsonHelper::fromString($the_json,true,true);
		}
		if (!is_array($the_json)  || empty($the_json)) {
			throw new ApiParseException("No data found in request send under the key of gokabam_api_data");
		}
		$everything_update = new GKA_Everything();
		$page_load_id = $GokabamGoodies->get_page_load_id();

		$this->parser_manager    = new ParserManager($this->kid_talk,$this->mydb,$everything_update,$page_load_id,$the_json);

		$filler_manager = new FillerManager($GokabamGoodies,null,null,$this->user_map);
		$filler_manager->convert_everything_from_update($everything_update);
		$everything = $filler_manager->get_everything(true, $page_load_id,false);
		$everything->api_action = 'update';
		$everything->pass_through_data = $init_everything->pass_through_data;
		return $everything;

	}



	/**
	 * Reads from post, if any commands that are recognized will do them and return result
	 * if error or not sufficient information will return an error result
	 * This function does all create, update and delete, as well as gets status and initial data
	 * @return GKA_Everything
	 */
	public function all() {
		global $GokabamGoodies;
		try {
			$this->mydb->beginTransaction();
			/**
			 * @var GKA_Everything $everything
			 *  here we are getting the basic stuff
			 */
			$everything = $this->find_action_stuff();

			switch ($everything->api_action) {
				case 'update': {
					/*
					 * Update here does all inserts, updates and deletions for the objects
					 */
					$this->start_userspace();
					$ret = $this->update_everything($everything);
					$this->end_userspace(); //this will be called later if exception above
					break;
				}

				case 'get': {
					/*
					 * the main search function, used by the client to update itself and also get the initial stuff
					 */
					$ret = $this->get($everything);
					break;
				}

				case 'init': {
					//give an empty structure to work with
					//this is helpful to both clients and testers
					$ret = new GKA_Everything();
					$ret->pass_through_data = $everything->pass_through_data;
					$ret->api_action = 'update';
					break;
				}
				default: {
					throw new \InvalidArgumentException("No case for action: [{$everything->api_action}]");
				}
			}

			$ret->is_valid = true;
			$ret->message = 'success';
			$this->mydb->commit();


		} catch (\Exception $e) {
			$this->mydb->rollback();
			$GokabamGoodies->set_page_load_id(null);
			if (isset($everything)) {
				$pass_through = $everything->pass_through_data;
			}
			else {
				$pass_through = null;
			}
			try {
				$exception_info = null;
				$ret = $this->create_error_response( $e, $pass_through,$exception_info );
			} catch (\Exception $f) {
				//if everything is just falling apart send back some data
				$ret = new GKA_Everything();
				$ret->is_valid = false;
				$ret->message = $f->getMessage() . "\n" . $f->getTraceAsString();
				$ret->exception_info = $exception_info;
			}
		}
		$this->finalize_everything_out($ret);
		return $ret;

	}

	/**
	 * @param \Exception $e
	 * @param string|null $pass_through_data
	 * @param array $exception_info
	 * @return GKA_Everything
	 * @throws SQLException
	 */
	protected function create_error_response(\Exception $e, $pass_through_data,&$exception_info) {
		$exception_info['trace'] = [];
		$this->start_userspace("For Error Report");
		$exception_info = ErrorLogger::saveException($e);
		$everything = new GKA_Everything();
		$everything->is_valid = false;
		$everything->exception_info = (object)$exception_info;
		$everything->message = $e->getMessage();
		$everything->pass_through_data = $pass_through_data;
		$this->end_userspace(ErrorLogger::$last_error_id);
		return $everything;
	}


	/**
	 * @param GKA_Everything $everything
	 *
	 * @return GKA_Everything
	 * @throws ApiParseException
	 * @throws FillException
	 * @throws SQLException
	 */
	protected function get(GKA_Everything $everything) {
		global $GokabamGoodies;


		if (empty($everything->begin_timestamp)) {$everything->begin_timestamp = null;}
		if (empty($everything->end_timestamp)) {$everything->end_timestamp = null;}
		$filler_manager = new FillerManager($GokabamGoodies,$everything->begin_timestamp,
															$everything->end_timestamp,$this->user_map);
		$filler_manager->do_all();
		$everything = $filler_manager->get_everything();
		$everything->api_action = 'get';
		return $everything;
	}



	protected function finalize_everything_out(GKA_Everything $everything) {
		$everything->server =  new GKA_ServerData();
		$everything->server->server_time = date('M d Y h:i:s a', time());
		$everything->server->server_timezone = date_default_timezone_get();
		$everything->server->server_timestamp = time();
		$nonce = wp_create_nonce(strtolower( PLUGIN_NAME) . 'public_nonce');
		$everything->server->ajax_nonce = $nonce;
	}


	/**
	 * creates a gokabam_api_page_loads and returns its primary key
	 * @param string $reason - optional reason of why this is called
	 * @return int
	 * @throws SQLException
	 */
	protected function start_userspace($reason = null) {
		global $GokabamGoodies;
		if ($GokabamGoodies->get_page_load_id()) {
			return false;
		}
		$start =  microtime(true);
		$user_id = get_current_user_id();
		$user_info = get_userdata( $user_id );
		if ($user_info && property_exists($user_info,'roles') ) {
			$user_roles = implode(', ', $user_info->roles);
		} else {
			$user_roles = null;
		}

		if ($user_info && property_exists($user_info,'display_name') ) {
			$user_name = $user_info->display_name;
		} else {
			$user_name = null;
		}

		$err_info = ErrorLogger::get_call_info();
		$is_dirty = $err_info['is_commit_modified'] ;
		$git_branch = $err_info['branch'];
		$git_commit = $err_info['last_commit_hash'];
		$ip_address = $err_info['caller_ip_address'];
		$current_version_id = $GokabamGoodies->get_current_version_id();
		$page_load_id = $this->mydb->execSQL(
		"INSERT INTO gokabam_api_page_loads(
			   user_id,
			   ip,
			   git_commit_hash,
			   is_git_dirty,
			   git_branch,
			   start_micro_time,
			   person_name,
			   user_roles,
			   reason,
			   version_id
			   )
			  VALUES (?,?,?,?,?,?,?,?,?,?)",
			[
				'ississsssi',
				$user_id,
				$ip_address,
				$git_commit,
				$is_dirty,
				$git_branch,
				$start,
				$user_name,
				$user_roles,
				$reason,
				$current_version_id
			],
			MYDB::LAST_ID
		);

		if (isset($GokabamGoodies) && !empty($GokabamGoodies)) {
			$GokabamGoodies->set_page_load_id($page_load_id);
		}
		return true;
	}

	/**
	 * Finishes up user space
	 * @param int|null $error_id
	 * @throws SQLException
	 */
	protected function end_userspace($error_id=null) {
		global $GokabamGoodies;
		if (!$GokabamGoodies->get_page_load_id()) {
			return;
		}
		$page_load_id = $GokabamGoodies->get_page_load_id();
		$end =  microtime(true);
		$this->mydb->execSQL("UPDATE gokabam_api_page_loads SET stop_micro_time = ?,error_log_id=? WHERE id = ?",
			['sii',$end,$error_id,$page_load_id],
			MYDB::ROWS_AFFECTED);

	}
}
