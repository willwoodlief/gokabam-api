
class KabamContainerMinimalSingle extends KabamContainerBase {
    constructor(gokabam,css_class_array, filter) {
        super(gokabam,css_class_array, filter,false,'minimal');
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

    create_outer_div(class_array) {
        let div = super.create_outer_div(class_array);
        div.addClass('gokabam-minimal-single-container');
        div.append('<div style="width: 100%;height: 2em;background-color: green;color: white" >Minimal Demo</div>');
        return div;
    }

    create_container_div(parent_div) {
        let div = super.create_container_div(parent_div);
        div.addClass('gokabam-minimal-single-container');
        return div;
    }
}