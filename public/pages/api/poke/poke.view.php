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

	    /** @noinspection PhpUndefinedVariableInspection */
	    if (preg_match(Poke::get_regex(), $request_uri, $m)) {
		    $table  = $m['table'];
		    $code = $m['code'];
		    $hint = $m['hint'];
		    ErrorLogger::unused_params($hint); //maybe for later ?
		    $reconstructed = $table."_".$code;
		    $kid = $GokabamGoodies->get_kid_talk()->convert_parent_string_kid($reconstructed);
		    $kid_string = $kid->kid;
        } else {
	        throw new \Exception("Could not find the api version");
        }



    } catch (\Exception $e) {
	    ErrorLogger::saveException($e);
    }

?>



<?php ErrorLogger::print_exceptions()?>
<div class="gk-poke-home"></div>
<?php if ($kid_string) { ?>
    <script>
        var the_kid = "<?= $kid_string ?>";
    </script>

<?php } else {?>
    <script>
        var the_kid = null;
    </script>

<?php } ?>


