<?php

namespace gokabam_api;
require_once 'pages.php';
require_once  'gokabam.goodies.php';

/**
 * @var $GokabamGoodies GoKabamGoodies
 * <p>
 *   Nice Stuff
 * </p>
 */
global $GokabamGoodies;





/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 */
class CreateKids {

	/**
	 * @var MYDB|null $mydb
	 */
	protected $mydb = null;

	/**
	 * @var integer|null $latest_version_id
	 */
	protected $latest_version_id = null;

	/**
	 * CreateKids constructor.
	 *
	 * @param MYDB $mydb
	 * @param integer $version_id
	 */
	public function __construct( $mydb, $version_id ) {

		$this->mydb = $mydb;
		$this->latest_version_id     = $version_id;
	}
}
