jQuery(document).ready(function($) {
    // 1. Clean Button Event
    $('.af-clean-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var target = $btn.data('target');
        var originalText = $btn.text();

        if (!confirm(afDBData.confirm)) {
            return;
        }

        $btn.prop('disabled', true).text('Cleaning...');

        $.post(afDBData.ajax_url, {
            action: 'af_db_cleaner_execute',
            nonce: afDBData.nonce,
            target: target
        }, function(response) {
            $btn.prop('disabled', false).text('Clean Complete');
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);

            if (response.success) {
                refreshDBScan(true); // Pass true to show notice on clean completion too
            } else {
                alert(response.data.message || 'Error executing cleanup.');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text(originalText);
            alert('A server communication error occurred.');
        });
    });

    // 2. Refresh Scan Event
    $('#af-db-refresh-scan').on('click', function(e) {
        e.preventDefault();
        refreshDBScan(true); // Show success notice on manual scan
    });

    function refreshDBScan(showNotice) {
        var $scanBtn = $('#af-db-refresh-scan');
        var originalBtnText = $scanBtn.text();
        var $notice = $('#af-scan-notice');

        // Set button into loading state
        $scanBtn.prop('disabled', true).text('Scanning Database...');
        $notice.hide();

        $.post(afDBData.ajax_url, {
            action: 'af_db_cleaner_preview',
            nonce: afDBData.nonce
        }, function(response) {
            // Restore button state
            $scanBtn.prop('disabled', false).text(originalBtnText);

            if (response.success) {
                var stats = response.data.stats;
                $('#stat-revisions').text(stats.revisions);
                $('#stat-transients').text(stats.expired_transients);
                $('#stat-bad_comments').text(stats.bad_comments);
                $('#stat-orphaned_postmeta').text(stats.orphaned_postmeta);

                // Show visual success notification if requested
                if (showNotice) {
                    $notice.css('display', 'inline-flex').hide().fadeIn(250);
                    setTimeout(function() {
                        $notice.fadeOut(400);
                    }, 3500);
                }
            } else {
                alert(response.data.message || 'Error scanning database overhead.');
            }
        }).fail(function() {
            $scanBtn.prop('disabled', false).text(originalBtnText);
            alert('A server communication error occurred during scan.');
        });
    }
});