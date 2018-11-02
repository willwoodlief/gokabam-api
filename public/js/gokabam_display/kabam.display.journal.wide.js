
class KabamDisplayJournalWide extends KabamDisplayBase {


    constructor(gokabam,the_filter,container) {

        super(gokabam,the_filter,container,'wide',false,'KabamJournal');
        this._editing_kid = null;
    }

    create_parent_div(classes) {
        classes.push('gk-journal-wide my-clearfix');
        return  super.create_parent_div(classes);
    }

    /**
     *
     * @param {jQuery} parent
     */
    create_content_div(parent) {
        let base_id = this.base_id;
        let word_class = base_id + '-word-holder';
        let tag_class = base_id + '-tag-holder';
        let content_class = base_id + '-content-holder';

        let html =
            '<div class="gk-journal-miniframe  my-clearfix">\n' +
            '    <div class="col-md-2 col-sm-12 journal-container '+ word_class +'"></div>\n' +
            '    <div class="col-md-10 col-sm-12 '+ content_class+'"></div>\n' +



            '    <div class="col-md-12 col-sm-12  journal-container '+ tag_class+ '"></div>\n' +
            '</div>'
        ;
        // noinspection JSUnresolvedFunction
        parent.append(html);
        return parent.find('.' + content_class);
    }

    // noinspection JSUnusedLocalSymbols
    add_child_containers(parent) {
        let journal = this.objects[0];
        let base_id = this.base_id;
        let word_class = base_id + '-word-holder';
        let tag_class = base_id + '-tag-holder';

        let tag_type_regex = /^tag_\w+$/;

        let tag_filter = {
            rules:[
                {
                    property_name: 'kid',
                    property_value: tag_type_regex
                },
                {
                    property_name: 'parent',
                    property_value: journal.kid
                }
            ],
            literals: [
            ]
        };

        let tag_container_class = jQuery.GoKabam.get_container('wide','KabamTag');
        let tag_container = new tag_container_class(jQuery.GoKabam,['gk-tag-of-journal'], tag_filter, false);
        parent.find('div.'+tag_class).append(tag_container.div);

        //////////////////////////
        let container_class = jQuery.GoKabam.get_container('compact','KabamWord');
        //make test container with word
        // version_YD53eP
        let type_regex = /^word_\w+$/;
        /**
         * @type {KabamRuleFilter}
         */
        let word_filter = {
            rules:[
                {
                    property_name: 'kid',
                    property_value: type_regex
                },
                {
                    property_name: 'parent',
                    property_value: journal.kid
                }
            ],
            literals: [
            ]
        };

        let word_container = new container_class(jQuery.GoKabam,['gk-test-test'], word_filter, false);
        parent.find('div.'+word_class).append(word_container.div);

        return [tag_container,word_container]
    }

    on_refresh(parent_div) {
        let that = this;
        let display_class = this.base_id + '_gk-journal-wide-display';
        jQuery(document).off('click','.' + display_class);
        jQuery(document).on('click','.'+ display_class,function() {
            //find editor for this type and single
            let editor_class = that.gokabam.get_editor(that.root_type,that.style,false);
            if (!editor_class) {
                throw new Error('Display Journal cannot find single editor for journal');
            }
            let kid = jQuery(this).data('kid');
            that._editing_kid = kid;
            if (!object_map.hasOwnProperty(kid)) {
                throw new Error("Display Journal Map does not have journal ["+ kid +"] in click handler");
            }
            let journal_to_be_edited = object_map[kid];
            let editor = new editor_class(that.gokabam,[journal_to_be_edited],that);
            editor.edit();
        });


        parent_div.html('');
        let object_array = this.objects;
        let object_map = {};
        let md = window.markdownit();
        for(let i =0; i < object_array.length ; i++) {
            // noinspection JSValidateTypes
            /**
             * @type {KabamJournal}
             */
            let journal = object_array[i];
            object_map[journal.kid] = journal;

            let processed_text = '';
            if (journal.text) {
                processed_text = md.render(journal.text);
            }


            let html = '' +
                '<div ' +
                    'style="" ' +
                    'data-kid = "'+ journal.kid+'" ' +
                    'class="gk-journal-text '+ display_class +' gk-journal-id-'+journal.kid +'"' +
                '>' +

                processed_text  +

                '</div>';

            parent_div.append(html);
        }
    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_starting(editor) {
        let div_class = '.gk-journal-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.addClass('gk-on-edit');

    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_submit(editor) {
        let div_class = '.gk-journal-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.removeClass('gk-on-edit');
    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_cancel(editor) {
        let div_class = '.gk-journal-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.removeClass('gk-on-edit');
    }


}


