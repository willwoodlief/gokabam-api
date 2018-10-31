
class KabamContainerWordWide extends KabamContainerBase {
    constructor(gokabam,css_class_array, filter) {
        super(gokabam,css_class_array, filter,false,'wide');
        //find the parent kid
        this._parent_kid = null;
        let count = 0;
        for(let i = 0; i < filter.rules.length; i++) {
            let rule = filter.rules[i];
            if (rule.property_name === 'parent') {
              this._parent_kid = rule.property_value;
              count++;
            }
        }
        if (!this._parent_kid) {
            throw new Error("Word container cannot find a parent id in the rules");
        }

        if (count > 1) {
            throw new Error("Word container: found " + count + " parents in the rules");
        }

    }

    get parent_kid() { return this._parent_kid;}


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

            if (that.parent_kid) {
                let new_word = new KabamWord(null);
                // noinspection JSUndefinedPropertyAssignment
                new_word.parent = that.parent_kid;
                new_word.type = 'data';
                that.gokabam.update([new_word]);
            }

        });


        let html =

            '  <div class="col-md-2 col-sm-4 gk-container-frame">\n' +
            '      <div class="new-word">\n' +
            '          <button class="btn btn-success '+ button_class + '   ">'+
            '             <i class="fas fa-cloud-sun"></i> New Word' +
            '          </button>\n' +
            '      </div>\n' +
            '  </div>\n' +
            '      <div class="col-md-10 col-sm-12 gk-container-displays '+ base_id + '_child_container" >\n' +'' +
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