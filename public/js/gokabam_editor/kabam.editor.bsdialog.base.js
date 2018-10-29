

class KabamEditorBSDialogBase extends  KabamEditorBase{


    constructor(gokabam,roots,editor_callback_object,style,is_multiple,root_type,css_class_array,dialog_size,title) {
        super(gokabam,roots,editor_callback_object,style,is_multiple,root_type,css_class_array);

        if (!dialog_size) {
            this._dialog_size = BootstrapDialog.SIZE_NORMAL;
        } else {
            this._dialog_size = dialog_size;
        }

        if (!title) {
            this._title = '';
        } else {
            this._title = title;
        }
    }



    /**
     * @abstract
     * this is the one class that needs to be filled in by decedents
     * fills in the content div
     * each of the form fields need to have a lister that fills in the object
     * @param {jQuery} div
     * @return {void}
     */
    fill_in_content(div) {

    }


    /**
     * put jquery handlers here to update the object(s)
     * @abstract
     * @param {BootstrapDialog} dialogRef
     */
    before_dialog(dialogRef) {

    }

    /**
     * remove handlers and other cleanup here
     * @abstract
     * @param {BootstrapDialog} dialogRef
     */
    after_dialog(dialogRef) {

    }


    get dialog_size() { return this._dialog_size;}

    get title() {return this._title;}

    /**
     * make it easier for decedents
     */
    refresh() {
        this.fill_in_content(this.div);
    }


    /**
     * @public
     starts the BS Dialog with the content
     */
    edit() {
        this.refresh();
        let inner_div = this.div;
        let title = this.title;
        let that = this;
        //set up the bs editor
        BootstrapDialog.show({

            title: title,

            message: inner_div,

            size: this.dialog_size,

            data: {

            },

            onshow: function(dialogRef){
                that.before_dialog(dialogRef);
            },

            onhide : function(dialogRef){
                that.after_dialog(dialogRef);
            },
            buttons: [
                {
                    label: 'Save',
                    cssClass: 'btn-primary',
                    action: function(dialogRef){
                        that.on_submit();
                        dialogRef.close();
                    }
                },
                {
                    label: 'Close',
                    action: function(dialogItself){
                        that.on_cancel();
                        dialogItself.close();
                    }
                }

            ]
        });
    }


    /**
    cleans up the bs dialog
     */
    cleanup() {
        super.cleanup();
    }

    /**
     creates the blank div, we are having everything done in fill_in_content
     * @param {string[]} class_array
     * @return {jQuery}
     */
    create_content_div(class_array) {
        // let the base create the content div
        return super.create_content_div(class_array);
    }


}