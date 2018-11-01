/*
coordinator:
    //when page is loaded then all the objects in the database is loaded into the page
        //each object has an md5 hash to keep track of updates, this hash must be returned with any update

   the coordinator
    // can return an array of all types or gets the information for a named object

    //when first initialized, does an event where any listener can get the data that they want

    after that:
    set up watch for change for specific elements or types
    will do a callback when there is a change
      //the app keeps a record of all inserts, changes or deletes and will pass it on
      // the listener simply asks for any changes past a timestamp

    does event when there is a new element added,
     listeners can filter out for types or instance they want

    sends in update for the element, the app will compare the checksum given to the page for this element to the checksum exiting now
       and will only allow update if the checksums are even, otherwise something else updated first
       //and will return the updated information
 */

(function ($) {
    // Empty object, we are going to use this as our Queue
    var ajaxQueue = $({});

    /**
     * @description  queue requests to the server to that they run only one at a time, and only after the last one completes or times out
     * @author https://stackoverflow.com/a/31149750/2420206
     * @param {Object} ajaxOpts
     * @constructor
     */
    $.GokabamAjaxQueue = function (ajaxOpts) {
        // hold the original complete function
        var oldComplete = ajaxOpts.complete;

        // queue our ajax request
        ajaxQueue.queue(function (next) {

            // create a complete callback to fire the next event in the queue
            ajaxOpts.complete = function () {
                // fire the original complete if it was there
                if (oldComplete) oldComplete.apply(this, arguments);
                next(); // run the next query in the queue
            };

            try {
                // run the query
                $.ajax(ajaxOpts);
            } catch (e) {
                console.log(e);
            }
        });
    };

})(jQuery);


(function ($) {

    /**
     * Success callback
     *
     * @callback gk_successCallback
     * @param {*} data
     */

    /**
     * Error callback
     *
     * @callback gk_errorCallback
     * @param {string} errorMessage
     */

    /**
     * @description sends an ajax message to the wordpress using the nonce to clear security, and allows callbacks for success and failure
     * @param {string} method
     * @param {object}server_options
     * @param {(gk_successCallback|null)} [success_callback=null]
     * @param {(gk_errorCallback|null)} [error_callback=null]
     * @constructor
     */
    $.GokabamTalk = function gokabam_api_talk_to_frontend(method, server_options, success_callback, error_callback) {


        if (!server_options) {
            server_options = {};
        }

        // noinspection ES6ModulesDependencies
        var outvars = jQuery.extend({}, server_options);
        // noinspection JSUnresolvedVariable
        outvars._ajax_nonce = gokabam_api_frontend_ajax_obj.nonce;
        // noinspection JSUnresolvedVariable
        outvars.action = gokabam_api_frontend_ajax_obj.action;
        outvars.method = method;
        // noinspection ES6ModulesDependencies
        // noinspection JSUnresolvedVariable
        jQuery.GokabamAjaxQueue({
            type: 'POST',
            dataType: "json",
            url: gokabam_api_frontend_ajax_obj.ajax_url,
            data: outvars,
            success: success_handler,
            error: error_handler
        });

        function success_handler(data) {
            try {
                // noinspection JSUnresolvedVariable
                if (data && data.is_valid) {
                    if (success_callback) {
                        success_callback(data.data);
                    } else {
                        console.debug(data);
                    }
                } else {
                    if (!data) {
                        data = {message: 'no response'};
                    }
                    if (error_callback) {
                        error_callback(data);
                    } else {
                        console.debug(data);
                    }

                }
            } catch (error) {
                $.GokabamErrorLogger(error);
            }
        }

        /**
         *
         * @param {XMLHttpRequest} jqXHR
         * @param {Object} jqXHR.responseJSON
         * @param {string} textStatus
         * @param {string} errorThrown
         */
        function error_handler(jqXHR, textStatus, errorThrown) {
            if (errorThrown === 'abort' || errorThrown === 'undefined') return;
            var what = '';
            var message = '';
            if (jqXHR && jqXHR.responseText) {
                try {
                    what = jQuery.parseJSON(jqXHR.responseText);
                    if (what !== null && typeof what === 'object') {
                        if (what.hasOwnProperty('message')) {
                            message = what.message;
                        } else {
                            message = jqXHR.responseText;
                        }
                    }
                } catch (err) {
                    message = jqXHR.responseText;
                }
            } else {
                message = textStatus + ' ---> ' + errorThrown;
                console.info('Ajax failed but did not return json information, check below for details', what);
                console.error(jqXHR, textStatus, errorThrown);
                $.GokabamErrorLogger(message,"warn");
            }

            if (error_callback) {
                error_callback(message);
            } else {
                $.GokabamErrorLogger(message,"warn");
            }


        }

    }


})(jQuery);


/**
 * Changes timestamps in the data to human readable dates and times for the language and locale and timezone of the browser
 */
jQuery(function ($) {
    function TimeStampToLocale() {
        $(".a-timestamp-full-date-time").each(function () {
            var qthis = $(this);
            var ts = $(this).data('ts');
            if (ts === 0 || ts === '0') {
                qthis.text('');
            } else {
                var m = moment(ts * 1000);
                qthis.text(m.format('LLLL'));
            }
        });

        $(".a-timestamp-full-date").each(function () {
            var qthis = $(this);
            var ts = $(this).data('ts');
            if (ts === 0 || ts === '0') {
                qthis.text('');
            } else {
                var m = moment(ts * 1000);
                qthis.text(m.format('LL'));
            }
        });

        $(".a-timestamp-short-date-time").each(function () {
            var qthis = $(this);
            var ts = $(this).data('ts');
            if (ts === 0 || ts === '0') {
                qthis.text('');
            } else {
                var m = moment(ts * 1000);
                qthis.text(m.format('lll'));
            }

        });

        $(".a-timestamp-short-date").each(function () {
            var qthis = $(this);
            var ts = $(this).data('ts');
            if (ts === 0 || ts === '0') {
                qthis.text('');
            } else {
                var m = moment(ts * 1000);
                qthis.text(m.format('ll'));
            }
        });
    }

    TimeStampToLocale();


    function GokabamUniqueID() {
        this.gk_rem_ids = {};

        /**
         *
         * @param {string} id
         * @return {string}
         */
        this.register = function (id) {
            let ret = id;

            let counter = 1;
            while (this.gk_rem_ids.hasOwnProperty(ret)) {
                ret = id + '-' + counter;
                counter++;
            }
            this.gk_rem_ids[ret] = Date.now();
            return ret;
        };
    }

    $.GokabamIds = new GokabamUniqueID();

    $.GokabamErrorLogger = function(error,type_error) {
        if (type_error == null) {
            type_error = 'error';
        }
        let isError = function(e){
            return e && e.stack && e.message;
        };
        let message = '';
        if (typeof error === 'string' || error instanceof String) {
            message = error;
        } else if (isError(error)) {
            message = error.message + "\n" + error.stack ;
        } else if (error == null) {
            message = '[null]';
        } else if ( typeof error === 'object' ) {
            message = JSON.stringify(error);
        } else {
            message = '' + error;
        }

        $.notify(message, { position:"right",clickToHide: true,autoHide: false, className: type_error });
        if (type_error === 'error') {
            console.error(error);
        } else {
            console.warn(error);
        }

    }

});

