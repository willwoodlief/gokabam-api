<?php
    namespace gokabam_api;
    require_once( PLUGIN_PATH  . 'lib/ErrorLogger.php' );

    try {

    } catch (\Exception $e) {
	    ErrorLogger::saveException($e);
    }

?>

<?php ErrorLogger::print_exceptions()?>
hello
