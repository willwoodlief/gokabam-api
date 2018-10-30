
class KabamContainerWordWide extends KabamContainerBase {
    constructor(gokabam,css_class_array, filter) {
        super(gokabam,css_class_array, filter,false,'wide');
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
        div.addClass('gk-container-wide-words');
        let base_id = this.base_id;
        let button_class = base_id + '_new_button';
        let that = this;
        jQuery(document).off('click','.' + button_class);
        jQuery(document).on('click','.' + button_class ,function() {
            //get the parent id for the first word in this collection, and make a new one
            let word = null;
            //go through all the displays
            for(let i in that.displays) {
                if (word) {break;}
                let display_array = that.displays[i];
                for( let  b = 0; b < display_array.length; b++) {
                    if (word) {break;}
                    let display = display_array[b];
                    let objects = display.objects;
                    for( let j = 0; j < objects.length; j++) {
                        let test = objects[j];
                        if (test.constructor.name === 'KabamWord') {
                            word = test;
                            break;
                        }
                    }
                }

            }

            if (word) {
                let parent_kid = word.parent;
                let new_word = new KabamWord(null);
                new_word.parent = parent_kid;
                new_word.type = 'data';
                that.gokabam.update([new_word]);
            }


        });


        let html =

            '  <div class="col-md-1 col-sm-2 gk-container-sider">\n' +
            '      <div class="new-word">\n' +
            '          <button class="btn btn-success '+ button_class + '   ">'+
            '             <i class="fas fa-cloud-sun"></i> New Word' +
            '          </button>\n' +
            '      </div>\n' +
            '  </div>\n' +
            '  <div class="col-md-11 col-sm-12 gk-container-content '+ base_id + '_child_container' +'">\n' +
            '  </div>';

        div.append(html);
        return div;

    }

    create_container_div(parent_div) {

        let child_div = jQuery('<div></div>');
        child_div.addClass('gk-container-content');
        parent_div.find('.' + this.base_id + '_child_container').append(child_div);
        return child_div;
    }
}