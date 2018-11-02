<?php
    namespace gokabam_api;
    require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
    /**
     * @var $GokabamGoodies GoKabamGoodies
     */
    global $GokabamGoodies;
    $kid_string = null;
    try {
	    if (empty($GokabamGoodies)) {
	        throw new \Exception("No Gokabam Goodies");
        }



    } catch (\Exception $e) {
	    ErrorLogger::saveException($e);
    }

?>



<?php ErrorLogger::print_exceptions()?>
<script>
    var site_url = '<?= get_site_url() ?>';
</script>
<div class="gk-poke-home"></div>



