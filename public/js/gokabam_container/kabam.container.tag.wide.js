
class KabamContainerTagWide extends KabamContainerBase {
    constructor(gokabam,css_class_array, filter) {
        super(gokabam,css_class_array, filter,true,'wide');
        //find the parent kid


    }


    on_new_display(display) {
        super.on_new_display(display);
    }

    on_display_destroyed(display) {
        super.on_display_destroyed(display);
    }

    on_display_changed(display) {
        super.on_display_changed(display);
    }

    /**
     * Outer div is simply a shell
     * @param class_array
     * @return {jQuery}
     */
    create_outer_div(class_array) {
        let div = super.create_outer_div(class_array);
        div.addClass('gk-container-wide-tags');
        let base_id = this.base_id;
        let html =

            ' <div class="gk-container-frame" style="display: none"></div>\n' +
            ' <div class="col-sm-12 gk-container-displays '+ base_id + '_child_container" >\n' +'' +
            ' </div>\n';

        div.append(html);
        return div;

    }

    create_container_div(parent_div) {

        let child_div = jQuery('<div></div>');
        child_div.addClass('gk-container-displays');
        parent_div.find('.' + this.base_id + '_child_container').append(child_div);
        return child_div;
    }
}