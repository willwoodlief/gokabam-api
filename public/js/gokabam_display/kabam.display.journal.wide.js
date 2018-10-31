
class KabamDisplayJournalWide extends KabamDisplayBase {

//todo have gateway return the changed parents, as well as the changed children, on an update
//todo walk up the parents list for each starting node, and add just the parents. But special cases add use case which uses api and sql parts which reference elements (anything which does a cursor in the triggers
    //todo or easier, use the change log
    //todo layout, change layout for words and position tags, better journal presentation

    constructor(gokabam,the_filter,container) {

        super(gokabam,the_filter,container,'wide',false,'KabamJournal');
        this._editing_kid = null;
    }

    create_parent_div(classes) {
        classes.push('gk-journal-wide my-clearfix');
        let div =   super.create_parent_div(classes);
        let base_id = this.base_id;
        let word_class = base_id + '_word_holder';
        let tag_class = base_id + '_tag_holder';
        let content_class = base_id + '_content_holder';

        let html =
            '<div class="gk-journal-miniframe">\n' +
            '    <div class="col-md-4 col-sm-12 '+ word_class +'"></div>\n' +
            '    <div class="col-md-8 col-sm-12 '+ content_class+'"></div>\n' +
            '    <div class="col-md-12 col-sm-12 '+ tag_class+ '"></div>\n' +
            '</div>'
           ;
        div.append(html);
        return div;
    }

    create_content_div(parent) {
        return  super.create_content_div(parent);
    }

    // noinspection JSUnusedLocalSymbols
    add_child_containers(parent) {
        let journal = this.objects[0];
        let base_id = this.base_id;
        let word_class = base_id + '_word_holder';
        let tag_class = base_id + '_tag_holder';



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

        let tag_container_class = $.GoKabam.get_container('wide','KabamTag');
        let tag_container = new tag_container_class($.GoKabam,['gk-tag-of-journal'], tag_filter, false);
        parent.find('div.'+tag_class).append(tag_container.div);

        //////////////////////////
        let container_class = $.GoKabam.get_container('wide','KabamWord');
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

        let word_container = new container_class($.GoKabam,['gk-test-test'], word_filter, false);
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
        for(let i =0; i < object_array.length ; i++) {
            // noinspection JSValidateTypes
            /**
             * @type {KabamJournal}
             */
            let journal = object_array[i];
            object_map[journal.kid] = journal;


            let html = '' +
                '<div ' +
                    'style="" ' +
                    'data-kid = "'+ journal.kid+'" ' +
                    'class="gk-journal '+ display_class +' gk-journal-id-'+journal.kid +'"' +
                '>' +
                        '  <div class="col-md-12  col-sm-12">\n' +
                            '    <div class="gk-journal-text">' + journal.text + '</div> \n' +
                        '  </div>'+
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


