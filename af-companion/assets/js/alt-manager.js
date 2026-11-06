jQuery(document).ready(function($) {

    // Model Selector Change Event
    $('#af_ai_model_select').on('change', function() {
        var selectedModel = $(this).val();

        $.post(afAltData.ajax_url, {
            action: 'af_save_selected_ai_model',
            nonce: afAltData.nonce,
            model: selectedModel
        }, function(response) {
            if (response.success) {
                // Subtle visual feedback on dropdown change
                $('#af_ai_model_select').css('border-color', '#10b981').animate({ borderColor: '#cbd5e1' }, 1200);
            }
        });
    });

    // 1. Manual Save Button Event
    $(document).on('click', '.af-save-alt-btn', function(e) {
        e.preventDefault();

        var $button = $(this);
        var attachmentId = $button.data('attachment-id');
        var $input = $('#alt-input-' + attachmentId);
        var altText = $input.val();

        if (!attachmentId) {
            return;
        }

        $button.prop('disabled', true).text('Saving...');

        $.ajax({
            url: afAltData.ajax_url,
            type: 'POST',
            data: {
                action: 'af_alt_quick_update',
                attachment_id: attachmentId,
                alt_text: altText,
                nonce: afAltData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.text('Saved ✓').css({'background': '#059669', 'color': '#fff', 'border-color': '#059669'});
                    setTimeout(function() {
                        $button.prop('disabled', false).text('Save').css({'background': '', 'color': '', 'border-color': ''});
                    }, 1500);
                } else {
                    alert(response.data.message || 'Error updating alt text.');
                    $button.prop('disabled', false).text('Save');
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
                $button.prop('disabled', false).text('Save');
            }
        });
    });

    // 2. Context AI Generation Event
    $(document).on('click', '.af-ai-alt-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var attachmentId = $btn.data('attachment-id');
        var $input = $('#alt-input-' + attachmentId);
        var originalHtml = $btn.html();

        $('.af-ai-error-notice').remove();

        $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0 4px 0 0; width:14px; height:14px;"></span> Generating...');

        $.post(afAltData.ajax_url, {
            action: 'af_alt_generate_ai',
            nonce: afAltData.nonce,
            attachment_id: attachmentId
        }, function(response) {
            $btn.prop('disabled', false).html(originalHtml);

            if (response.success) {
                $input.val(response.data.alt_text);
                $input.css('border-color', '#10b981').animate({ borderColor: '#cbd5e1' }, 1500);
            } else {
                var errorMsg = response.data.message || 'AI generation failed.';
                
                if (errorMsg.indexOf('href=') !== -1) {
                    var $notice = $('<div class="notice notice-error af-ai-error-notice is-dismissible" style="margin: 15px 0; padding: 12px; border-left: 4px solid #dc2626;"><p style="margin:0; font-size: 13px;">' + errorMsg + '</p></div>');
                    $('.af-status-header').after($notice);
                } else {
                    alert('AI Notice: ' + errorMsg);
                }
            }
        }).fail(function() {
            $btn.prop('disabled', false).html(originalHtml);
            alert('Server communication error occurred.');
        });
    });

});