class KabamDisplayJournalCompact extends KabamDisplayBase {

    constructor(gokabam,the_filter,container) {


        super(gokabam,the_filter,container,'compact',false,'KabamJournal');
        this._editing_kid = null;
    }

    create_parent_div(classes) {


        let click_class = this.base_id + '-gk-journal-compact-clicker';
        let more_class = ['gk-journal-compact', 'my-clearfix', click_class];
        classes = [].concat(classes, more_class);

        jQuery(document).off('click','.' + click_class);
        jQuery(document).on('click','.'+ click_class,function() {

        });
        return  super.create_parent_div(classes);

    }

    create_content_div(parent) {
        let base_id = this.base_id;
        let tag_class = base_id + '-tag-holder';
        let content_class = base_id + '-content-holder';



        let html =
            '<div class="gk-compact-journal-miniframe '+ '' +' my-clearfix">\n' +

            '    <div class=" '+ content_class+'"></div>\n' +

            '    <div class=" gk-compact-journal-container '+ tag_class+ '"></div>\n' +
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

        return [tag_container]
    }

    on_refresh(parent_div) {
        let that = this;
        let display_class = this.base_id + '-gk-journal-compact-display';
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
             * @type {KabamJournal}
             */
            let journal = object_array[i];
            object_map[journal.kid] = journal;
            let journal_title = this.get_journal_title(journal);
            if (!journal_title) {
                journal_title = '';
            }

            let processed_text = '';
            if (journal.text) {
                processed_text = md.render(journal.text);
            }

            let html = '' +
                '<div ' +
                'style="" ' +
                'data-kid = "'+ journal.kid+'" ' +
                'class="gk-journal-compact-innest '+ display_class +' gk-journal-id-'+journal.kid +'"' +
                '>' ;
            if (journal_title) {
                html += '  <div class="gk-journal-title">' + journal_title + '</div> \n';
            } else {
                html += '  <div class="gk-journal-title"> Missing Title </div> \n';
            }

            html +=    '  <p class="gk-journal-text">' + processed_text + '</p> \n' +

                '</div>';


            parent_div.append(html);
        }
    }

    /**
     * If a title is set, will return it, else null
     * @param  {KabamJournal} journal
     * @return {string|null}
     */
    get_journal_title(journal) {
        let lib = this.gokabam.heartbeat.everything.library;
        for(let i =0; i < journal.words.length; i++) {


            let kid_word = journal.words[i];

            if (!lib.hasOwnProperty(kid_word)) {
                throw new Error("Everything does not have the word kid["+ kid_word +"] that is in the journal reference ")
            }
            /**
             * @type {KabamWord} w
             */
            let w = lib[kid_word];
            if (w.type === 'title') {
                return w.text;
            }
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