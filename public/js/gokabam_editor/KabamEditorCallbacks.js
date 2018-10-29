/**
 * Used for shared callbacks from the editor
 * meant to have classes derived from this to add in the methods and provide an interface
 * @abstract
 */
class KabamEditorCallbacks {

    constructor() {

    }

    /**
     * @abstract
     * @param {KabamEditorBase} editor
     */
    on_edit_delete(editor) {

    }


    /**
     * @abstract
     * @param {KabamEditorBase} editor
     */
    on_edit_starting(editor) {

    }

    /**
     * @abstract
     * @param {KabamEditorBase} editor
     */
    on_edit_submit(editor) {

    }

    /**
     * @abstract
     * @param {KabamEditorBase} editor
     */
    on_edit_cancel(editor) {

    }

}