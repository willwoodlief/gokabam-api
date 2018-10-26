<?php
    namespace gokabam_api;
    require_once( PLUGIN_PATH  . 'lib/ErrorLogger.php' );

    try {

    } catch (\Exception $e) {
	    ErrorLogger::saveException($e);
    }

?>

<?php ErrorLogger::print_exceptions()?>

<div class="gk-wrap">
    <div class="gk-side">
        <div class="gk-infobar">
            <span class="gk-status" style="margin-left: 2em;"> have a nice day!</span>
            <i class="fa fa-spinner fa-spin gk-spinner" ></i>
        </div>
        <button type="button" class="gk-talker" > Talk </button>
        <button type="button" class="gk-test gk-test1 " > Refresh Heartbeat </button>
        <button type="button" class="gk-test gk-test2" > Update Version </button>
    </div>

    <div class="gk-main">

    </div>
</div>
