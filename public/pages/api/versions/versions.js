var gk_single_container = null;
//poke page java script
function gk_root_start() {
   //create filer

    let holder = jQuery('.gk-poke-home');
    if (holder.length === 0) {
        throw new Error("Could not find div on page");
    }

    let version_type_regex = /^version_\w+$/;

    let version_filter = {
        rules: [
            {
                property_name: 'kid',
                property_value: version_type_regex
            }
        ],
        literals: []
    };

    let version_container_class = jQuery.GoKabam.get_container('compact', 'KabamVersion');
    let version_container = new version_container_class(jQuery.GoKabam, ['gk-versions-page'], version_filter);
    holder.append(version_container.div);
    gk_single_container = version_container;


}