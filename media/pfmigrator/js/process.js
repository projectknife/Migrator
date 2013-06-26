var PFmigrator =
{
    process: function()
    {
        console.log('Starting process...');

        // Get the form
        var f  = $('adminForm');
        var t  = $('jform_task');
        var tv = t.value;

        var total = $('jform_total', f).get('value');
        var ls    = $('jform_limitstart', f).get('value');
        var jstat = $('jform_status');
        var tint  = parseInt(total);
        var lsint = 0;

        console.log('Got all the vars');

        // Set the value of the "task" field
        t.set('value', 'process');

        // Serialize the form
        var d = f.toQueryString();

        console.log(d);

        // Do the ajax request
        var rq = new Request.JSON(
        {
            url: f.get('action'),
            data: d + '&tmpl=component&format=json',
            type: 'POST',
            cache: false,
            onSuccess: function(resp)
            {
                console.log('Request successful...');
                console.log(resp);

                // Update hidden form fields
                $('jform_limitstart').set('value', resp.limitstart);

                // Update the counter info
                $('counter_limitstart').set('text', resp.limitstart);

                // Get integer values
                var lsint = parseInt(resp.limitstart);

                // Update the progress bar
                var progress = lsint * (100/ tint);
                jQuery('progress_bar').setStyle('width', progress + '%');
                jQuery('progress_label').set('text', parseInt(progress) + '%');

                // Append the log
                if (typeof resp.proclog != 'undefined') {
                    var ullog = $('jform_log');

                    for(var i = 0; i < resp.proclog.length; i++)
                    {
                        var liel = Element('li', {text: resp.proclog[i]}).inject(ullog, 'before');
                    }
                }

                if (lsint < tint) {
                    // Set status to success
                    jstat.removeClass('label-info');
                    jstat.addClass('label-success');
                    jstat.set('text', 'Updating');

                    if ($('jform_stop').val() == '0') {
                        setTimeout("PFmigrator.process()", 1000);
                    }
                    else {
                        $('jform_prgcontainer').removeClass('active');
                        $('jform_prgcontainer').removeClass('progress-striped');
                        jstat.set('text', 'Complete');
                    }
                }
                else {
                    $('jform_prgcontainer').removeClass('active');
                    $('jform_prgcontainer').removeClass('progress-striped');
                    jstat.set('text', 'Complete');
                }
            },
            onFailure: function(resp)
            {
                console.log('Request failed...');
                console.log(resp.responseText);

                jstat.removeClass('label-success');
                jstat.removeClass('label-info');
                jstat.addClass('label-important');
                jstat.set('text', 'Error: ' + resp.responseText);

                PFmigrator.displayMsg(resp.responseText);
            },
            onException: function(headerName, value)
            {
                console.log('Request failed [exception]...');
                console.log(headerName);

                jstat.removeClass('label-success');
                jstat.removeClass('label-info');
                jstat.addClass('label-important');
                jstat.set('text', 'Error: ' + headerName + ': ' + value);

                PFmigrator.displayMsg(headerName, value);
            },
            onComplete: function()
            {
                console.log('Request complete...');

                if (lsint < tint) {
                    jstat.removeClass('label-info');
                    jstat.removeClass('label-success');

                    if (!jstat.hasClass('label-important')) {
                        jstat.removeClass('label-important');
                        jstat.set('text', 'Idle');
                    }
                }
            },
            onLoadStart: function()
            {
                console.log('Starting request...');

                if (lsint < tint) {
                    jstat.set('text', 'Processing');
                    jstat.removeClass('label-success');
                    jstat.addClass('label-info');
                }
            }
        }).send();

        /*rq.done(function(resp)
        {
            if (CscMigrateOrders.isJsonString(resp) == false) {
                CscMigrateOrders.displayException(resp);
            }
            else {
                resp = jQuery.parseJSON(resp);

                // Get integer values
                var lsint = parseInt(resp.limitstart);
                var tint  = parseInt(total);

                if (lsint < tint) {
                    if (jQuery('#jform_stop').val() == '0') {
                        setTimeout("CscMigrateOrders.process()", 1000);
                    }
                    else {
                        jQuery('#jform_prgcontainer').removeClass('active');
                        jQuery('#jform_prgcontainer').removeClass('progress-striped');
                        jstat.text('Complete');
                    }
                }
                else {
                    jQuery('#jform_prgcontainer').removeClass('active');
                    jQuery('#jform_prgcontainer').removeClass('progress-striped');
                    jstat.text('Complete');
                }
            }
        });*/
    },

    displayMsg: function(resp, err)
    {
        var mc = $('system-message-container');

        if (typeof mc == 'undefined') return false;

        if (resp.length != 0 && typeof resp.success != 'undefined') {
            if (resp.success == "true") {
                var msg_class = 'success';
            }
            else {
                var msg_class = 'error';
            }

            if (typeof resp.messages != 'undefined') {
                var l = resp.messages.length;
                var x = 0;

                if (l > 0) {
                    for (x = 0; x < l; x++)
                    {
                        mc.append('<div class="alert alert-' + msg_class + '"><a class="close" data-dismiss="alert" href="#">×</a>' + resp.messages[x] + '</div>');
                    }
                }
            }
        }
        else {
            if (typeof err != 'undefined') {
                mc.append('<div class="alert alert-error"><a class="close" data-dismiss="alert" href="#">×</a>' + err + '</div>');
            }
            else {
                mc.append('<div class="alert alert-error"><a class="close" data-dismiss="alert" href="#">×</a>Request failed!</div>');
            }
        }
    },


    displayException: function(msg)
    {
        var mc = $('system-message-container');

        if (typeof mc == 'undefined') {
            alert(msg);
        }
        else {
            mc.append(msg);
        }
    }
}