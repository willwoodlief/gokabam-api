<?php
namespace gokabam_api;

class Version {

	public function __construct() {

	}

	public static function getRegEx() {

	}

	public function show_main_versions_page() {
		print "Show Main Version Page";
	}

	public function show_new_version_page() {
		print "Show New Version Page";
	}

	public function show_version_page($version) {
		print "Show  Version $version Page";
	}

	public function create_new_version() {
		print "Create New Version ";
	}


}