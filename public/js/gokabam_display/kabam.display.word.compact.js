class KabamDisplayWordCompact extends KabamDisplayBase {

    constructor(gokabam,the_filter,container) {


        super(gokabam,the_filter,container,'compact',false,'KabamWord');
        this._editing_kid = null;
    }

    create_parent_div(classes) {
        let click_class = this.base_id + '-gk-word-compact-clicker';
        let more_class = ['gk-word-compact', 'my-clearfix', click_class];
        classes = [].concat(classes, more_class);


        jQuery(document).off('click','.' + click_class);
        jQuery(document).on('click','.'+ click_class,function() {

        });
        return  super.create_parent_div(classes);
    }

    create_content_div(parent) {
        let content_div  = jQuery('<div class="gk-word-compact-inner"></div>');
        // noinspection JSUnresolvedFunction
        parent.append(content_div);
        return content_div;
    }

    // noinspection JSUnusedLocalSymbols
    add_child_containers(parent) {
        return []
    }

    /**
     * @link https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet
     * @link https://markdown-it.github.io/markdown-it/
     * @param parent_div
     */
    on_refresh(parent_div) {
        let that = this;
        let display_class = this.base_id + '-gk-word-compact-display';
        jQuery(document).off('click','.' + display_class);
        jQuery(document).on('click','.'+ display_class,function() {
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

        jQuery(document).on('click','.'+ display_class + ' a',function(event) {
            event.stopPropagation();
        });

        parent_div.html('');
        let object_array = this.objects;
        let object_map = {};
        let md = window.markdownit();
        for(let i =0; i < object_array.length ; i++) {
            // noinspection JSValidateTypes
            /**
             * @type {KabamWord}
             */
            let word = object_array[i];
            object_map[word.kid] = word;


            let processed_text = '';
            if (word.text) {
                processed_text = md.render(word.text);
            }

            let html = '' +
                '<div ' +
                'style="" ' +
                'data-kid = "'+ word.kid+'" ' +
                'class="gk-word-compact-innest '+ display_class +' gk-word-id-'+word.kid +'"' +
                '>' +

                '  <p class="gk-word-text"><span class="gk-word-type">' + word.type + '</span>' + processed_text + '</p> \n' +

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