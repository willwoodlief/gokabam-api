<?php
    namespace gokabam_api;
    require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
    global $GokabamGoodies;

    try {
	    $api_version_id = null;
	    $api_version_created=null;
	    $api_version_row = null;
	    $api_version_pk = null;
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
        $api_version_pk = $api_version_row->id;

	    ApiVersionHome::$post_name = 'API Version' . ' ' .  $api_version_id  ;

    } catch (\Exception $e) {
	    ErrorLogger::saveException($e);
    }

?>



<?php ErrorLogger::print_exceptions()?>

<?php if ($api_version_row) { ?>
<div class="row">

    <div class="col-sm-12 col-md-5 panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">Api Families for <span class="selected-api-version"></span></h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <button type="button" class="btn btn-primary gokabam-make-new-family"> Make New Family </button>
                </div>
            </div>
        </div>
    </div>


    <div class="col-sm-12 col-md-6 col-md-offset-1 panel panel-success">
        <div class="panel-heading">
            <h3 class="panel-title"> Api Calls for <span class="selected-family"></span></h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <button type="button" class="btn btn-success"> Make New Api Call</button>
                </div>
            </div>
        </div>
    </div>

</div> <!-- .row -->

    <div class="" id="family-api-forms" style="display: none">

        <div class="gokabam-new-family-form form-group">
            <form method="post" action="">
                <div class="row">
                    <input type="hidden" name="gokabam_current_version_id" value="<?= $GokabamGoodies->get_current_version_id() ?>">
                    <input type="hidden" name="gokabam_api_version_id" value="<?= $api_version_pk?>">
                    
                    <div class="col-sm-12 col-md-6">
                        <label for="gokabam_new_family_name">Family Name</label>
                        <input type="text" class="form-control" id="gokabam_new_family_name" name="gokabam_new_family_name" placeholder="Name">
                    </div>

                    <div class="col-sm-12 col-md-6">
                        <label for="gokabam_new_family_blurb">Family Blurb</label>
                        <input type="text" class="form-control" id="gokabam_new_family_blurb" name="gokabam_new_family_blurb" placeholder="Blurb">
                    </div>

                    <div class="col-sm-12 col-md-12">
                        <label for="gokabam_family_description">Family Description</label>
                        <textarea class="form-control"
                                  id="gokabam_family_description"
                                  name="gokabam_family_description"
                                  placeholder="Notes or Ideas"
                                  rows="8"
                        ></textarea>
                    </div>
                </div>
            </form>
        </div>

    </div>


<?php } ?>


