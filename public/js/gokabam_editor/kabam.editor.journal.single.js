
class KabamEditorJournalSingle extends KabamEditorBSDialogBase {

    constructor(gokabam,roots,editor_callback_object) {
        let title = 'Edit Journal';
        let root_type = 'KabamJournal';


        if (roots.length === 0) {
            title = "Insert Journal";
            roots = [new KabamJournal(null) ];
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
     * @return {KabamJournal[]}
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

        let journal = this.objects[0];


        let text = journal.text;
        if (!text) {text = '';}

        let kid_string = journal.kid;
        if (!kid_string) { kid_string = 'blank';}
        let special_name = 'gk-journal-id-'+kid_string;
        special_name = jQuery.GokabamIds.register(special_name);
        jQuery(document).off('change','.' + special_name);


        jQuery(document).on('change','.gk-rf-text.' + special_name,function() {
            journal.text = $(this).val();
        });

        //add on handlers before creation, but also call off for multiple calls on this object


        // noinspection JSUnresolvedFunction
        div.append(
            '<div class="form-group">\n' +
            '  <label for="gk-single-journal-edit-input-text">Text</label>\n' +
            '  <textarea rows="20" class="form-control gk-single-journal-edit-input gk-rf-text  '+ special_name +'" id="gk-single-journal-edit-input-text" >'
            + text + '</textarea>\n' +
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