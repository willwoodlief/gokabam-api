/**
 * @class
 * @param {HeartbeatErrorHandler} heartbeat_error_handler
 * @constructor
 */
function GoKabam(heartbeat_error_handler) {
    this.heartbeat = new GoKabamHeartbeat(heartbeat_error_handler);

    /**
     * @description refreshes from server
     */
    this.refresh = function() {
        this.heartbeat.get_information();
    };


    /**
     * @description wrapper for creating a notification, will add stuff here to help coordinate
     * @param {HeartbeatNotificationCallback} callback
     * @param {RuleFilter} filter
     * @return {(integer)} returns the number id
     */
    this.create_notification = function(callback,filter) {
        return this.heartbeat.create_notification(callback,filter);
    };

    /**
     * stops this notification
     * @param {integer} notification_id
     * @return {void}
     */
    this.cancel_notification = function(notification_id) {
        this.heartbeat.cancel_notification(notification_id);
    }
}

jQuery(function($){

    /**
     * @param {GKA_Exception_Info|string} exception_info - Exception information
     */
    function error_handler(exception_info) {
        console.log(exception_info);
    }

    if (!$.GoKabam) {
        $.GoKabam = new GoKabam(error_handler);
    }

    //create test handler

    /**
     * @param {HeartbeatNotification} event
     */
    function handler(event) {
        console.log(event);
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

    $.GoKabam.refresh();

    let nid = $.GoKabam.create_notification(handler,filter);

    $('button.gk-test1').click(function() {
        $.GoKabam.refresh();
    });

    $('button.tgk-test2').click(function() {
        $.GoKabam.cancel_notification(nid);
    });
});