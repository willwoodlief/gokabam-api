//main version page java script



jQuery(function($) {
    $('.gokabam-make-new-family').click(function() {

        var msg = $('.gokabam-new-family-form');
        BootstrapDialog.show({
            message: msg,
            data: {

            },
            onhide : function(){
                $('#family-api-forms').append(msg);
            },
            buttons: [{
                label: 'Create This Family',
                cssClass: 'btn-primary',
                action: function(/*dialogRef*/){
                    $(".gokabam-new-family-form form").submit();
                }
            }]
        });
    })

});