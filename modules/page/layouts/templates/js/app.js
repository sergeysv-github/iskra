function display_message(message, level) 
{
    var $container = $('#message-container');

    if ($container.length) {
        $container.html('');
    } else {
        $container = $('<div id="message-container"></div>').prependTo('div.side-menu');
    }

    if (message === '') {
        return;
    }

    if (level === 'error') {
        $container.html('<div class="alert alert-danger">'+ message +'</div>');
    } else if (level === 'success') {
        $container.html('<div class="alert alert-success alert-fadeout">'+ message +'</div>');
    }
    window.scrollTo(0, 0);

    setTimeout(function(){
        $('.alert-fadeout').fadeOut(1000);
    }, 2000);
}

function display_error_message(message) 
{
    display_message(message, 'error');
}

function display_success_message(message) 
{
    display_message(message, 'success');
}

function display_form_errors(form, errors) 
{
    // Might be a single error message here. In this case, display it
    // in a general message container.
    if (typeof errors === 'string') {
        display_error_message(errors);
        return;
    }
    // Properties of "errors" would be the form input names, and values
    // would be their respective error messages.
    for (var key in errors) 
    {
        // Anonymous errors must go to the general message container.
        if (key === '0' || parseInt(key) > 0) {
            display_error_message(errors[key]);
            continue;
        }
        var idname = 'id_' + key;

        // For named errors, display each error under the corresponding
        // form input.
        // FIXME filtering by form doesn't work!!! e.g. $('input..', form)
        var $input = $('*[id="'+ idname +'"]');
        if (!$input.length) {
            $input = $('div[id="'+ idname +'"]');
            if (!$input.length) {
                continue;
            }
        }

        $input.attr('aria-describedby', idname+'_status');

        // Add the error message.
        var $container = $input;
        if ($input.parent().is("div.input-group")) {
            $container = $input.parent();
            $input.closest('div.input-group').addClass('has-feedback has-error');
        } else {
            $input.closest('div.form-group').addClass('has-feedback has-error');
        }
        $('<span id="'+ idname +'_status" class="text-danger small">'+ errors[key] +'</span>').insertAfter($container);
        
        // If the form is "tabbed", show the tab where the input is located.
        var tab_id = $input.parents('.form-tab').attr('id');
        if (typeof tab_id !== 'undefined') {
            $(document).find('a[href="#'+ tab_id +'"]').click();
        }
    }
}

/**
 * Initializes dynamic AJAX selector.
 * 
 * @param {string} dom_selector DOM selector
 * @param {string} post_var_name Name of the POST variable containing the search
 * value, typed in the input.
 */
function init_ajax_select(dom_selector, post_var_name)
{
    if (!jQuery().selectize) {
        return;
    }
    
    $(dom_selector).addClass('dropdown-no-caret');
    $(dom_selector).attr('placeholder', 'Type to search by name');
    $(dom_selector).selectize({
        valueField: 'id',
        labelField: 'fullname',
        searchField: 'fullname',
        create: false,
        maxOptions: 20,
        load: function(query, callback) {
            if (!query.length || query === '') {
                return callback();
            }
            $.ajax({
                type: 'POST',
                data: post_var_name + '=' + encodeURIComponent(query),
                dataType: 'json',
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res === false) {
                        callback();
                    } else {
                        callback(res);
                    }
                }
            });
        }
    });
}

function init_ajax_table_loader()
{
    var timer;
    $(window).scroll(function() 
    {
        // If the load indicator isn't there, no need to trigger AJAX.
        var $preloader = $("#page-bottom-preloader");
        if (!$preloader.length) {
            return;
        }
        
        // Add more content to the preloader.
        $preloader.hide();
        $preloader.html('<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span> Loading more...');
        
        // Trigger AJAX on window scroll.
        if ($(window).scrollTop() === $(document).height() - $(window).height()) {
            $preloader.removeClass('hidden');
            var $table = $preloader.siblings('table.all-list').eq(0);
            var rows_count = $table.find('tbody tr').length;
            // We need a bit of delay here so as not to load the same results twice.
            if ( timer ) clearTimeout(timer);
            timer = setTimeout(function(){
                $preloader.show();
                $.ajax({
                    type: 'POST',
                    data: 'load_more_table[loaded_count]='+rows_count,
                    dataType: 'json',
                    success: function(response) {
                        if (response === false) {
                            // We don't have anything to display. Get rid of the 
                            // preloader so as not to trigger this function again.
                            $preloader.remove();
                            return;
                        }
                        $(response.html).appendTo($table);
                        $preloader.addClass('hidden');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $preloader.addClass('hidden');
                    }
                });
            }, 500);
        }
    });
}

$(function(){
	
    $('form.dynamic-validation').each(function()
    {
        // Add IDs to all inputs, based on their names.
        // E.g., <select name="cities"> will have a "id_cities" id.
        // <input name="object[value]"> will have a "id_value" id.
        $(this).find(':input').each(function()
        {
            // Find a name first.
            var $input = $(this);
            var name = $input.attr('name');
            if (typeof name === 'undefined' || name === '') {
                return;
            }

            // If the input is hidden, skip it. Unless...
            if ($input.is(':hidden')) {
                if ($input.hasClass('wysiwyg-editor')) {
                    // ...unless it's a WYSIWYG textarea.
                    $input = $('div[id="'+ $input.attr('data-relates-to') +'"]');
                } else if ($input.hasClass('select-dynamic')) {
                    // ...or a dynamic select
                    $input = $(this).siblings('.selectize-control');
                } else {
                    return;
                }
            }
            var matches = name.match(/\[(.*?)\]/);
            if (matches) {
                name = matches[1];
            }
            var id = 'id_'+ name;
            $input.attr('id', id);
        });
    });
    
    // Add some dynamics to the forms, wherever needed.
    $('form.dynamic-validation').submit(function(e) 
    {
        e.preventDefault();

        // Prepare the form for dynamic feedback messages.
        $(this).addClass('has-feedback');

        // Remember the submit button value and disable it temporarily.
        $submit = $(this).find('button[type="submit"]');
        var submit_text = $submit.html();
        $submit.attr('disabled', true).html('Sending data...');

        // Clear all inputs and glyphs.
        $(this).find('span.form-control-feedback').remove();
        $(this).find('span.text-danger').remove();
        $(this).find('div.form-group').removeClass('has-feedback').removeClass('has-error');

        // Clear the message container.
        display_message('');

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (typeof response.redirect !== 'undefined') {
                    location.href = response.redirect;
                    return;
                }
                console.log(response);
                if (!response.success) {
                    display_form_errors(this, response.data);
                } else {
                    display_success_message(response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR.responseText);
                display_error_message(textStatus + ': ' + errorThrown);
            }
        });

        // Return windows back to normal.
        $submit.removeAttr('disabled');
        $submit.html(submit_text);
    });

    // Prepare and clean up the search forms.
    $('form.search-form').each(function(){
        if ($(this).hasClass('navbar-form')) {
                return;
        }
        // Add hidden inputs for application-reserved variables.
        var q = '<input type="hidden" name="q" value="'+ app_request.query +'">';
        $(q).prependTo(this);
        $(this).submit(function()
        {
            // Remove empty inputs.
            $(this).find(':input').each(function(){
                if ($(this).is(':button') || $(this).is(':submit')) {
                    return;
                }
                if ($(this).val() === '') {
                    $(this).remove();
                }
            });
            return true;
        });
    });

    // Initialize all datepickers
    if (jQuery().datetimepicker) {
        $('.datepicker').datetimepicker({
              format: "DD/MM/YYYY"
        });
        $('.datetimepicker').datetimepicker({
            
        });
    }

    // Initialize all dynamic selects
    if (jQuery().selectize) {
        $('.select-dynamic').selectize({
            maxOptions: 15,
            allowEmptyOption: true
        });
    }

    // Make button labels work as triggers for radiobuttons.
    $('label.btn').click(function(){
        $(this).closest('.btn-group').find('input[type="radio"]').each(function(){
            $(this).attr('checked', !$(this).attr('checked'));
        });
    });

    // Make button labels work as triggers for checkboxes.
    $('label.btn').click(function(){
        $(this).find('input[type="checkbox"]').each(function(){
            $(this).attr('checked', !$(this).attr('checked'));
        });
    });
    
    // Process "Save" and "Save and continue" buttons.
    $('#btn-save, #btn-save-and-continue').click(function(){
        var $form = $(this).closest('form');
        $('#hidden-form-mode').remove();
        var stay = ($(this).attr('id') === 'btn-save') ? 0 : 1;
        $('<input id="hidden-form-mode" type="hidden" name="stay" value="'+ stay +'">').appendTo($form);
        $form.submit();
    });
    
    // Export buttons.
    $('.btn-export').click(function(){
        var format = 'csv'; // TODO
        var html = '<form id="export-request-form" method="post">';
        html += '<input type="hidden" name="export[format]" value="'+ format +'">';
        html += '</form>';
        $(html).insertAfter(this);
        $form = $('#export-request-form');
        $form.submit();
        $form.remove();
    });
    
    // Dynamic table preloader.
    init_ajax_table_loader();

});