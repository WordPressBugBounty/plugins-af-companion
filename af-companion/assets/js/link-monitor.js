jQuery(document).ready(function($) {
    // Run Batch Scan Trigger
    $('#af-run-scan-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $progress = $('#af-scan-progress');

        $btn.prop('disabled', true).text('Scanning Links...');
        $progress.slideDown();

        $.ajax({
            url: afLinkData.ajax_url,
            type: 'POST',
            data: {
                action: 'af_link_run_scan',
                nonce: afLinkData.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Scan error occurred.');
                    $btn.prop('disabled', false).text('Run Content Link Scan Now');
                    $progress.slideUp();
                }
            },
            error: function() {
                alert('Connection error during link scan.');
                $btn.prop('disabled', false).text('Run Content Link Scan Now');
                $progress.slideUp();
            }
        });
    });

    // Recheck Individual Link
    $(document).on('click', '.af-recheck-link-btn', function(e) {
        e.preventDefault();
        var $button = $(this);
        var linkId = $button.data('link-id');

        if (!linkId) return;

        $button.prop('disabled', true).text('Checking...');

        $.ajax({
            url: afLinkData.ajax_url,
            type: 'POST',
            data: {
                action: 'af_link_recheck_single',
                link_id: linkId,
                nonce: afLinkData.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Refresh to reflect updated status code
                } else {
                    alert(response.data.message || 'Error rechecking link.');
                    $button.prop('disabled', false).text('Recheck');
                }
            },
            error: function() {
                alert('Connection error.');
                $button.prop('disabled', false).text('Recheck');
            }
        });
    });
});