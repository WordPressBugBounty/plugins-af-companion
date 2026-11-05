jQuery(document).ready(function($) {
    // Use event delegation in case elements are re-rendered dynamically
    $(document).on('click', '.af-mark-reviewed-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var postId  = $button.data('post-id');

        if (!postId) {
            return;
        }

        $button.prop('disabled', true).text('Updating...');

        $.ajax({
            url: afCMData.ajax_url,
            type: 'POST',
            data: {
                action: 'af_mark_content_reviewed', // Ensure this matches your PHP add_action('wp_ajax_...')
                post_id: postId,
                nonce: afCMData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update last reviewed date text
                    $('#reviewed-date-' + postId).html(response.data.formatted_date);
                    
                    // Transition status badge pill from stale to fresh
                    $button.closest('tr').find('.af-status-pill')
                           .removeClass('status-stale')
                           .addClass('status-fresh')
                           .text('Fresh');
                           
                    $button.text('Reviewed ✓').css('background', '#059669').css('color', '#fff');
                } else {
                    alert(response.data.message || 'Error updating record.');
                    $button.prop('disabled', false).text('Mark Reviewed');
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
                $button.prop('disabled', false).text('Mark Reviewed');
            }
        });
    });
});