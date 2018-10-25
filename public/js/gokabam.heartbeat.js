/*********************************************************************************
************************ Rules and Notifications
 * ********************************************************************************
 */

/**
 * @description allows a range , but with optional min and max
 * @typedef {object} HeartbeatRange
 * @property {number|null} min
 * @property {number|null} max
 */
/**
 * The rules are an important part of the rule filter, which can have 0 or more rules
 * @typedef {object} HeartbeatRule
 * @property {string} property_name
 * @property {RegExp|string|HeartbeatRange} property_value
 */

/**
 *  Matches all things in everything, if literals are included then the matching starts with those and then applied to the filters
 *  If literals are empty, then the matching starts with anything available
 *  If both rules and literals are empty, then everything is matched
 *
 * @typedef {object} RuleFilter
 * @property {HeartbeatRule[]} rules
 * @property {GKA_Kid[]} literals
 */

/**
 * Typedef for the notification data
 * each event for the rule is grouped together, so if there was an insert, a delete , and update then three notifications
 * @typedef {object} HeartbeatNotification
 * @property {string} action   : inserted|updated|deleted|added-to-filter|removed-from-filter|init
                            init is called once , right after the filter is made, and returns the initial matching objects

 * @property {RuleFilter} filter  - the filter which can be changed
 * @property {KabamRoot[]}   targets - can be empty,or 1 or more  objects derived from root which this message is about
 * @property {number} notification_id
 */



/**
 * Typedef for the notification callback
 * @callback HeartbeatNotificationCallback
 * @param {HeartbeatNotification} event
 */





/**
 * Typedef for error callback for the heartbeat
 *
 * @callback HeartbeatErrorHandler
 * @param {GKA_Exception_Info|string} exception_info - Exception information
 * @Return {void} nothing is expected to be returned once this is called
 */



/**
 * creates a new Heartbeat
 * @class
 * @param {HeartbeatErrorHandler|null} error_callback
 *
 */
function GoKabamHeartbeat (error_callback) {

    /**
     * callbackHandler will take a rule set and update the callback with any changes
     * @description :
     *    when its first called, it will apply everything from the snapshot to the ruleset and store it in ret
     *       then it will send an init
     *
     * @class
     *  a) if this is a new object, in the constructor
     *          find_objects_in_ruleset: gets the matches and we store the snapshot in rem
     *          then this goes to init and that is all we do
     *
     *  b) when the server sends a new everything, to process_everything  it will call  do this process again
     *          find_objects_in_ruleset: gets the matches and store in compare_rem
     *
     *  c) for anything that is in compare_rem that is not in rem, and its not in the inserted array,
     *                      this goes to  added-to-filter
     *
     *  d) for anything that is in rem but not in compare_rem, and its not in the deleted array,
     *                  this goes to removed-from-filter
     *
     *  e) for everything in both deleted and rem this goes to deleted callback
     *
     *  f) for everything in both inserted and in compare_rem this goes to inserted callback
     *
     *  g) for everything in both updated and in rem this goes to updated callback
     *
     *  h) set rem to what is in compare_rem
     */

    /**
     * @param {HeartbeatNotificationCallback} callback
     * @param {RuleFilter} filter

     * @param {number} notification_id
     * @param {KabamEverything} everything
     */
    function CallbackHandler(callback,filter,notification_id,everything) {

        /**
         * @type {KabamRoot[]} rem
         */
        this.rem = find_objects_in_ruleset(everything,filter);
        send_to_callback('init',this.rem);


        /**
         @public
         @description Called to update the callback with a new everything, or simply to redo the ruleset with the same
                        everything this class was initialized with

         * @param {KabamEverything} everything
         * @return {void}
         */
        this.process_everything = function(everything) {

            //get the current set of objects that match this rule (filter from the constructor params)
            let compare_rem = find_objects_in_ruleset(everything,filter);

            //the two arrays are arrays of objects that have a unique property: kid, so make two arrays of kids
            let rem_kids = [];


            let rem_hash = {};
            for(let i = 0; i < this.rem.length; i++) {
                rem_kids.push( this.rem[i].kid);
                rem_hash[this.rem[i].kid] = this.rem[i];
            }

            let compare_rem_hash = {};
            let compare_rem_kids = [];
            for(let i = 0; i < compare_rem.length; i++) {
                compare_rem_kids.push( compare_rem[i].kid);
                compare_rem_hash[compare_rem[i].kid] = compare_rem[i];
            }



            // for anything that is in compare_rem that is not in rem, and its not in the inserted array,this goes to  added-to-filter
            let in_compare_but_not_rem = compare_rem_kids.filter(x => !rem_kids.includes(x));

            if (in_compare_but_not_rem.length > 0 ) {
                let objects_to_send = [];
                for(let i=0; i < in_compare_but_not_rem.length; i++) {
                    objects_to_send.push (compare_rem_hash[in_compare_but_not_rem[i]]);
                }
                send_to_callback('added-to-filter',objects_to_send);
            }


            {
                // for anything that is in rem but not in compare_rem, and its not in the deleted array,this goes to removed-from-filter
                let deleted_kids = everything.get_changed_deleted(null);
                let in_rem_but_not_compare = rem_kids.filter(x => !compare_rem_kids.includes(x));
                if (in_rem_but_not_compare.length > 0) {
                    let in_array_but_not_deleted = in_rem_but_not_compare.filter(x => !deleted_kids.includes(x));
                    if (in_array_but_not_deleted.length > 0) {
                        let objects_to_send = [];
                        for (let i = 0; i < in_compare_but_not_rem.length; i++) {
                            objects_to_send.push(rem_hash[in_array_but_not_deleted[i]]);
                        }
                        send_to_callback('removed-from-filter', objects_to_send);
                    }
                }

            }


            {
                // for everything in both deleted and rem this goes to deleted callback
                let deleted_kids = everything.get_changed_deleted(null);
                let intersection_rem_deleted = rem_kids.filter(x => deleted_kids.includes(x));
                if (intersection_rem_deleted.length > 0) {
                    let objects_to_send = [];
                    for (let i = 0; i < intersection_rem_deleted.length; i++) {
                        objects_to_send.push(rem_hash[intersection_rem_deleted[i]]);
                    }
                    send_to_callback('deleted', objects_to_send);
                }

            }

            {
                let inserted_kids = everything.get_changed_deleted(null);
                // for everything in both inserted and in compare_rem this goes to inserted callback
                let intersection_compare_inserted = compare_rem_kids.filter(x => inserted_kids.includes(x));
                if (intersection_compare_inserted.length > 0) {
                    let objects_to_send = [];
                    for (let i = 0; i < intersection_compare_inserted.length; i++) {
                        objects_to_send.push(compare_rem_hash[intersection_compare_inserted[i]]);
                    }
                    send_to_callback('inserted', objects_to_send);
                }
            }


            //for everything in both updated and in rem this goes to updated callback
            {
                let updated_kids = everything.get_changed_updated(null);
                // for everything in both inserted and in compare_rem this goes to inserted callback
                let intersection_rem_updated = rem_kids.filter(x => updated_kids.includes(x));
                if (intersection_rem_updated.length > 0) {
                    let objects_to_send = [];
                    for (let i = 0; i < intersection_rem_updated.length; i++) {
                        objects_to_send.push(rem_hash[intersection_rem_updated[i]]);
                    }
                    send_to_callback('updated', objects_to_send);
                }
            }


            //set rem to what is in compare_rem
            this.rem = compare_rem;

        };

        /**
         * @private
         * @description takes the ruleset given in the constructor , and sees which things in the everything library matches
         *
         * @param {KabamEverything} everything
         * @param {RuleFilter} filter
         * @return {KabamRoot[]}
         */
        function find_objects_in_ruleset(everything,filter) {
            let ret = [];
            if (!everything) {return ret;}

            let library = everything.library;

            //do literals first
            for(let i =0; i < filter.literals.length; i++ ) {
                let lit = filter.literals[i];
                if (library.hasOwnProperty(lit)) {
                    ret.push(lit);
                }
            }

            //now do regular expressions

            for( let kid in library) {
                if (!library.hasOwnProperty(kid)) {
                    continue;
                } //sigh !

                let what = library[kid];

                for(let n = 0; n < filter.rules.length; n++) {
                    let rule_node = filter.rules[i];
                    let property = rule_node.property_name;

                    if (! what.hasOwnProperty(property)) {
                        continue; //important, the rules cover a lot of tests that simply do not exist in many objects
                    }

                    let property_to_test = what[property];

                    let thing = rule_node.property_value;
                    //special check for null and empty and string
                    if (thing === property_to_test) {
                        ret.push(what);
                        continue;
                    }
                    if (property_to_test === null || property_to_test === '') {continue;}

                    if (typeof thing === 'string' || thing instanceof String) {
                        //match with the string, but it already failed a direct match, so next
                        continue;
                    }

                    //if got here then thing should be an object
                    if (thing.hasOwnProperty('min') && thing.hasOwnProperty('max')) {
                        //match with min and max on numeric values
                        if (!isNaN(property_to_test)) { //if its numeric
                            let make_it_numeric = Number(property_to_test);
                            if ( thing.max != null && thing.min != null) {
                                if ((make_it_numeric <= thing.max) && (make_it_numeric >= thing.min) ) {
                                    ret.push(what);
                                }
                            } else if (thing.max == null && thing.min != null) {
                                if ( make_it_numeric >= thing.min ) {
                                    ret.push(what);
                                }
                            } else if (thing.min == null && thing.max != null) {
                                if ( make_it_numeric <= thing.max ) {
                                    ret.push(what);
                                }
                            }
                        }
                    } else if (thing instanceof RegExp) {
                        //test with regular expression
                        if (thing.test(property_to_test)) {
                            ret.push(what);
                        }
                    } else {
                        throw new Error("No clue what this rule for " + property
                            + "is, its not a min,max or a regex. Type of =  " + (typeof  thing) )
                    }
                }
            }
            return ret;
        }

        /**
         *
         * @param {string} action
         * @param {KabamRoot[]} targets
         */
        function send_to_callback(action,targets) {

            /**
             *
             * @type {HeartbeatNotification} message
             */

            //deep copy this structure so if the callee changes it then no harm
            let message = {
                action: action,
                filter:   jQuery.extend(true, {}, filter),
                targets: targets.slice(),
                notification_id : notification_id
            };

            if (callback) {
                callback(message);
            }

        }
    }


    /**
     *
     * @type {CallbackHandler[]} da_callbacks
     */
    this.da_callbacks = [];

    /**
     *
     * @type {integer|null}
     */
     this.last_server_time = null;

    /**
     *
     * @type {KabamEverything|null}
     */
    this.everything = null;


    /**
     *
     * @param {HeartbeatNotificationCallback} callback
     * @param {RuleFilter} filter
     * @return {(integer)} returns the number id
     */
    this.create_notification = function(callback,filter) {
        if (!everything) { throw new  Error("Create notification was called before the server responding with the information");}
        let next_id = this.da_callbacks.length;
        let handler = new CallbackHandler(callback,filter,next_id,everything);
        this.da_callbacks.push(handler );
        return this.da_callbacks.length -1;

     };

    /**
     * stops this notification
     * @param {integer} notification_id
     */
    this.cancel_notification = function(notification_id) {
        this.da_callbacks[notification_id] = null;
    };

    /**
     *
     * @param {KabamEverything} everything
     */
    this.send_to_notify = function( everything) {
        for(let i = 0; i < this.da_callbacks.length; i++) {
            this.da_callbacks[i].process_everything(everything);
        }
    };



    /**
     * @description this will get all the data first call
     *              on the second and onward calls, it will get only updates, insert and deletes since the last call
     */
     this.get_information = function() {

        let hugs = {api_action: 'get',pass_through_data: 'heartbeat get',begin_timestamp: this.last_server_time , end_timestamp: null};
        let that = this;
        $.GokabamTalk('gokabam_api',
            {gokabam_api_data:hugs}, //pass the params to the wordpress backend

            /**
             * This is the success function that will be called as long as the php system does not crash
             * @param {GKA_Everything} data
             */
            function(data) {

                // noinspection JSUnresolvedVariable
                gokabam_api_frontend_ajax_obj.nonce = data.server.ajax_nonce;
                if (!data.is_valid) {
                    if (error_callback) {
                        error_callback(data.exception_info);
                        return;
                    }
                }

                that.last_server_time = data.end_timestamp + 1;  //get ready for next call, do not overlap the times

                if (that.everything === null) {
                    that.everything = new KabamEverything(data);
                } else {
                    let new_everything = new KabamEverything(data);
                    //compare the difference between this and what we have
                   everything.get_changed_deleted(new_everything);
                   everything.get_changed_inserted(new_everything);
                   everything.get_changed_updated(new_everything);
                   that.send_to_notify(everything);
                   that.everything = new_everything;
                }

            },

            /**
             * This is the function that is called in case of a serious backend crash
             * @param {*} message
             */
            function(message) {
                if (error_callback) {
                    error_callback(message);
                }
            }
        )
    }
}
