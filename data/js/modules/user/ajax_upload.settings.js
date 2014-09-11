$(document).ready(function()
{
    var options = { 
        beforeSend: function() 
        {
        	$("#loader").show();
        	$("#loader > div").width('0%');
        },
        uploadProgress: function(event, position, total, percentComplete) 
        {
        	$("#loader > div").width(percentComplete+'%');
        },
        success: function() 
        {
            $("#loader > div").width('100%').fadeOut();
        },
        complete: function(response) 
        {
            console.log(response.responseText);
            var res = $.parseJSON(response.responseText);
            if(res.success == true) {
                var d = new Date();
                $('#user-info .message').addClass('hidden');
                if(res.typ == 'avatar') {
                    $('#profile_picture > .picture').css('background-image', 'url(' + res.path + '?' + d.getTime() + ')');
                    $('.avatar').css('background-image', 'url(' + res.path + '?' + d.getTime() + ')');
                    $('#change_profile_picture input[type=file]').val("");
                } else {
                    $('#main-eyecatcher').css('background-image', 'url(' + res.path + '?' + d.getTime() + ')');
                    $('#change_background_picture input[type=file]').val("");
                }
            } else {
                console.log('test');
                $('#user-info .message').removeClass('hidden').children('.container').text(res.error);
            }
        },
        error: function()
        {
        	$("#message").html("<font color='red'> ERROR: unable to upload files</font>");
        }
    };

    $(".ajax-upload").each(function () {
        $(this).attr('action', $(this).attr('action') + '/ajax').ajaxForm(options);
    });

});