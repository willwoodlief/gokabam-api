
class KabamEditorVersionSingle extends KabamEditorBSDialogBase {

    constructor(gokabam,roots,editor_callback_object) {
        let title = 'Edit Journal';
        let root_type = 'KabamVersion';


        if (roots.length === 0) {
            title = "Insert Journal";
            roots = [new KabamVersion(null) ];
        } else {
            if (roots[0].constructor.name !== root_type) {
                throw new Error("This single journal dialog only accepts a journal to edit");
            }
        }

        if (roots.length > 1) {
            throw new Error("This single journal dialog is getting more than one journal to edit at a time");
        }


        let style = 'minimal';
        let is_multiple = false;

        let css_class_array = ['kabam-editor-single-journal'];
        let dialog_size = BootstrapDialog.SIZE_NORMAL;
        super(gokabam,roots,editor_callback_object,style,is_multiple,root_type,css_class_array,dialog_size,title)
    }

    /**
     *
     * @return {KabamVersion[]}
     */
    get objects() {
        // noinspection JSValidateTypes
        return  super.objects;
    }

    /**
     * fills in the content div
     * each of the form fields need to have a lister that fills in the object
     * @param {jQuery} div
     * @return {void}
     */
    fill_in_content(div) {

        /**
         * @type {KabamVersion}
         */
        let version = this.objects[0];


        let text = version.text;
        if (!text) {text = '';}

        let website = version.website_url;
        if (!website) {website = '';}

        let repo_url = version.git_repo_url;
        if (!repo_url) {repo_url = '';}

        let post_id = version.post_id;
        if (!post_id) {post_id = '';}

        let kid_string = version.kid;
        if (!kid_string) { kid_string = 'blank';}
        let special_name = 'gk-version-id-'+kid_string;
        special_name = jQuery.GokabamIds.register(special_name);
        jQuery(document).off('change','.' + special_name);


        jQuery(document).on('change','.gk-rf-text.' + special_name,function() {
            version.text = jQuery(this).val();
        });

        jQuery(document).on('change','.gk-rf-website.' + special_name,function() {
            version.website_url = jQuery(this).val();
        });

        jQuery(document).on('change','.gk-rf-git.' + special_name,function() {
            version.git_repo_url = jQuery(this).val();
        });

        jQuery(document).on('change','.gk-rf-post.' + special_name,function() {
            version.post_id = jQuery(this).val();
        });

        //add on handlers before creation, but also call off for multiple calls on this object


        // noinspection JSUnresolvedFunction
        div.append(
            '<div class="form-group">\n' +
            '  <label for="gk-single-journal-edit-version-name">Version Name</label>\n' +
            '  <input class="form-control gk-single-journal-edit-version-name gk-rf-text '+ special_name +'" id="gk-single-journal-edit-version-name"' +
            '  size="50" value="'+ text +'">\n' +
            '</div>' +


            '<div class="form-group">\n' +
            '  <label for="gk-single-journal-edit-ref-site">Reference Website</label>\n' +
            '  <input class="form-control gk-single-journal-edit-ref-site gk-rf-website '+ special_name +'" id="gk-single-journal-edit-ref-site"' +
            '  size="50" value="'+ website +'">\n' +
            '</div>' +


            '<div class="form-group">\n' +
            '  <label for="gk-single-journal-edit-git-url">Git URL</label>\n' +
            '  <input class="form-control gk-single-journal-edit-git-url gk-rf-git '+ special_name +'" id="gk-single-journal-edit-git-url"' +
            '  size="50" value="'+ repo_url +'">\n' +
            '</div>' +


            '<div class="form-group">\n' +
            '  <label for="gk-single-journal-edit-post-id">This Site\'s Post ID</label>\n' +
            '  <input class="form-control gk-single-journal-edit-post-id gk-rf-post '+ special_name +'" id="gk-single-journal-edit-post-id"' +
            '  size="50" value="'+ post_id +'">\n' +
            '</div>' +

            '</div>'
        );
    }


    /**
     * put jquery handlers here to update the object(s)
     * @param {BootstrapDialog} dialogRef
     */
    before_dialog(dialogRef) {


    }

    /**
     * remove handlers and other cleanup here
     * @param {BootstrapDialog} dialogRef
     */
    after_dialog(dialogRef) {

    }

    on_submit() {
        let journal = this.objects[0];
        if (!journal.text) {
            journal.text = null;
        }
        super.on_submit();
    }
}