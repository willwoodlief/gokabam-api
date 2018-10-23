// api test page js

let rightEditor=null,leftEditor = null,lastCall=null,nowCall=null;
function cycleEditors(obj) {
    lastCall = nowCall;
    nowCall = JSON.stringify(obj, null, '\t');
    leftEditor.setValue(nowCall);
    leftEditor.clearSelection();
    if (!lastCall) {
        lastCall = '';
    }
    rightEditor.setValue(lastCall);
    rightEditor.clearSelection();
}

jQuery(function($) {
    // noinspection ES6ModulesDependencies
    var aceDiffer = new AceDiff({
        mode: 'ace/mode/json',
        element: '.gk-main',
        diffGranularity: 'specific',
        left: {
            content: '',
        },
        right: {
            content: '',
        },
    });




    // aceDiffer.editors.right.ace.setTheme('ace/theme/katzenmilch');
    aceDiffer.editors.left.ace.getSession().setUseWrapMode(true);
    aceDiffer.editors.right.ace.getSession().setUseWrapMode(true);

    rightEditor = aceDiffer.editors.right.ace;
    leftEditor = aceDiffer.editors.left.ace;

    let status_span = $('span.gk-status');

    $('.gk-talker').click(function() {
        status_span.text('').removeClass('gk-status-error');
        let hugs = {};
        let words = leftEditor.getValue();
        words = words.trim();
        if (!words) {
            hugs = {api_action: 'init',pass_through_data: 'no input, so asking initizing'};
        } else {
            try {
                hugs = JSON.parse(words);
            } catch (e) {
                status_span.text(e.message).addClass('gk-status-error');
                return;
            }
        }



        if ($.isEmptyObject(hugs)) {
            hugs = {api_action: 'init',pass_through_data: 'empty object after parsing, so asking initizing'};
        }
        $('.gk-spinner').show();

        $.GokabamTalk('gokabam_api',
            {gokabam_api_data:hugs},
            function(data) {
                $('.gk-spinner').hide();


                cycleEditors(data);
                // noinspection JSUnresolvedVariable
                gokabam_api_frontend_ajax_obj.nonce = data.server.ajax_nonce;
                if (data.exception_info) {
                    $('span.gk-status').text(data.message).addClass('gk-status-error');
                } else {
                    $('span.gk-status').text(data.message).removeClass('gk-status-error');
                }
            },
            function(message) {
                $('.gk-spinner').hide();
                if ((typeof message === "string") && (message.length > 0)) {
                    $('span.gk-status').text(message).addClass('gk-status-error');
                } else {
                    $('span.gk-status').text('ERROR').addClass('gk-status-error');
                }

                cycleEditors(message);
            }
        )
    });

});


