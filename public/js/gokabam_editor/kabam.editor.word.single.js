
class KabamEditorWordSingle extends KabamEditorBSDialogBase {

    constructor(gokabam,roots,editor_callback_object) {
        let title = 'Edit Word';
        let root_type = 'KabamWord';


        if (roots.length === 0) {
            title = "Insert Word";
            roots = [new KabamWord(null) ];
        } else {
            if (roots[0].constructor.name !== root_type) {
                throw new Error("This single word dialog only accepts a word to edit");
            }
        }

        if (roots.length > 1) {
            throw new Error("This single word dialog is getting more than one word to edit at a time");
        }


        let style = 'minimal';
        let is_multiple = false;

        let css_class_array = ['kabam-editor-single-word'];
        let dialog_size = BootstrapDialog.SIZE_NORMAL;
        super(gokabam,roots,editor_callback_object,style,is_multiple,root_type,css_class_array,dialog_size,title)
    }

    /**
     *
     * @return {KabamWord[]}
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

        let word = this.objects[0];
        let type = word.type;
        if (!type) {
            type = '';
        }
        let language = word.language;
        if(!language) {language = '';}
        let text = word.text;
        if (!text) {text = '';}

        let allowed_types = ['name','title','blurb','description','overview','data'];
        let options_string = '';
        for(let i = 0; i < allowed_types.length; i++) {
            options_string += '<option ';
            if (type) {

                if (type ===allowed_types[i]) {
                    options_string += ' selected="selected" ';
                }
            }
            options_string += '>'+ allowed_types[i] + '</option>\n';
        }
        let kid_string = word.kid;
        if (!kid_string) { kid_string = 'blank';}
        let special_name = 'gk-word-id-'+kid_string;
        jQuery(document).off('change','.' + special_name);
        jQuery(document).on('change','.gk-rf-type.' + special_name,function() {
            word.type = $(this).val();
        });

        jQuery(document).on('change','.gk-rf-language.' + special_name,function() {
            word.language = $(this).val();
        });

        jQuery(document).on('change','.gk-rf-text.' + special_name,function() {
            word.text = $(this).val();
        });

        //add on handlers before creation, but also call off for multiple calls on this object


        // noinspection JSUnresolvedFunction
        div.append(
            '<div class="form-group">\n' +
            '  <label for="gk-single-word-edit-input-type">Type</label>\n' +
            '  <select class="form-control gk-single-word-edit-input gk-rf-type '+ special_name +'" id="gk-single-word-edit-input-type">\n' +
            options_string +
            '  </select>' +
            '</div>' +
            '<div class="form-group">\n' +
            '  <label for="gk-single-word-edit-input-language">Language</label>\n' +
            '  <input class="form-control gk-single-word-edit-input gk-rf-language '+ special_name +'" id="gk-single-word-edit-input-language"' +
            ' maxlength="2" size="2" value="'+ language +'">\n' +
            '</div>' +
            '<div class="form-group">\n' +
            '  <label for="gk-single-word-edit-input-text">Text</label>\n' +
            '  <textarea class="form-control gk-single-word-edit-input gk-rf-text  '+ special_name +'" id="gk-single-word-edit-input-text" >'
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
        let word = this.objects[0];
        if (!word.text) {
            word.text = null;
        }
        if (!word.language) {
            word.language = null;
        }
        if (!word.type) {
            word.type = null;
        }
        super.on_submit();
    }
}