jQuery(document).ready(function($) {
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
                        $button.prop('disabled', false).text('Save Alt').css({'background': '', 'color': '', 'border-color': ''});
                    }, 1500);
                } else {
                    alert(response.data.message || 'Error updating alt text.');
                    $button.prop('disabled', false).text('Save Alt');
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
                $button.prop('disabled', false).text('Save Alt');
            }
        });
    });
});