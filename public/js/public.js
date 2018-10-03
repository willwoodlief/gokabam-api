function gokabam_api_talk_to_frontend(method, server_options, success_callback, error_callback) {

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
    var gokabam_api_ajax_req = jQuery.ajax({
        type: 'POST',
        beforeSend: function () {
            if (gokabam_api_ajax_req && (gokabam_api_ajax_req !== 'ToCancelPrevReq') && (gokabam_api_ajax_req.readyState < 4)) {
                //    gokabam_api_ajax_req.abort();
            }
        },
        dataType: "json",
        url: gokabam_api_frontend_ajax_obj.ajax_url,
        data: outvars,
        success: success_handler,
        error: error_handler
    });

    function success_handler(data) {

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
            message = "textStatus";
            console.info('Admin Ecomhub ajax failed but did not return json information, check below for details', what);
            console.error(jqXHR, textStatus, errorThrown);
        }

        if (error_callback) {
            error_callback(message);
        } else {
            console.warn(message);
        }


    }
}



jQuery(function($) {
    function TimeStampToLocale() {
        $(".a-timestamp-full-date-time").each(function() {
            var qthis = $(this);
            var ts = $(this).data('ts');
            if (ts === 0 || ts === '0') {
                qthis.text('' );
            } else {
                var m = moment(ts * 1000);
                qthis.text(m.format('LLLL'));
            }
        });

        $(".a-timestamp-full-date").each(function() {
            var qthis = $(this);
            var ts = $(this).data('ts');
            if (ts === 0 || ts === '0') {
                qthis.text('' );
            } else {
                var m = moment(ts * 1000);
                qthis.text(m.format('LL'));
            }
        });

        $(".a-timestamp-short-date-time").each(function() {
            var qthis = $(this);
            var ts = $(this).data('ts');
            if (ts === 0 || ts === '0') {
                qthis.text('' );
            } else {
                var m = moment(ts*1000);
                qthis.text(m.format('lll') );
            }

        });

        $(".a-timestamp-short-date").each(function() {
            var qthis = $(this);
            var ts = $(this).data('ts');
            if (ts === 0 || ts === '0') {
                qthis.text('' );
            } else {
                var m = moment(ts * 1000);
                qthis.text(m.format('ll'));
            }
        });
    }

    TimeStampToLocale();

});