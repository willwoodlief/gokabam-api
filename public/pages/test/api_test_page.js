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

function gk_root_start() {
    let test_holder = $('.test-container');


    ////////////////////////////////////////
    ////////// Words ///////////////////
    /////////////////////////////////////

    let container_class = jQuery.GoKabam.get_container('wide','KabamWord');
    //make test container with word
    // version_YD53eP
    let type_regex = /^word_\w+$/;
    /**
     * @type {KabamRuleFilter}
     */
    let word_filter = {
        rules:[
            {
                property_name: 'kid',
                property_value: type_regex
            },
            {
                property_name: 'parent',
                property_value: 'version_YD53eP'
            }
        ],
        literals: [
        ]
    };

    let container = new container_class(jQuery.GoKabam,['gk-test-test'], word_filter);
    test_holder.append('<h2>Word Test Wide Container</h2>');
    test_holder.append(container.div);

    ////////////////////////////tags///////////////////////////////

    let tag_type_regex = /^tag_\w+$/;

    let tag_filter = {
        rules:[
            {
                property_name: 'kid',
                property_value: tag_type_regex
            },
            {
                property_name: 'parent',
                property_value: 'version_YD53eP'
            }
        ],
        literals: [
        ]
    };

    let tag_container_class = jQuery.GoKabam.get_container('wide','KabamTag');
    let tag_container = new tag_container_class(jQuery.GoKabam,['gk-tag-test'], tag_filter);
    test_holder.append('<h2>Tag Test Wide Container</h2>');
    test_holder.append(tag_container.div);



    //////////////////////// Journals ///////////////////////////////
    {
        let journal_type_regex = /^journal_\w+$/;

        let journal_filter = {
            rules: [
                {
                    property_name: 'kid',
                    property_value: journal_type_regex
                },
                {
                    property_name: 'parent',
                    property_value: 'version_YD53eP'
                }
            ],
            literals: []
        };


        let journal_container_class = jQuery.GoKabam.get_container('wide', 'KabamJournal');
        let journal_container = new journal_container_class(jQuery.GoKabam, ['gk-tag-test'], journal_filter);
        test_holder.append('<h2>Journal Test Wide Container</h2>');
        test_holder.append(journal_container.div);

    }
    /// TEST VERSION ////
    {
        let version_type_regex = /^version_\w+$/;

        let version_filter = {
            rules: [
                {
                    property_name: 'kid',
                    property_value: version_type_regex
                }
            ],
            literals: []
        };


        let version_container_class = jQuery.GoKabam.get_container('wide', 'KabamVersion');
        let version_container = new version_container_class(jQuery.GoKabam, ['gk-tag-test'], version_filter);
        test_holder.append('<h2>Version Container</h2>');
        test_holder.append(version_container.div);

    }

    $('button.gk-test1').click(function() {
        jQuery.GoKabam.refresh();
    });

    $('button.gk-test2').click(function() {
        alert('I do nothing');
    });


}

//todo get this json view to fit inside parent without going bigger
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


