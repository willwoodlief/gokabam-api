/**
 * Containers can belong to displays or not, all they know is their parent div and which filters they use
 */
class KabamContainerBase extends KabamEditorCallbacks {

    /**
     * all derived classes should set style in the super constructor
     * @return {string}
     */
    get style() { return this.container_style;}

    //called by things creating this container, to place the div on the correct place
    get div() { return this.outer_div;}

    get base_id() { return this._base_id;}

    get displays() { return this._display_list;}
    /**
     * @param {GoKabam} gokabam
     * @param {string[]} css_class_array
     * @param {KabamRuleFilter} filter
     * @param {boolean} prefer_multiple
     * @param {string} style
     */

    constructor(gokabam,css_class_array, filter, prefer_multiple,style) {
        super();
        this.container_style = style;
        this.prefer_multiple = prefer_multiple;
        this.filter = filter;

        /**
         *
         * @type {Object.<string, KabamDisplayBase[]>} this.display_list
         */
        this._display_list = {};
        this.gokabam = gokabam;

        let ugly_base_name = this.constructor.name;
        let camel_array = ugly_base_name.split(/(?=[A-Z])/);

        let lower_array = [];
        for(let i=0; i < camel_array.length; i++) {
            lower_array.push(camel_array[i].toLowerCase());
        }
        let pretty_base_name = lower_array.join('_');//upper case separated by underscore, and all lower case

        this._base_id = $.GokabamIds.register(pretty_base_name);


        if (css_class_array.indexOf('gokabam-container') < 0) {
            css_class_array.unshift('gokabam-container');
        }
        this.outer_div = this.create_outer_div(css_class_array);

        this.container_div = this.create_container_div(this.outer_div); //


        /**
         *
         * @type {number} notification_id
         */
        this.notification_id = 0;

        /**
          @type {Object.<string, KabamRoot>} this.object_map
         */

        this.object_map = {};

        gokabam.create_notification(this.on_notify,this,filter);
    }

    // noinspection JSMethodCanBeStatic
    /**
     * can override this to create the parent div, add classes and create the none display area stuff
     * @abstract
     * @param {string[]} class_array
     * @return {jQuery}
     */
    create_outer_div(class_array) {
        let div = jQuery('<div></div>');
        let class_string = class_array.join(' ');
        div.addClass(class_string);
        div.addClass('gk-container');
        return div;
    }

    // noinspection JSMethodCanBeStatic
    /**
     * @abstract
     * @param parent_div
     * @return {jQuery}
     */
    create_container_div(parent_div) {
        let div = jQuery('<div></div>');
        parent_div.append(div);
        div.addClass('gk-container-content');
        return div;
    }


    /**
     * derived can implement their own clean up, but should call base class
     * cancel the notification
     * go through list of displays and call their remove
     *  erase the contents of container div
     */
    remove() {
        this.gokabam.cancel_notification(this.notification_id);
        for(let root_type in this._display_list) {
            if (! this._display_list.hasOwnProperty(root_type)) {continue;}
            let ar = this._display_list[root_type];
            for(let i =0; i < ar.length; i++) {
                let display = ar[i];
                display.remove();
            }
        }
        this.container_div.html('');
        this._display_list = {};
        this.object_map = {};
    }

    /**
     *
     * @param {KabamRoot[]} root_list
     * @return {KabamDisplayBase[]} ; return empty array if nothing is created, or will return the new display array
     */
    maybe_create_displays(root_list) {

        //we are going to organize the root list by root class type
        /**
         * @type {Object.<string, KabamRoot[]>} rooters
         */
        let rooters = {};

        for(let list_index = 0; list_index < root_list.length; list_index++) {

            let root = root_list[list_index];
            //see if we have already seen this object
            if (this.object_map.hasOwnProperty(root.kid)) {
                continue;
            }

            let root_type = root.constructor.name;
            if (!rooters.hasOwnProperty(root_type)) {
                rooters[root_type] = [];
            }
            rooters[root_type].push(root);

            //this will be removed from the object map when its taken out of the filter set
            this.object_map[root.kid] = root;
        }


        for( let root_type in rooters) {
            if (!rooters.hasOwnProperty(root_type)) {continue;}

            //first check to see if there is already a multiple display created for this type, if so we are done for this root type
            if (this._display_list.hasOwnProperty(root_type)) {
                let display_array = this._display_list[root_type];
                if (display_array.length > 0) {
                    let display = display_array[0];
                    if (display.is_multiple) {
                        continue;
                    }
                }
            }

            //got here, then need to create a new display,
            // if we want multiple we request it
            // if there is a multiple display type for this style
            //      and set the rule for it and we are done with this root type

            //but if we do not care about multiple, or there is no multiple to get, then we set a new display for each
            // thing in the array of roots for this root type given in the params

            /**
             *
             * @type {function|null}
             */
            let display_class = null;

            if (this.prefer_multiple) {
                display_class = this.gokabam.get_display(root_type,this.style,true);
                if (display_class) {
                    //copy filter and add a new rule for the kid to only match this type
                    let prefix = KabamRoot.get_kid_prefix(root_type);

                    /**
                     * @type {KabamRuleFilter} new_filter
                     */
                    let new_filter = jQuery.extend(true, {}, this.filter);
                    let new_rule = {
                        property_name: 'kid',
                        property_value: new RegExp('^' + prefix + '_\\w+$')
                    };

                    new_filter.rules.push(new_rule);

                    /**
                     * @type {KabamDisplayBase} display
                     */
                    let display = new display_class(this.gokabam, new_filter,this);

                    //add the display to the display list
                    if (this._display_list.hasOwnProperty(root_type)) {
                        this._display_list[root_type].push(display);
                    } else {
                        this._display_list[root_type] = [display];
                    }

                    //we are done with this root type, made a multiple which wil handle all objects of this type now
                    // and in the future, next time we get a new object of this class,
                    continue;
                }
            }
            if (!display_class) {
                display_class = this.gokabam.get_display(root_type,this.style,false);
            }

            if (!display_class) {
                throw new Error("Could not find a display class for root type [" + root_type + "] and for style [" + this.style + "]");
            }

            //loop through each root, and make a display just for it
            let root_array = rooters[root_type];
            for(let i = 0; i < root_array.length; i++) {
                //here we make a single display
                let root = root_array[i];
                let new_filter = {
                    rules:[
                    ],
                    literals: [
                        root.kid
                    ]
                };

                let display = new display_class(this.gokabam, new_filter,this);
                //add the display to the display list
                if (this._display_list.hasOwnProperty(root_type)) {
                    this._display_list[root_type].push(display);
                } else {
                    this._display_list[root_type] = [display];
                }
            } //end for loop making a new display for each object

        }

    }



    /**
     * override to organize and other display stuff
     * at this time, the display will already be in the display list
     *  as long as the derived adds the display div to the parent div and shows the display div,
     *  then no need to call super
     * @abstract
     * @param {KabamDisplayBase} display
     * @return {void}
     */
    on_new_display(display) {
        this.container_div.append(display.div);
        display.div.show();
    }


    /**
     * @abstract  Override this to manage display space
     *           call super to remove this from the base
     *
     *  this is called as part of the remove container process too, as well as during its lifetime
     * @param {KabamDisplayBase} display
     */
    on_display_destroyed(display) {
        // remove display from list
        let root_type = display.root_type;
        if (this._display_list.hasOwnProperty(root_type)) {
            let display_array = this._display_list[root_type];
            for(let i = 0; i < display_array.length; i++) {
                let test = display_array[i];
                if (test === display) {
                    display_array.splice(i, 1);
                    break;
                }
            }
        }
    }

    /**
     * @abstract
     * called when display content is changed, base does nothing
     * @param {KabamDisplayBase} display
     */
    on_display_changed(display) {

    }


    /**
     * the class will register this function in the constructor
     * and will listen for newly added things to the filter
     * perhaps new displays will be created, depending if there is a single or multiple display for that root type made
     *
     * the different actions are: inserted|updated|deleted|added-to-filter|removed-from-filter|init
     * @param {HeartbeatNotification} event
     */
    on_notify(event) {
        switch (event.action) {
            case 'init' : {
                this.notification_id = event.notification_id;
                this.maybe_create_displays(event.targets);
                break;
            }

            case 'added-to-filter' :
            case 'inserted' : {
                this.maybe_create_displays(event.targets);
                break;
            }


            case 'removed-from-filter':
            case 'deleted' : {
                //remove objects from map, so if they are added again, will see if need new display
                //sometimes things come and go from a filter set
                for(let i=0; i < event.targets.length; i++) {
                    let root = event.targets[i];
                    delete this.object_map[root.kid];
                }
                break;
            }


            default: {
                return;
            }
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

