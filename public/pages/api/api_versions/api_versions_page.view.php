<?php
    namespace gokabam_api;
    require_once( realpath( dirname( __FILE__ )  . '/../../../../lib/ErrorLogger.php') );
    global $wpdb,$GokabamGoodies;
    $versions = [];
    try {
	    /** @noinspection SqlResolve */
	    $versions = $wpdb->get_results(
		    " 
                select v.version_name,a.id,version_id,api_version,a.created_at_ts,
                api_version_notes,api_version_name
                from gokabam_api_api_versions a
                left join gokabam_api_versions v ON v.id = a.version_id
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
    <h2>Create A New API Version</h2>
    <p>
        This will take all currently existing api specs and copy them over to the new version.
        <br>
        The old api versions and specs will not be effected
    </p>

    <input type="hidden" name="current_version_id" value="<?= $GokabamGoodies->get_current_version_id() ?>">
    <table>
        <tr>
            <td>
                <input type="text" name="api_version_number" placeholder="Version">
            </td>

            <td>
                <input type="text" name="api_version_name" placeholder="Version Name">
            </td>
            <td>
                <textarea type="text" name="api_version_notes" placeholder="Version Comments"></textarea>
            </td>
            <td>
                <button type="submit" class="btn btn-primary"> Make New Api Version </button>
            </td>
        </tr>
    </table>
</form>
<table>
    <thead>
    <tr>
        <th><span>API Version</span></th>
        <th><span>API Name</span></th>
        <th><span>Created</span></th>
        <th><span>API Comment</span></th>
        <th><span>Created In</span></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($versions as $row) { ?>
        <?php
            $url_to_version = PLUGIN_URL . 'gokabam_api/api/version/' . $row->api_version
        ?>
        <tr>
            <td>
                <a href="<?=$url_to_version?>">
                    <span><?= $row->api_version ?></span>
                </a>
            </td>
            <td><span><?= $row->api_version_name ?></span></td>
            <td><span class="a-timestamp-full-date-time" data-ts="<?= $row->created_at_ts ?>"></span></td>
            <td><span><?= $row->api_version_notes ?></span></td>
            <td><span><?= $row->version_name ?></span></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
