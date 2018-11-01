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
 * @typedef {object} KabamRuleFilter
 * @property {HeartbeatRule[]} rules
 * @property {GKA_Kid[]} literals
 */

/**
 * Typedef for the notification data
 * each event for the rule is grouped together, so if there was an insert, a delete , and update then three notifications
 * @typedef {object} HeartbeatNotification
 * @property {string} action   : inserted|updated|deleted|added-to-filter|removed-from-filter|init|get
                            init is called once , right after the filter is made, and returns the initial matching objects
                            get is used when a null filter is passed in, and will simply notify the refresh has taken place
                            only null filters receive get notifications, but null filters only receive that one notification
 * @property {KabamRuleFilter} filter  - the filter which can be changed
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
     * @param {object|null} callback_object
     * @param {KabamRuleFilter} filter

     * @param {number} notification_id
     * @param {KabamEverything} everything
     */
    function CallbackHandler(callback,callback_object,filter,notification_id,everything) {

        /**
         * @type {KabamRoot[]} rem
         */
        this.rem = [];


        /**
         * kicks off the action, there may be multiple nested calls to different handlers created from here
         * so cannot be part of the constructor
         */
        this.start_handler = function() {
            if (everything && filter) {
                this.rem = find_objects_in_ruleset(everything,filter);
                send_to_callback('init',this.rem); //notice that init always called, even when nothing is found
            }
        };

        /**
         @public
         @description Called to update the callback with a new everything, or simply to redo the ruleset with the same
                        everything this class was initialized with

         * @param {KabamEverything} everything
         * @param {KabamEverything} new_everything , the updated refresh
         * @return {void}
         */
        this.process_everything = function(everything,new_everything) {

            if (filter == null) {
                if (everything.api_action === 'get') {
                    send_to_callback('get',[]);
                }
                 // only send out during a refresh
                return;
            }

            //get the current set of objects that match this rule (filter from the constructor params)
            let what_dataset = everything;
            if (new_everything) {
                what_dataset = new_everything;
            }
            let compare_rem = find_objects_in_ruleset(what_dataset,filter);

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


            {
                // for anything that is in compare_rem that is not in rem, and its not in the inserted array,
                // this goes to  added-to-filter
                let in_compare_but_not_rem = compare_rem_kids.filter(x => !rem_kids.includes(x));
                let inserted_kids = everything.get_changed_inserted(null);
                let in_array_but_not_inserted = in_compare_but_not_rem.filter(x => !inserted_kids.includes(x));
                if (in_array_but_not_inserted.length > 0) {

                    let objects_to_send = [];
                    for (let i = 0; i < in_array_but_not_inserted.length; i++) {
                        objects_to_send.push(compare_rem_hash[in_compare_but_not_rem[i]]);
                    }
                    send_to_callback('added-to-filter', objects_to_send);
                }
            }

            {
                // for anything that is in rem but not in compare_rem, and its not in the deleted array,this goes to removed-from-filter
                let deleted_kids = everything.get_changed_deleted(null);
                let in_rem_but_not_compare = rem_kids.filter(x => !compare_rem_kids.includes(x));
                if (in_rem_but_not_compare.length > 0) {
                    let in_array_but_not_deleted = in_rem_but_not_compare.filter(x => !deleted_kids.includes(x));
                    if (in_array_but_not_deleted.length > 0) {
                        let objects_to_send = [];
                        for (let i = 0; i < in_array_but_not_deleted.length; i++) {
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
                let inserted_kids = everything.get_changed_inserted(null);
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
                        let updated_object = new_everything.library[intersection_rem_updated[i]];
                        objects_to_send.push(updated_object);
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
         *  returns empty array if filter is null
         * @param {KabamEverything} everything
         * @param {KabamRuleFilter} filter
         * @return {KabamRoot[]}
         */
        function find_objects_in_ruleset(everything,filter) {
            let ret_hash = {};
            if (!everything) {return [];}
            if (!filter) {return [];}

            let library = everything.library;

            //do literals first
            for(let i =0; i < filter.literals.length; i++ ) {
                let lit = filter.literals[i];
                if (library.hasOwnProperty(lit)) {
                    ret_hash[lit] =  library[lit];
                }
            }

            //now do rules
            // all the rules have to fit each thing, in order for it to pass

            for( let kid in library) {
                if (!library.hasOwnProperty(kid)) {
                    continue;
                } //sigh !

                let what = library[kid];
                let count_matches = 0;

                for(let n = 0; n < filter.rules.length; n++) {
                    let rule_node = filter.rules[n];
                    let property = rule_node.property_name;

                    if (! what.hasOwnProperty(property)) {
                        continue; //important, the rules cover a lot of tests that simply do not exist in many objects
                    }

                    let property_to_test = what[property];

                    let thing = rule_node.property_value;
                    //special check for null and empty and string
                    if (thing === property_to_test) {
                        count_matches ++;
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
                                    count_matches ++;
                                }
                            } else if (thing.max == null && thing.min != null) {
                                if ( make_it_numeric >= thing.min ) {
                                    count_matches++;
                                }
                            } else if (thing.min == null && thing.max != null) {
                                if ( make_it_numeric <= thing.max ) {
                                    count_matches ++;
                                }
                            }
                        }
                    } else if (thing instanceof RegExp) {
                        //test with regular expression
                        if (thing.test(property_to_test)) {
                            count_matches ++;
                        }
                    } else {
                        throw new Error("No clue what this rule for " + property
                            + "is, its not a min,max or a regex. Type of =  " + (typeof  thing) )
                    }
                }
                //test to see if the match count meets the rule count, if so put it in ret_hash
                if ((count_matches > 0 ) && (count_matches === filter.rules.length)) {
                    ret_hash[what.kid] = what;
                }
            }

            let ret = [];
            for(let i in ret_hash) {
                if (ret_hash.hasOwnProperty(i)) {
                    ret.push(ret_hash[i]);
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
                if (callback_object) {
                    //call with callback object
                    callback.call(callback_object,message);
                } else
                {
                    callback(message);
                }

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
     * @type {KabamEverything|null}
     */
    this.everything = null;

    let that = this;


    /**
     * @public
     * if filter is null the callback will be called without any objects and action refresh
     * @param {HeartbeatNotificationCallback} callback
     * @param {object|null} callback_object
     * @param {KabamRuleFilter|null} filter
     * @return {(integer)} returns the number id
     */
    this.create_notification = function(callback,callback_object,filter) {
        let next_id = this.da_callbacks.length;
        let handler = new CallbackHandler(callback,callback_object,filter,next_id,this.everything);
        this.da_callbacks.push(handler );
        handler.start_handler();
        return this.da_callbacks.length -1;

     };

    /**
     * @public
     * stops this notification
     * @param {number} notification_id
     */
    this.cancel_notification = function(notification_id) {
        this.da_callbacks[notification_id] = null;
    };

    /**
     *
     * @param {KabamEverything} everything , the old refresh
     * @param {KabamEverything} new_everything , the updated refresh
     */
    this.send_to_notify = function( everything,new_everything) {

        //create copy of the callbacks array, in case extra things get added in the middle of all this
        let callbacks = this.da_callbacks.slice();
        for(let i = 0; i < callbacks.length; i++) {
            if (callbacks[i] == null) { continue;}
            callbacks[i].process_everything(everything,new_everything);
        }
    };



    /**
     * @public
     * @description this will get all the data first call
     *              on the second and onward calls, it will get only updates, insert and deletes since the last call
     */
     this.get_information = function() {
        try {
            //always get full updates
            let hugs = {api_action: 'get',pass_through_data: 'heartbeat get',begin_timestamp: null , end_timestamp: null};
            data_push_and_recieve(hugs);
        } catch(error) {
            jQuery.GokabamErrorLogger(error,'warn');
        }

    };

    /**
     * @public
     * @description will update the server and then run the filter callbacks with the new information
     *  if any one of these fail then nothing gets updated,
     *  if the md5 is obsolete for any of them, then all will be rejected, but the notice will come through the error callback
     *  To delete an object, set delete to 1 . They can be recovered later, but not through this js library
     * @param {KabamRoot[]}  root_array , 1 or more objects  derived from root, must use the actual classes
     * @return {void}  the updated info and side effects will be sent through the filter callbacks already registered
     */
    this.push_update = function(root_array) {

        try {
            let every_update = new KabamEverything(null);
            for (let i = 0; i < root_array.length; i++) {
                let root = root_array[i];
                let added_copy = every_update.add_root(root, true); //will throw error if root not correct class
                added_copy.clean();  //clear out dependencies
            }
            every_update.api_action = 'update';
            data_push_and_recieve(every_update);
        } catch(error) {
            jQuery.GokabamErrorLogger(error,'warn');
        }
     };

     function data_push_and_recieve(data) {
         jQuery.GokabamTalk('gokabam_api',
             {gokabam_api_data:data}, //pass the params to the wordpress backend

             /**
              * This is the success function that will be called as long as the php system does not crash
              * @param {GKA_Everything} data
              */
             function(data) {

                 // noinspection JSUnresolvedVariable
                 gokabam_api_frontend_ajax_obj.nonce = data.server.ajax_nonce;
                 if (!data.is_valid) {
                     jQuery.GokabamErrorLogger(data.exception_info.message,"warn");
                     if (error_callback) {
                         error_callback(data.exception_info);
                         return;
                     }
                 }



                 if (that.everything === null) {
                        that.everything = new KabamEverything(data);
                        that.send_to_notify(that.everything,null);

                 } else {

                     let new_everything = null;
                     if (data.api_action === 'update') {
                        //the new everything is what we already have plus the new changes

                         //make a copy of our old
                         new_everything = new KabamEverything(that.everything);
                         let updated_everything = new KabamEverything(data);

                         {
                             //for any new deleted things, add the deleted kids to our deleted_kids, and remove the ids from our library
                             let new_deleted_kids_array = new_everything.get_changed_deleted(updated_everything);
                             for (let i = 0; i < new_deleted_kids_array.length; i++) {
                                 let del_kid = new_deleted_kids_array[i];
                                 if (new_everything.library.hasOwnProperty(del_kid)) {
                                      new_everything.remove_kid(del_kid);
                                 }
                             }
                             new_everything.deleted_kids = new_everything.deleted_kids.concat(new_deleted_kids_array);
                         }

                         {
                             //for any new inserted things, add them to the library and to the appropriate property array
                             let new_inserted_kids_array = new_everything.get_changed_inserted(updated_everything);
                             for (let i = 0; i < new_inserted_kids_array.length; i++) {
                                 let ins_kid = new_inserted_kids_array[i];
                                 if (updated_everything.library.hasOwnProperty(ins_kid)) {
                                     let libtard =  updated_everything.library[ins_kid];
                                     new_everything.add_root(libtard,false);
                                 }
                             }
                         }

                         {
                             //for any updated things, just copy over the library entry
                             let new_updated_kids_array = new_everything.get_changed_updated(updated_everything);
                             for (let i = 0; i < new_updated_kids_array.length; i++) {
                                 let up_kid = new_updated_kids_array[i];
                                 if (updated_everything.library.hasOwnProperty(up_kid)
                                     &&
                                     new_everything.library.hasOwnProperty(up_kid)
                                 ) {
                                     new_everything.library[up_kid] =  updated_everything.library[up_kid];
                                 }
                             }
                         }

                     } else if (data.api_action === 'get') {
                         new_everything = new KabamEverything(data);
                     }


                     //compare the difference between this and what we have

                     //set up the change caches
                     that.everything.get_changed_deleted(new_everything);
                     that.everything.get_changed_inserted(new_everything);
                     that.everything.get_changed_updated(new_everything);
                     let rem_everything = that.everything;
                     that.everything = new_everything; //update everything before new handlers are made with the inserted stuff
                     that.send_to_notify(rem_everything,new_everything);



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
