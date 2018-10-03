<?php
    namespace gokabam_api;
    require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
    global $GokabamGoodies;

    try {
	    $api_version_id = null;
	    $api_version_created=null;
	    $api_version_row = null;
	    /** @noinspection PhpUndefinedVariableInspection */
	    if (preg_match(ApiVersionHome::get_regex(), $request_uri, $m)) {
		    $api_version_id  = $m[1];
        } else {
	        throw new \Exception("Could not find the api version");
        }

	    $mydb = $GokabamGoodies->get_mydb();
	    $sql = <<<SQL
            SELECT id,created_at_ts,version_id,api_version
             api_version_name,api_version_notes
             from gokabam_api_api_versions  WHERE api_version = ?;
SQL;
	    $res = $mydb->execSQL($sql, array('s', $api_version_id));
        if (empty($res)) {
            throw new \Exception("Cannot find the API Version of $api_version_id");
        }
        $api_version_row = $res[0];
        $api_version_name = $api_version_row->api_version_name;
        $api_version_created = $api_version_row->created_at_ts;

	    ApiVersionHome::$post_name = 'API Version' . ' ' .  $api_version_id  ;

    } catch (\Exception $e) {
	    ErrorLogger::saveException($e);
    }

?>



<?php ErrorLogger::print_exceptions()?>

<?php if ($api_version_row) { ?>

<h1>
    <span class="a-timestamp-full-date-time" data-ts="<?= $api_version_created ?>"></span>
</h1>

<?php } ?>


