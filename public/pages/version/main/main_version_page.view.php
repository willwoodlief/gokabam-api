<?php
    namespace gokabam_api;
    require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
    global $wpdb;
    $versions = [];
    try {

	    /** @noinspection SqlResolve */
	    $versions = $wpdb->get_results(
		    " 
                select id,version,commit_id,tag,created_at_ts,version_notes,version_name
                from gokabam_api_versions 
                order by created_at_ts asc;
                " );

	    if ( $wpdb->last_error ) {
		    throw new \Exception( $wpdb->last_error );
	    }
    } catch (\Exception $e) {
	    ErrorLogger::saveException($e);
    }

?>
<h1>
	Main Version Page
</h1>
<?php ErrorLogger::print_exceptions()?>
<form action="" method="post">
    <table>
        <tr>
            <td>
                <input type="text" name="version_number" placeholder="Version">
            </td>

            <td>
                <input type="text" name="version_name" placeholder="Version Name">
            </td>
            <td>
                <textarea type="text" name="version_notes" placeholder="Version Comments"></textarea>
            </td>
            <td>
                <button type="submit" class="btn btn-primary"> Add New Version </button>
            </td>
        </tr>
    </table>
</form>
<table>
    <thead>
    <tr>
        <th><span>Version</span></th>
        <th><span>Name</span></th>
        <th><span>Created</span></th>
        <th><span>Comment</span></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($versions as $row) { ?>
        <tr>
            <td><span><?= $row->version ?></span></td>
            <td><span><?= $row->version_name ?></span></td>
            <td><span class="a-timestamp-full-date-time" data-ts="<?= $row->created_at_ts ?>"></span></td>
            <td><span><?= $row->version_notes ?></span></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
