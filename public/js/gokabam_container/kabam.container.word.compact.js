
class KabamContainerWordCompact extends KabamContainerBase {
    constructor(gokabam,css_class_array, filter) {
        super(gokabam,css_class_array, filter,false,'compact');


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
        div.addClass('gk-container-compact-words');
        let base_id = this.base_id;
        let button_class = base_id + '_new_button';
        let popoff_class = base_id + '-popoff';
        let that = this;
        jQuery(document).off('click','.' + button_class);
        jQuery(document).on('click','.' + button_class ,function() {

            if (that.parent_kid) {
                let new_word = new KabamWord(null);
                // noinspection JSUndefinedPropertyAssignment
                new_word.parent = that.parent_kid;
                new_word.type = 'data';
                that.gokabam.update([new_word]);
            }

        });


        jQuery(document).off('click','.' + popoff_class);
        jQuery(document).on('click','.' + popoff_class ,function() {
            let keys = Object.keys(that.displays);
            if (keys.length > 0) {
                let displays = that.displays[  keys[0]  ];
                if (displays.length > 0) {
                    let gig = displays[0];
                    that.gokabam.popout_container(gig.root_type,'wide','pop-test',that.filter,that.div);
                }


            }

        });


        let new_style = '';
        if (this.parent_kid == null) {
            new_style = ' style="display:none" ';
        }

        let html =

            '<div class="gk-container-frame">\n' +

            '  <div class=" gk-compact-word-header gk-adder '+ button_class + '   ">' +
            '    <span class="gk-compact-word-adder" '+ new_style + ' >' +
            '      <i class="fas fa-file-alt"></i>' +
            '    </span>' +
            '  </div>' +

            '  <div class=" gk-compact-word-header gk-popoff '+ popoff_class + '   ">' +
            '    <span class="gk-compact-word-popoff">' +
            '      <i class="fas fa-angle-up"></i>' +
            '    </span>' +
            '  </div>' +

            '    <div class="gk-container-displays '+ base_id + '_child_container" ></div>\n' +'' +
            '  </div>';

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