/**
 * Typedef for get notification
 *
 * @callback GoKabamGetCallback
 * @param {GoKabam} go_kabam - reference to self
 * @Return {void} nothing is expected to be returned once this is called
 */

/**
 * Typedef for GoKabamDisplayRegistration
 * @typedef {object} GoKabamDisplayRegistration
 * @property {string} root_class_string :Name of Root Derived Class
 * @property {string} style   : the name of the style
 * @property {boolean}  is_multiple  : if true can handle multiple objects
 * @property {function} display_class  : the class of the display
 */


/**
 * Typedef for GoKabamContainerRegistration
 * @typedef {object} GoKabamContainerRegistration
 * @property {string|null} root_class_string :Name of Root Derived Class
 * @property {string} style   : the name of the style
 * @property {function} register_class  : the class of the display
 */


/**
 * Typedef for GoKabamEditorRegistration
 * @typedef {object} GoKabamEditorRegistration
 * @property {string} root_class_string :Name of Root Derived Class
 * @property {string} style   : the name of the style
 * @property {boolean}  is_multiple  : if true can handle multiple objects
 * @property {function} edit_class  : the class of the edit
 */


/**
 * @class
 * @param {HeartbeatErrorHandler} heartbeat_error_handler
 * @param {GoKabamGetCallback[]} get_callbacks
 * @constructor
 */
function GoKabam(heartbeat_error_handler,get_callbacks) {

    this.get_callbacks = get_callbacks.slice();
    let that = this;
    this.heartbeat = new GoKabamHeartbeat(heartbeat_error_handler);

    

    //add in a callback for when refresh is called

    /**
     * @param {HeartbeatNotification} event
     */
    function get_handler(event) {
        for(let i = 0; i < that.get_callbacks.length; i++) {
            that.get_callbacks[i](that);
        }
    }
    
    

    this.heartbeat.create_notification(get_handler,null,null);

    /**
     * @description refreshes from server
     */
    this.refresh = function() {
        this.heartbeat.get_information();
    };


    /**
     * @public
     * @description wrapper for creating a notification, will add stuff here to help coordinate
     * @param {HeartbeatNotificationCallback} callback
     * @param {object} callback_object
     * @param {KabamRuleFilter} filter
     * @return {(integer)} returns the number id
     */
    this.create_notification = function(callback,callback_object,filter) {
        return this.heartbeat.create_notification(callback,callback_object,filter);
    };

    /**
     * @public
     * stops this notification
     * @param {number} notification_id
     * @return {void}
     */
    this.cancel_notification = function(notification_id) {
        this.heartbeat.cancel_notification(notification_id);
    };

    this.update = function(root_array) {
        this.heartbeat.push_update(root_array);
    };

    /**
     *
     * @type {Object.<string, GoKabamDisplayRegistration>} this.display_registry_map
     */
    this.display_registry_map = {};

    /**
     * 
     * @param {GoKabamDisplayRegistration} entry
     * @return {void}
     *
     */
    this.register_display = function(entry) {
        let rooter = entry.root_class_string;
        let style = entry.style;
        let decorated_style = style + '_' + (entry.is_multiple? 'multiple' : 'single');
        if (!this.display_registry_map.hasOwnProperty(rooter)) {
            this.display_registry_map[rooter] = {};
        }
        if (this.display_registry_map[rooter].hasOwnProperty(decorated_style)) {
            throw new  Error('Already registered display of ' + decorated_style);    
        }
        this.display_registry_map[rooter][decorated_style] = entry;
    };

    /**
     *
     * @param root_class_string
     * @param display_style
     * @param is_multiple
     * @return {function}
     */
    this.get_display = function(root_class_string,display_style,is_multiple) {
        if (!this.display_registry_map.hasOwnProperty(root_class_string)) {
            return null
        }
        let decorated_style = display_style + '_' + (is_multiple? 'multiple' : 'single');

        if (!this.display_registry_map[root_class_string].hasOwnProperty(decorated_style)) {
            return null;
        }

        return this.display_registry_map[root_class_string][decorated_style].display_class;
    };

    //containers

    /**
     *
     * @type {Object.<string, GoKabamContainerRegistration>} this.display_registry_map
     */
    this.container_registry_map = {};

    /**
     *
     * @param {GoKabamContainerRegistration} entry
     * @return {void}
     */
    this.register_container = function(entry) {

        let rooter = entry.root_class_string;
        if (!rooter) {
            rooter = 'empty';
        }
        let style = entry.style;
        let decorated_style = style + '_' + rooter;

        this.container_registry_map[decorated_style] = entry;

    };

    /**
     * @param {string} display_style
     * @param {string=} root_type
     * @return {function}
     */
    this.get_container = function(display_style,root_type ) {
        if (!root_type) {
            root_type = 'empty';
        }
        let decorated_style = display_style + '_' + root_type;

        if (!this.container_registry_map.hasOwnProperty(decorated_style)) {
            return null
        }
        return this.container_registry_map[decorated_style].register_class;
    };

    /**
     *
     * @type {Object.<string, GoKabamEditorRegistration>} this.editor_registry_map
     */
    this.editor_registry_map = {};

    /**
     *
     * @param {GoKabamEditorRegistration} entry
     * @return {void}
     *
     */
    this.register_editor = function(entry) {
        let rooter = entry.root_class_string;
        let style = entry.style;
        let decorated_style = style + '_' + (entry.is_multiple? 'multiple' : 'single');
        if (!this.editor_registry_map.hasOwnProperty(rooter)) {
            this.editor_registry_map[rooter] = {};
        }
        if (this.editor_registry_map[rooter].hasOwnProperty(decorated_style)) {
            throw new  Error('Already registered display of ' + decorated_style);
        }
        this.editor_registry_map[rooter][decorated_style] = entry;
    };

    /**
     * will try to get that editor for the style, but if not will get it for the root class
     * @param root_class_string
     * @param edit_style
     * @param is_multiple
     * @return {function}
     */
    this.get_editor = function(root_class_string,edit_style,is_multiple) {
        if (!this.editor_registry_map.hasOwnProperty(root_class_string)) {
            return null
        }
        let decorated_style = edit_style + '_' + (is_multiple? 'multiple' : 'single');

        if (!this.editor_registry_map[root_class_string].hasOwnProperty(decorated_style)) {
            //return the first one that matches multiple for the root type
            for(let decorated in this.editor_registry_map[root_class_string]) {
                let container = this.editor_registry_map[root_class_string][decorated];
                if (container.is_multiple === is_multiple) {
                    return container.edit_class;
                }
            }
            return null;
        }

        return this.editor_registry_map[root_class_string][decorated_style].edit_class;
    };

    // noinspection JSUnusedLocalSymbols
    /**
     * @link http://dev.vast.com/jquery-popup-overlay/
     * @param {string} root_type
     * @param {string} style
     * @param {string} classes
     * @param {KabamRuleFilter} filter
     * @param {jQuery} parent
     */
    this.popout_container = function(root_type,style,classes,filter,parent) {

        let container_class = jQuery.GoKabam.get_container(style,root_type);
        if (!container_class) {
            throw new Error("Could not find container for " + root_type + " and style " + style);
        }

        let container = new container_class(this,[classes],filter);
        let container_div = container.div;
        let popout_div_name = container.base_id + '-popout';
        jQuery('body').append("<div id='"+popout_div_name+"' class='gk-generic-popup'></div>");
        let new_guy = jQuery('#' + popout_div_name);
        let cheat = jQuery("<div class='gk-inner-popout'></div>");
        cheat.append(container_div);
        new_guy.append(cheat);
        new_guy.append('<button class=" btn btn-primary '+ popout_div_name +'_close"><i class="fas fa-window-close"></i> Close</button>');
        new_guy.popup({
          //  type: 'tooltip',
            opacity: 0.3,
            transition: 'all 0.3s',
            autozindex: true,
            blur: false,
            backgroundactive: true,
            keepfocus: false,
          //  tooltipanchor: parent,
        });
        new_guy.draggable();
        new_guy.popup('show');
    }
}




jQuery(function($){


    /**
     * @param {GKA_Exception_Info|string} exception_info - Exception information
     */
    function error_handler(exception_info) {
        console.log(exception_info);
    }

    let is_initialized = false; //for one time action

    /**
     *
     * @param {GoKabam} go_kabam
     */
    function on_get(go_kabam) {
        if (is_initialized === false) {
            is_initialized = true;

            if (typeof gk_root_start === "function")
            {
                try {
                    gk_root_start();
                } catch(error) {
                    jQuery.GokabamErrorLogger(error,'error');
                }
            }
        }
    }

    if (!jQuery.GoKabam) {
        jQuery.GoKabam = new GoKabam(error_handler,[on_get]);
    }



    jQuery.GoKabam.refresh();






    ///////////////////////////////////////////////////
    ////////////////Containers//////////////////////////
    /////////////////////////////////////////////////


    // noinspection JSCheckFunctionSignatures
    jQuery.GoKabam.register_container({style: 'minimal',register_class: KabamContainerMinimalSingle});

    // noinspection JSCheckFunctionSignatures
    jQuery.GoKabam.register_container({style: 'wide',register_class: KabamContainerWordWide,root_class_string: 'KabamWord'});

    // noinspection JSCheckFunctionSignatures
    jQuery.GoKabam.register_container({style: 'compact',register_class: KabamContainerWordCompact,root_class_string: 'KabamWord'});


    // noinspection JSCheckFunctionSignatures
    jQuery.GoKabam.register_container({style: 'wide',register_class: KabamContainerTagWide,root_class_string: 'KabamTag'});

    // noinspection JSCheckFunctionSignatures
    jQuery.GoKabam.register_container({style: 'wide',register_class: KabamContainerJournalWide,root_class_string: 'KabamJournal'});

    // noinspection JSCheckFunctionSignatures
    jQuery.GoKabam.register_container({style: 'compact',register_class: KabamContainerJournalCompact,root_class_string: 'KabamJournal'});

    // noinspection JSCheckFunctionSignatures
    jQuery.GoKabam.register_container({style: 'wide',register_class: KabamContainerVersionWide,root_class_string: 'KabamVersion'});

    // noinspection JSCheckFunctionSignatures
    jQuery.GoKabam.register_container({style: 'compact',register_class: KabamContainerVersionCompact,root_class_string: 'KabamVersion'});




    ///////////////////////////////////////////////////
    ////////////////Displays//////////////////////////
    /////////////////////////////////////////////////


    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamDisplayRegistration} entry
     */
    let entry = {
        root_class_string : 'KabamWord',
        style : 'minimal',
        is_multiple : false,
        display_class : KabamDisplayWordMinimal
    };

    jQuery.GoKabam.register_display(entry);


    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamDisplayRegistration} entry
     */
    entry = {
        root_class_string : 'KabamWord',
        style : 'wide',
        is_multiple : false,
        display_class : KabamDisplayWordWide
    };

    jQuery.GoKabam.register_display(entry);


    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamDisplayRegistration} entry
     */
    entry = {
        root_class_string : 'KabamWord',
        style : 'compact',
        is_multiple : false,
        display_class : KabamDisplayWordCompact
    };

    jQuery.GoKabam.register_display(entry);


    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamDisplayRegistration} entry
     */
    entry = {
        root_class_string : 'KabamTag',
        style : 'wide',
        is_multiple : true,
        display_class : KabamDisplayTagWide
    };

    jQuery.GoKabam.register_display(entry);




    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamDisplayRegistration} entry
     */
    entry = {
        root_class_string : 'KabamJournal',
        style : 'wide',
        is_multiple : false,
        display_class : KabamDisplayJournalWide
    };

    jQuery.GoKabam.register_display(entry);


    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamDisplayRegistration} entry
     */
    entry = {
        root_class_string : 'KabamJournal',
        style : 'compact',
        is_multiple : false,
        display_class : KabamDisplayJournalCompact
    };

    jQuery.GoKabam.register_display(entry);


    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamDisplayRegistration} entry
     */
    entry = {
        root_class_string : 'KabamVersion',
        style : 'wide',
        is_multiple : false,
        display_class : KabamDisplayVersionWide
    };

    jQuery.GoKabam.register_display(entry);

    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamDisplayRegistration} entry
     */
    entry = {
        root_class_string : 'KabamVersion',
        style : 'compact',
        is_multiple : false,
        display_class : KabamDisplayVersionCompact
    };

    jQuery.GoKabam.register_display(entry);


    //////////////////////////////////////////////////
    ////////////////Editors//////////////////////////
    /////////////////////////////////////////////////


    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamEditorRegistration} edit_entry
     */
    let edit_entry = {
        root_class_string : 'KabamWord',
        style : 'minimal',
        is_multiple : false,
        edit_class : KabamEditorWordSingle
    };


    jQuery.GoKabam.register_editor(edit_entry);



    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamEditorRegistration} edit_entry
     */
    let edit_journal = {
        root_class_string : 'KabamJournal',
        style : 'minimal',
        is_multiple : false,
        edit_class : KabamEditorJournalSingle
    };


    jQuery.GoKabam.register_editor(edit_journal);


    // noinspection JSValidateTypes
    /**
     *
     * @type {GoKabamEditorRegistration} edit_entry
     */
    let edit_version = {
        root_class_string : 'KabamVersion',
        style : 'minimal',
        is_multiple : false,
        edit_class : KabamEditorVersionSingle
    };


    jQuery.GoKabam.register_editor(edit_version);



});


