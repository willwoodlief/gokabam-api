
class KabamContainerVersionCompact extends KabamContainerBase {
    constructor(gokabam,css_class_array, filter) {
        super(gokabam, css_class_array, filter, false, 'compact');
        //versions have no parents

        /**
         *
         * @type {int[]}
         * @private
         */


    }


    on_new_display(display) {
        // initialize some things that may not exist yet


        if(!this.hasOwnProperty('_slots')) {
            this._slots = [];
        }

        if(!this.hasOwnProperty('_next_index')) {
            this._next_index = 0;
        }

        let base_id = this.base_id;
        let special = base_id + '-holder';
        let place_here = this.div.find('.' + special);
        if (place_here.length === 0 ) {
            throw new Error("Could not find ." + special + " in parent div for compact version");
        }

        // fill in empty slots if something removed, else add to end
        // bootstrap rows of 1 sm, 2 md and 3 lg

        if (this._slots.length > 0) {
            //use slot, pop it off the array
            let where = this._slots.pop();
            let thing_class = base_id + '-version-compact-thing-slot' + where;
            let thing = place_here.find('.' + thing_class);
            if (thing.length === 0) {
                throw new Error("Could not find " + thing_class + " in " + special + " to replace the slot");
            }
            thing.append(display.div);
            place_here.append(thing);
        } else {
            //make a div, and append it to the end of child
            let thing_class = base_id + '-version-compact-thing-slot' + this._next_index;
            display._thing_slot = this._next_index;
            this._next_index ++;
            let thing = jQuery('<div class="col-sm-12 col-md-6 col-lg-4 version-compact-thing '+ thing_class +'" ></div>');
            thing.append(display.div);
            place_here.append(thing);
        }

        display.div.show();
    }

    on_display_destroyed(display) {
        //todo keep track of which column and row got removed
        let slot = display._thing_slot;
        this._slots.push(slot);
        //put on empty slot list
        super.on_display_destroyed(display);
    }

    on_display_changed(display) {
        super.on_display_changed(display);
    }

    create_outer_div(class_array) {
        let div = super.create_outer_div(class_array);
        div.addClass('gk-container-compact-versions');
        let base_id = this.base_id;
        let button_class = base_id + '-new_button';
        let that = this;
        jQuery(document).off('click','.' + button_class);
        jQuery(document).on('click','.' + button_class ,function() {

            let new_version = new KabamVersion(null);
            that.gokabam.update([new_version]);

        });

        let new_style = '';
        if (this.filter.rules.length === 0) {
            new_style = ' style="display:none" ';
        }

        let html =

            '<div class="gk-container-frame">\n' +



            '  <div class="gk-compact-container-header">' +

            '    <div class="new-version" '+ new_style +'>\n' +
            '      <button class="btn btn-success '+ button_class + '   ">'+
            '        <i class="fas fa-save"></i> New Version' +
            '      </button>\n' +
            '    </div>\n' +

            '  </div>' +




            '  <div class="my-clearfix gk-container-displays '+ base_id + '_child_container" ></div>\n' +
            '</div>  ';

        div.append(html);
        return div;

    }

    create_container_div(parent_div) {

        let base_id = this.base_id;
        let special = base_id + '-holder';
        let child_div = jQuery('<div class=" version-compact-thing-holder ' +special + '"></div>');
        child_div.addClass('gk-container-displays');
        parent_div.find('.' + this.base_id + '_child_container').append(child_div);
        return child_div;
    }


}