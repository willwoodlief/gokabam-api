

/**
 * Both containers and displays can call this
 * The base class allows interaction with the framework, but does nothing by itself other than these hookups
 *
 * derived classes need to implement
 * @see KabamEditorBase.refresh : adds the content to the content div (this.div)
 * @see KabamEditorBase.edit  : start up and display the edit mechanism, call super to connect refresh
 * @see KabamEditorBase.cleanup  : to clean up dialog or other resources, called after submit and cancel
 * @see KabamEditorBase.create_content_div : to initialize the dialog or framework, as well as create the empty content div
 *
 * the editor framework needs to call the object methods
 * @see KabamEditorBase.on_cancel
 * @see KabamEditorBase.on_submit
 *

 */
class KabamEditorBase {


    get div() { return this._content_div;}
    /**
     * all derived classes should set _style
     * @return {string}
     */
    get style() { return this._style;}


    /**
     * @return {KabamRoot[]}
     */
    get objects() { return this._roots;}

    /**
     * if true then this is designed to handle multiple objects
     * else it is only meant to display a single object
     *
     * all derived classes need set is multiple as they will
     * @return {boolean}
     */
    get is_multiple() { return this._is_multiple;}

    /**
     * derived classes need to set _root_type
     * @return {string}
     */
    get root_type() { return this._root_type;}

    /**
     * @param {GoKabam} gokabam
     * @param {KabamRoot[]} roots
     * @param {KabamEditorCallbacks} editor_callback_object
     * @param {string} style
     * @param {boolean} is_multiple
     * @param {string}  root_type
     * @param {string[]} css_class_array
     */
    constructor(gokabam,roots,editor_callback_object,style,is_multiple,root_type,css_class_array) {

        if (roots == null) {
            roots = [];
        }
        this._roots = [];

        //copy so that the original objects do not get changed during an aborted edit
        for(let i = 0; i < roots.length; i++) {
            let root = roots[i];
            let new_root = new root.constructor(root);
            this._roots.push( new_root);
        }


        this._gokabam = gokabam;
        this._style = style;
        this._root_type  = root_type;
        /**
         * @type {boolean}
         */
        this._is_multiple = is_multiple;
        if (!this.is_multiple) {
            if (this._roots.length > 1) {
                throw new Error("Single Editor cannot do multiple roots at the same time");
            }
        }

        this._content_div = this.create_content_div(css_class_array);

        //check roots for root type compatibility
        for(let i = 0; i < this._roots.length; i++) {
            let root = this._roots[i];
            let this_root_type = root.constructor.name;
            if (this_root_type !== this.root_type) {
                throw new Error("Incompatible root type: [" + this_root_type + "] for the editor type of ["+
                    this.root_type + "]");
            }
        }


        this._callback_object = editor_callback_object;
        this._callback_object.on_edit_starting(this);
    }

    // noinspection JSMethodCanBeStatic
    /**
     * can override this to create the content div, add classes and create the none display area stuff
     * @abstract
     * @param {string[]} class_array
     * @return {jQuery}
     */
    create_content_div(class_array) {
        let div = jQuery('<div></div>');
        let class_string = class_array.join(' ');
        div.addClass(class_string);
        return div;
    }

    /**
     * @abstract
     * fills in the content div
     * and provides the buttons or gui for submitting and canceling
     * if the object list is empty, then this means this is an insert
     */
    refresh() {

    }


    on_submit() {
        this._gokabam.update(this._roots);
        this._callback_object.on_edit_submit(this);
        this.cleanup();
    }

    on_delete() {
        for(let i =0; i < this.objects.length; i++) {
            let root = this.objects[i];
            root.delete = 1;
        }
        this._gokabam.update(this.objects);
        this._callback_object.on_edit_delete(this);
        this.cleanup();

    }

    on_cancel() {
        this._callback_object.on_edit_cancel(this);
        this.cleanup();
    }

    /**
     * @abstract
     * starts the edit gui up, derived should call the base when things are ready to display
     */
    edit() {
        this.refresh();
    }

    /**
     * @abstract
     * base version simply cleans up resources
     * called after both submit and cancel
     */
    cleanup() {
        this._content_div.html('');
        this._roots = [];
    }
}