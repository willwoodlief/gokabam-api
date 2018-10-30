/**
 * @class
 * KabamDisplayBase , implements some common message handling and setup
 *


 get_edit(),   static is multiple
 display is responsible for having a way to click or get the edit
 a display can have some words as part of the element, but needs to have a way to edit all words too
 */
class KabamDisplayBase extends KabamEditorCallbacks {

    /**
     * all derived classes should set _style in the constructor
     * @return {string}
     */
     get style() { return this._style;}


    /**
     * if true then this is designed to handle multiple objects
     * else it is only meant to display a single object
     *
     * all derived classes need set is is_multiple in the constructor
     * @return {boolean}
     */
     get is_multiple() { return this._is_multiple;}

    /**
     * derived classes need to set root_type in the constructor
     * @return {string}
     */
    get root_type() { return this._root_type;}

    /**
     * @return {GoKabam}
     */
    get gokabam() { return this._gokabam;}

    get base_id() { return this._base_id;}

    /**
     * @param {GoKabam} gokabam
     * @param {KabamRuleFilter} the_filter
     * @param {KabamContainerBase} container
     * @param {string} style
     * @param {boolean} is_multiple
     * @param {string}  root_type
     */
    constructor(gokabam,the_filter,container,style,is_multiple,root_type) {

        super();
        /**
         * @type {KabamRuleFilter} this._filter
          */
        this._filter = jQuery.extend(true,{},the_filter);
        this._roots = [];
        this._gokabam = gokabam;
        this._parent_div = null;
        this._content_div = null;
        this._parent_div_classes = ['gokabam-display'];
        this._style = style;
        this._root_type  = root_type;

        let ugly_base_name = this.constructor.name;
        let camel_array = ugly_base_name.split(/(?=[A-Z])/);

        let lower_array = [];
        for(let i=0; i < camel_array.length; i++) {
            lower_array.push(camel_array[i].toLowerCase());
        }
        let pretty_base_name = lower_array.join('_');//upper case separated by underscore, and all lower case

        this._base_id = $.GokabamIds.register(pretty_base_name);

        /**
         * @type {boolean}
         */
        this._is_multiple = is_multiple;

        this._container = container;

        /**
         * children need to add any dependent containers to this for proper clean up
         * @type {KabamContainerBase[]}
         */
        this._child_containers = [];
        /**
         *
         * @type {number} notification_id
         */
        this.notification_id = 0;

        gokabam.create_notification(this.on_notify,this,this._filter);
    }


    /**
     * @return {jQuery}
     */
    get div() { return this._parent_div;}

    get content_div() { return this._content_div;}

    get child_containers() { return this._child_containers;}

    get filter() { return this._filter;}

    /**
     *
     * @return {KabamRoot[]} returns derived classes from root, which are displayed
     */
    get objects() { return this._roots;}

    /**
     *
     * @return {string[]}
     */
    get classes() { return this._parent_div_classes;}


    /**
     * 1) cancel the notification
     * 2) go through container list and call remove
     * 2) clear out any contents
     * 3)  call container on_display_destroyed

     */
    remove() {
        //cancel the notifications
        this._gokabam.cancel_notification(this.notification_id);
        this._parent_div.hide();
        this.remove_child_containers();
        this._parent_div.html('');
        this._roots = [];
        this._container.on_display_destroyed(this);
    }


    refresh() {
        this.on_refresh(this.content_div);
        this.refresh_containers();
    }

    // noinspection JSMethodCanBeStatic
    /**
     * @abstract
     * draw is called to create the display from the roots and the parent_div
     * the div should be visible here, but it depends how the container handles it
     * its safest to just html('') out the parent div and start fresh each time
     */
    on_refresh(div) {
        div.show();
    }


    /**
     * @abstract
     * creates the parent div
     * override this to add more classes, and either call super or create the div here
     *   if not calling super , set the parent_div_classes to all that is being used
     *
     * @param {string[]} classes
     *  @return {jQuery}
     */
    create_parent_div(classes) {
        let parent_div  = jQuery('<div></div>');
        let class_string = classes.join(' ');
        parent_div.addClass(class_string);
        parent_div.hide();
        return parent_div;
    }

    // noinspection JSMethodCanBeStatic
    /**
     * @abstract
     * creates the div which holds the content
     * the base simply makes a plain div and appends it to the parent
     * The derived at call the base and then add classes and children
     *  @param {jQuery} parent_div
     *  @return {jQuery}
     */
    create_content_div(parent_div) {
        let content_div  = jQuery('<div></div>');
        // noinspection JSUnresolvedFunction
        parent_div.append(content_div);
        return content_div;
    }

    // noinspection JSMethodCanBeStatic
    /**
     * @abstract
     * @param {jQuery} parent_div
     * add containers to
     * @see this._child_containers
     * by returning array of containers added
     * this function needs to place them in parent div and position them with classes or style
     */
    add_child_containers(parent_div) {
        return [];
    }

    refresh_containers() {
        for(let i = 0; i < this.child_containers; i++) {
            this.child_containers[i].refresh();
        }
    }

    remove_child_containers() {
        for(let i = 0; i < this.child_containers; i++) {
            this.child_containers[i].remove();
        }
    }



    /**
     * the class will register this function in the constructor
     * then will dispatch the messages to the different handlers
     *
     * the different actions are: inserted|updated|deleted|added-to-filter|removed-from-filter|init
     * @param {HeartbeatNotification} event
     */
    on_notify(event) {
        switch (event.action) {
            case 'init' : {
                this.on_event_init(event) ;
                break;
            }
            case 'inserted' : {
                this.on_event_inserted(event) ;
                break;
            }
            case 'updated' : {
                this.on_event_updated(event) ;
                break;
            }
            case 'deleted' : {
                this.on_event_deleted(event) ;
                break;
            }
            case 'added-to-filter' : {
                this.on_event_added_to_filter(event) ;
                break;
            }
            case 'removed-from-filter': {
                this.on_event_removed_from_filter(event);
                break;
            }
            default: {
                throw new Error('Display base did not have a case for the action of ' + event.action);
            }
        }
    }

    /**
     * @private
     * internal function to set up the divs, will call overrides
     * Will only be called once, after the first object given
     * @return {void}
     */
    set_up_divs() {
        if (this.div) {
            this.refresh();
            return; //do not set up if already created parent div
        }
        this._parent_div = this.create_parent_div(this._parent_div_classes);
        this._content_div = this.create_content_div(this._parent_div);
        this._child_containers = this.add_child_containers(this._parent_div);
        this._container.on_new_display(this);

        this.refresh();
    }

    /**
     * this is the first time the roots are seen, so create a new array and copy anything given
     * afterwards, call draw
     * @private
     * @param {HeartbeatNotification} event
     */
    on_event_init(event) {
        this.notification_id = event.notification_id;
        this._roots = [];
        for(let i = 0; i < event.targets.length; i++) {
            let root = event.targets[i];
            let new_root = new root.constructor(root);
            this._roots.push(new_root);
        }
        this.set_up_divs();
    }

    /**
     * these are new roots, and the root array already exists, so just add on the new copies of stuff
     * afterwards, call draw
     * send message to container object that we have been changed
     * @private
     * @param {HeartbeatNotification} event
     */
    on_event_inserted(event) {
        for(let i = 0; i < event.targets.length; i++) {
            let root = event.targets[i];
            let new_root = new root.constructor(root);
            this._roots.push(new_root);
        }
        this.set_up_divs();
        this._container.on_display_changed(this);
    }

    /**
     * Updates what we already have copies of, so find the kid, and overwrite the older with a copy of the newer
     * We will keep the roots array in the same order, in case that is important to any derived classes
     * call draw
     * send message to container object that we have been changed
     * @private
     * @param {HeartbeatNotification} event
     */
    on_event_updated(event) {

        //avoid nested loops (in case we have a large sets )
        let root_map = {};
        for(let i = 0; i < this._roots.length; i++) {
            root_map[this._roots[i].kid] = {root: this._roots[i], index: i};
        }

        for(let i = 0; i < event.targets.length; i++) {
            let root = event.targets[i];
            let kid = root.kid;
            if (!root_map.hasOwnProperty(kid)) {
                throw new Error('Display Base\'s root map does not have the kid of ' + kid + ' during the update');
            }
            this._roots[root_map[kid].index] = new root.constructor(root);
        }
        this.refresh();
        this._container.on_display_changed(this);
    }

    /**
     * remove the deleted from roots, if roots empty then we destroy this draw object

     * @private
     * @param {HeartbeatNotification} event
     */
    on_event_deleted(event) {
        if (event.targets.length === this._roots.length) {
            //we are done, everything got deleted
            this.remove();

        } else {
            //remove deleted from the roots

            let root_map = {};
            for(let i = 0; i < event.targets.length; i++) {
                root_map[event.targets[i].kid] = event.targets[i];
            }

            let new_roots = [];
            for(let i = 0; i < this._roots.length; i++) {
                let our_kid = this._roots[i].kid;
                if (!root_map.hasOwnProperty(our_kid)) {
                    new_roots.push(this._roots[i]);
                }
            }

            this._roots = new_roots;

            this.refresh();
            this._container.on_display_changed(this);
        }
    }

    /**
     * @private
     * This is treated like an insert, even though it really means a root's properties got changed enough to add it to the set
     * @param {HeartbeatNotification} event
     */
    on_event_added_to_filter(event) {
        for(let i = 0; i < event.targets.length; i++) {
            let root = event.targets[i];
            let rootlet = new root.constructor(root);
            this._roots.push(rootlet);
        }
        this.refresh();
        this._container.on_display_changed(this);
    }

    /**
     * This acts exactly like the on deleted above
     * @private
     * @param {HeartbeatNotification} event
     */
    on_event_removed_from_filter(event) {
        if (event.targets.length === this._roots.length) {
            //we are done, everything got deleted
            this.remove();
        } else {
            //remove deleted from the roots

            let root_map = {};
            for(let i = 0; i < event.targets.length; i++) {
                root_map[event.targets[i].kid] = event.targets[i];
            }

            let new_roots = [];
            for(let i = 0; i < this._roots.length; i++) {
                let our_kid = this._roots[i].kid;
                if (!root_map.hasOwnProperty(our_kid)) {
                    new_roots.push(this._roots[i]);
                }
            }

            this._roots = new_roots;

            this.refresh();
            this._container.on_display_changed(this);

        }
    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_starting(editor) {

    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_submit(editor) {

    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_cancel(editor) {

    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_delete(editor) {

    }


}