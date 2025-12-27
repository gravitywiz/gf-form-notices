jQuery(document).ready(function($) {
    
    // Initialize datepickers
    function initDatepickers() {
        // Find date input fields
        var selectors = [
            'input[name="start_date"]',
            'input[name="_gform_setting_start_date"]',
            '#start_date',
            'input[name="end_date"]',
            'input[name="_gform_setting_end_date"]',
            '#end_date'
        ];
        
        selectors.forEach(function(selector) {
            var $field = $(selector);
            if ($field.length && !$field.hasClass('hasDatepicker')) {
                // Add date_time class to the field container
                $field.closest('.gform-settings-field').addClass('gform-settings-field__date_time');
                
                // Initialize datepicker
                $field.datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '-10:+10',
                    constrainInput: false, // Allow any text to be typed
                    onSelect: function(dateText) {
                        // Update the input value
                        $(this).val(dateText).trigger('change');
                        // Trigger preview
                        previewDate($(this));
                    }
                });
                
                // Add calendar icon after the field
                if (!$field.next('.ui-datepicker-trigger').length) {
                    var imgSrc = window.location.origin + '/wp-content/plugins/gravityforms/images/datepicker/datepicker.svg';
                    $field.after('<button type="button" class="ui-datepicker-trigger"><img src="' + imgSrc + '" alt="Open Date Picker" title="Open Date Picker"></button>');
                    $field.next('.ui-datepicker-trigger').on('click', function(e) {
                        e.preventDefault();
                        $field.datepicker('show');
                    });
                }
            }
        });
    }
    
    // Add preview containers below date fields
    function addDatePreviews() {
        // Try multiple possible selectors
        var selectors = [
            'input[name="start_date"]',
            'input[name="_gform_setting_start_date"]',
            '#start_date'
        ];
        
        // Start Date
        var $startDateField = null;
        for (var i = 0; i < selectors.length; i++) {
            $startDateField = $(selectors[i]);
            if ($startDateField.length) break;
        }
        
        if ($startDateField && $startDateField.length && !$startDateField.next('.ui-datepicker-trigger').next('.gffn-date-preview').length) {
            var $trigger = $startDateField.next('.ui-datepicker-trigger');
            if ($trigger.length) {
                $trigger.after('<div class="gffn-date-preview" style="margin-top: 8px; margin-left: 0; color: #666; font-style: italic; display: block; clear: both;"></div>');
            } else {
                $startDateField.after('<div class="gffn-date-preview" style="margin-top: 8px; color: #666; font-style: italic;"></div>');
            }
        }
        
        // End Date
        var $endDateField = null;
        selectors = [
            'input[name="end_date"]',
            'input[name="_gform_setting_end_date"]',
            '#end_date'
        ];
        
        for (var i = 0; i < selectors.length; i++) {
            $endDateField = $(selectors[i]);
            if ($endDateField.length) break;
        }
        
        if ($endDateField && $endDateField.length && !$endDateField.next('.ui-datepicker-trigger').next('.gffn-date-preview').length) {
            var $trigger = $endDateField.next('.ui-datepicker-trigger');
            if ($trigger.length) {
                $trigger.after('<div class="gffn-date-preview" style="margin-top: 8px; margin-left: 0; color: #666; font-style: italic; display: block; clear: both;"></div>');
            } else {
                $endDateField.after('<div class="gffn-date-preview" style="margin-top: 8px; color: #666; font-style: italic;"></div>');
            }
        }
    }
    
    // Preview date
    function previewDate($field) {
        var date = $field.val().trim();
        var $preview = $field.next('.ui-datepicker-trigger').next('.gffn-date-preview');
        if (!$preview.length) {
            $preview = $field.next('.gffn-date-preview');
        }
        
        if (!date) {
            $preview.html('');
            return;
        }
        
        $preview.html('<span style="color: #999;">Loading preview...</span>');
        
        $.ajax({
            url: gfFormNotices.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gf_form_notices_preview_date',
                nonce: gfFormNotices.nonce,
                date: date
            },
            success: function(response) {
                if (response.success) {
                    $preview.html('Preview: <strong>' + response.data.preview + '</strong>');
                } else {
                    $preview.html('<span style="color: #dc3232;">' + (response.data.message || 'Invalid date') + '</span>');
                }
            },
            error: function() {
                $preview.html('<span style="color: #dc3232;">Error loading preview</span>');
            }
        });
    }
    
    // Initialize on page load
    initDatepickers();
    addDatePreviews();
    
    // Preview on blur and change - use multiple selectors
    var dateSelectors = 'input[name="start_date"], input[name="end_date"], input[name="_gform_setting_start_date"], input[name="_gform_setting_end_date"], #start_date, #end_date';
    
    $(document).on('blur change', dateSelectors, function() {
        previewDate($(this));
    });
    
    // Also preview existing values on load
    setTimeout(function() {
        $(dateSelectors).each(function() {
            if ($(this).val()) {
                previewDate($(this));
            }
        });
    }, 500);
    
});
