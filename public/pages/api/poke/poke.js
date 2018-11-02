var gk_single_container = null;
//poke page java script
function gk_root_start() {
   //create filer

    if (the_kid) {

        let holder = jQuery('.gk-poke-home');
        if (holder.length === 0) {
            throw new Error("Could not find div on page");
        }
        let filter = {
            rules: [],
            literals: [
                the_kid
            ]
        };

        let the_class = KabamWord.get_class_name_from_kid(the_kid);
        let container_class = jQuery.GoKabam.get_container('wide', the_class);
        let container = new container_class(jQuery.GoKabam, ['gk-singleton'], filter);
        holder.append(container.div);
        gk_single_container = container;
    }

}