/**
 * Typedef for get notification
 *
 * @callback GoKabamGetCallback
 * @param {GoKabam} go_kabam - reference to self
 * @Return {void} nothing is expected to be returned once this is called
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

    this.heartbeat.create_notification(get_handler,null);

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
     * @param {RuleFilter} filter
     * @return {(integer)} returns the number id
     */
    this.create_notification = function(callback,filter) {
        return this.heartbeat.create_notification(callback,filter);
    };

    /**
     * @public
     * stops this notification
     * @param {integer} notification_id
     * @return {void}
     */
    this.cancel_notification = function(notification_id) {
        this.heartbeat.cancel_notification(notification_id);
    };

    this.update = function(root_array) {
        this.heartbeat.push_update(root_array);
    }



}

var test_version = null;

jQuery(function($){


    /**
     * @param {GKA_Exception_Info|string} exception_info - Exception information
     */
    function error_handler(exception_info) {
        console.log(exception_info);
    }

    let nid = null;


    /**
     *
     * @param {GoKabam} go_kabam
     */
    function on_get(go_kabam) {
        if (nid == null) {
            //create test handler

            /**
             * @param {HeartbeatNotification} event
             */
            function handler(event) {
                console.log(event);
                test_version = event.targets[0]; //grab the literal
            }

            var re = /^version_\w+$/;
            /**
             * @type {RuleFilter}
             */
            let filter = {
                rules:[
                    {
                        property_name: 'kid',
                        property_value: re
                    }
                ],
                literals: [
                    'version_YD53eP'
                ]
            };

            nid = $.GoKabam.create_notification(handler,filter);
        }
    }

    if (!$.GoKabam) {
        $.GoKabam = new GoKabam(error_handler,[on_get]);
    }



    $.GoKabam.refresh();



    $('button.gk-test1').click(function() {
        $.GoKabam.refresh();
    });

    $('button.gk-test2').click(function() {
        $.GoKabam.update([test_version]);
    });
});