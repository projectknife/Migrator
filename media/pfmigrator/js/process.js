var PFmigrator =
{
    process: function(opt)
    {
        // Get the form
        var f  = $('adminForm');
        var t  = $('jform_task');
        var tv = t.value;

        var total = $('jform_total').get('value');
        var ls    = $('jform_limitstart').get('value');
        var jstat = $('jform_status');
        var tint  = parseInt(total);
        var lsint = 0;

        // Set the value of the "task" field
        t.set('value', 'process');

        // Serialize the form
        var d = f.toQueryString();

        jstat.set('text', opt.txt_proc);

        // Do the ajax request
        var rq = new Request.JSON(
        {
            url: f.get('action'),
            data: d + '&tmpl=component&format=json',
            type: 'POST',
            cache: false,
            onSuccess: function(resp)
            {
                console.log('success');
                console.log(resp);

                // Update hidden form fields
                $('jform_limitstart').set('value', resp.limitstart);
                $('jform_total').set('value', resp.total);
                $('jform_limit').set('value', resp.limit);

                // Update the counter info
                $('counter_limitstart').set('text', resp.limitstart);
                $('counter_total').set('text', resp.total);

                // Get integer values
                total = $('jform_total').get('value');
                ls    = $('jform_limitstart').get('value');
                tint  = parseInt(total);

                var lsint = parseInt(resp.limitstart);

                // Update the progress bar
                var progress = lsint * (100/tint);
                $('progress_bar').setStyle('width', progress + '%');
                $('progress_label').set('text', parseInt(progress) + '%');

                // Append the log
                if (typeof resp.proclog != 'undefined') {
                    var ullog = $('jform_log');

                    for(var i = 0; i < resp.proclog.length; i++)
                    {
                        var liel = Element('li', {text: resp.proclog[i]}).inject(ullog, 'top');
                    }
                }

                if (resp.success == false) {
                    $('jform_prgcontainer').removeClass('active');
                    $('jform_prgcontainer').removeClass('progress-striped');
                    jstat.set('text', opt.txt_err);
                    return false;
                }

                if (lsint < tint) {
                    // Set status to success
                    jstat.removeClass('label-info');
                    jstat.addClass('label-success');
                    jstat.set('text', opt.txt_upd);

                    setTimeout("PFmigrator.process(" + JSON.encode(opt) + ")", 1000);
                }
                else {
                    $('jform_prgcontainer').removeClass('active');
                    $('jform_prgcontainer').removeClass('progress-striped');
                    jstat.set('text', opt.txt_cpl);

                    var cproc = parseInt($('jform_process').value);
                    var procs = parseInt($('jform_processes').value);

                    // Go to next process
                    if (procs - 1 > cproc) {
                        $('jform_process').set('value', cproc + 1);
                        $('jform_total').set('value', 0);
                        $('jform_limitstart').set('value', 0);

                        $('progress_bar').setStyle('width', '0%');
                        $('progress_label').set('text', '0%');

                        $('proc_' + cproc).removeClass('proc-active');
                        $('proc_' + cproc).addClass('proc-done');
                        $('proc_' + (cproc + 1)).addClass('proc-active');

                        setTimeout("PFmigrator.process(" + JSON.encode(opt) + ")", 1000);
                    }
                    else {
                        // Migration complete
                        $('jform_progress').hide();
                        $('jform_counter').hide();
                        $('jform_progress_done').show();
                    }
                }
            },
            onError: function(text, error)
            {
                $('jform_progress').hide();
                $('jform_counter').hide();

                $('jform_exception_rsp').set('html', text);
                $('jform_exception_rsp_err').set('text', error);
                $('jform_exception').show();
            },
            onComplete: function()
            {
                if (lsint < tint) {
                    jstat.removeClass('label-info');
                    jstat.removeClass('label-success');

                    if (!jstat.hasClass('label-important')) {
                        jstat.removeClass('label-important');
                        jstat.set('text', opt.txt_idle);
                    }
                }
            }
        }).send();
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
                        var liel = Element('div', {text: resp.messages[x]}).inject(mc, 'top');
                    }
                }
            }
        }
        else {
            if (typeof err != 'undefined') {
                var liel = Element('div', {text: err}).inject(mc, 'top');
            }
            else {
                var liel = Element('div', {text: 'Request failed!'}).inject(mc, 'top');
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
            var liel = Element('div', {text: msg}).inject(mc, 'top');
        }
    }
}