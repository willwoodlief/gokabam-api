
class KabamDisplayVersionWide extends KabamDisplayBase {


    constructor(gokabam,the_filter,container) {

        super(gokabam,the_filter,container,'wide',false,'KabamVersion');
        this._editing_kid = null;
    }

    create_parent_div(classes) {
        classes.push('gk-version-wide my-clearfix');
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
        let journal_class = base_id + '-journal-holder';
        let content_class = base_id + '-content-holder';

        let html =
            '<div class="gk-version-miniframe  my-clearfix">\n' +
            '    <div class="col-md-2 col-sm-12 version-container '+ word_class +'"></div>\n' +
            '    <div class="col-md-5 col-sm-12  '+ content_class+'"></div>\n' +
            '    <div class="col-md-5 col-sm-12 version-container '+ journal_class+'"></div>\n' +


            '    <div class="col-md-12 col-sm-12  version-container '+ tag_class+ '"></div>\n' +
            '</div>'
        ;
        // noinspection JSUnresolvedFunction
        parent.append(html);
        return parent.find('.' + content_class);
    }

    // noinspection JSUnusedLocalSymbols
    add_child_containers(parent) {
        let version = this.objects[0];
        let base_id = this.base_id;
        let word_class = base_id + '-word-holder';
        let tag_class = base_id + '-tag-holder';
        let journal_class = base_id + '-journal-holder';
        
        //////////////////////////////////////////////////////////

        let tag_type_regex = /^tag_\w+$/;

        let tag_filter = {
            rules:[
                {
                    property_name: 'kid',
                    property_value: tag_type_regex
                },
                {
                    property_name: 'parent',
                    property_value: version.kid
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
                    property_value: version.kid
                }
            ],
            literals: [
            ]
        };

        let word_container = new container_class(jQuery.GoKabam,['gk-test-test'], word_filter, false);
        parent.find('div.'+word_class).append(word_container.div);


        //////////////////////////
        let journal_container_class = jQuery.GoKabam.get_container('compact','KabamJournal');
        //make test container with word
        // version_YD53eP
        let journal_type_regex = /^journal_\w+$/;
        /**
         * @type {KabamRuleFilter}
         */
        let journal_filter = {
            rules:[
                {
                    property_name: 'kid',
                    property_value: journal_type_regex
                },
                {
                    property_name: 'parent',
                    property_value: version.kid
                }
            ],
            literals: [
            ]
        };

        let journal_container = new journal_container_class(jQuery.GoKabam,['gk-test-test'], journal_filter, false);
        parent.find('div.'+journal_class).append(journal_container.div);

        return [tag_container,word_container, journal_container]
    }

    on_refresh(parent_div) {
        let that = this;
        let display_class = this.base_id + '_gk-journal-wide-display';
        jQuery(document).off('click','.' + display_class);
        //todo do not open this dialog if clicking on a link
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
        function addhttp(url) {
            if (!/^(f|ht)tps?:\/\//i.test(url)) {
                url = "http://" + url;
            }
            return url;
        }

        for(let i =0; i < object_array.length ; i++) {
            // noinspection JSValidateTypes
            /**
             * @type {KabamVersion}
             */
            let version = object_array[i];
            object_map[version.kid] = version;
            let other_site_url = '';
            if (version.website_url) {
                other_site_url = addhttp(version.website_url);
            }

            let repo_url = '';
            if (version.git_repo_url) {
                repo_url = addhttp(version.git_repo_url);
            }


/*
 this.website_url = null;
            this.post_id = null;
            this.git_repo_url = null;
            this.git_tag = null;
            this.git_commit_id = null;
            this.text = null;
 */
            let html = '' +
                '<div ' +
                    'style="" ' +
                    'data-kid = "'+ version.kid+'" ' +
                    'class=" gk-version-content '+ display_class +' gk-version-id-'+version.kid +'"' +
                '>' +

                '  <div class="gk-text"> <span class="gk-title">Version Name</span> <p >'+ version.text+'</p></div>\n' ;

            if (version.post_url) {
                html += '  <div class="gk-post-id"> <span class="gk-title">Post</span>' +
                    ' <a target="_blank" href="'+ version.post_url+'">'+ version.post_title+' </a></div>\n' ;
            } else {
                html += '  <div class="gk-post-id"> <span class="gk-missing"> No Post Set Yet </span></div>\n' ;
            }

            if (other_site_url) {
                html +=    '  <div class="gk-website-url" > <span class="gk-title">Reference</span>' +
                    ' <a  target="_blank" href="'+ other_site_url+'">'+ other_site_url +' </a></div>\n' ;
            } else {
                html += '  <div class="gk-website-url"> <span class="gk-missing"> No Reference Site Set Yet </span></div>\n' ;
            }

            if (repo_url) {
                html += '  <div class="gk-repo-url"> <span class="gk-title">Git</span> ' +
                    '<a  target="_blank" href="'+ repo_url+'">'+ repo_url+'</a></div>';
            } else {
                html += '  <div class="gk-repo-url"> <span class="gk-missing"> No Repo Url Set Yet </span></div>\n' ;
            }


            html += '</div>';

            parent_div.append(html);
        }
    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_starting(editor) {
        let div_class = '.gk-version-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.addClass('gk-on-edit');

    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_submit(editor) {
        let div_class = '.gk-version-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.removeClass('gk-on-edit');
    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_cancel(editor) {
        let div_class = '.gk-version-id-' + this._editing_kid;
        let div = jQuery(div_class);
        div.removeClass('gk-on-edit');
    }


}


