
class KabamDisplayWordMinimal extends KabamDisplayBase {


    constructor(gokabam,the_filter,container) {

        super(gokabam,the_filter,container,'minimal',false,'KabamWord');
        this._editing_kid = null;
    }

    create_parent_div(classes) {
        classes.push('gokabam-draw-word-single');
        return super.create_parent_div(classes);
    }

    create_content_div(parent) {
        return super.create_content_div(parent)
    }

    add_child_containers(parent) {
        return []
    }

    on_refresh(parent_div) {
        let that = this;
        jQuery(document).off('click','.gk-word-single-display');
        jQuery(document).on('click','.gk-word-single-display',function() {
            //find editor for this type and single
            let editor_class = that.gokabam.get_editor(that.root_type,that.style,false);
            if (!editor_class) {
                throw new Error('Display Word cannot find single editor for word');
            }
            let kid = jQuery(this).data('kid');
            that._editing_kid = kid;
            if (!object_map.hasOwnProperty(kid)) {
                throw new Error("Display Word Map does not have word in click handler");
            }
            let word_to_be_edited = object_map[kid];
            let editor = new editor_class(that.gokabam,[word_to_be_edited],that);
            editor.edit();
        });

        parent_div.html('');
        let object_array = this.objects;
        let object_map = {};
        for(let i =0; i < object_array.length ; i++) {
            // noinspection JSValidateTypes
            /**
             * @type {KabamWord}
             */
            let word = object_array[i];
            object_map[word.kid] = word;


            let html =
                '<div style="background-color: red; padding: 2em" data-kid = "'+word.kid+'" class="gk-word gk-word-single-display gk-word-id-'+word.kid +'">' +
                '<span class="gk-word-type">' + word.type + '</span><br> ' +
                '<span class="gk-word-language">' + word.language + '</span><br> ' +
                '<span class="gk-word-text">' + word.text + '</span><br> ' +
                '</div>';
            parent_div.append(html);



        }
    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_starting(editor) {
        let div_class = '.gk-word-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.addClass('gk-on-edit');

    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_submit(editor) {
        let div_class = '.gk-word-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.removeClass('gk-on-edit');
    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_cancel(editor) {
        let div_class = '.gk-word-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.removeClass('gk-on-edit');
    }


}


