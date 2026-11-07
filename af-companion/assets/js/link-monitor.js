jQuery(document).ready(function($) {
    // Run Batch Scan Trigger
    $('#af-run-scan-btn').off('click.afScan').on('click.afScan', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $progress = $('#af-scan-progress');

        if ($btn.prop('disabled')) return;

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

    // Recheck Individual Link (Sitewide Table)
    $(document).off('click.afRecheckSingle').on('click.afRecheckSingle', '.af-recheck-link-btn', function(e) {
        e.preventDefault();
        var $button = $(this);
        var linkId = $button.data('link-id');

        if (!linkId || $button.prop('disabled')) return;

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
                    var $row = $button.closest('tr');
                    var statusLower = response.data.status.toLowerCase();
                    var pillClass = 'status-fresh';

                    if (statusLower === 'broken') {
                        pillClass = 'status-stale';
                    } else if (statusLower === 'unverified') {
                        pillClass = '';
                    }

                    $row.find('strong').text(response.data.code);
                    $row.find('.af-status-pill').text(response.data.status).attr('class', 'af-status-pill ' + pillClass);
                    $button.prop('disabled', false).text('Recheck');
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

    // Recheck Links for a Specific Post (Editor Meta Box)
    $(document).off('click.afRecheckPost').on('click.afRecheckPost', '.af-recheck-post-btn', function(e) {
        e.preventDefault();
        var $button = $(this);
        var postId = $button.data('post-id');
        var $spinner = $('#af-post-recheck-spinner');

        if (!postId || $button.prop('disabled')) return;

        // Extract live Gutenberg or Classic editor content buffer
        var liveContent = '';
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            liveContent = wp.data.select('core/editor').getEditedPostAttribute('content') || '';
        } else if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
            liveContent = tinyMCE.get('content').getContent();
        } else {
            liveContent = $('#content').val() || '';
        }

        $button.prop('disabled', true);
        $spinner.addClass('is-active');

        $.ajax({
            url: afLinkData.ajax_url,
            type: 'POST',
            data: {
                action: 'af_link_recheck_post',
                post_id: postId,
                post_content: liveContent,
                nonce: afLinkData.nonce
            },
            success: function(response) {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');

                if (response.success) {
                    // Dynamically update only the meta box inner contents without reloading the page
                    if (response.data.html) {
                        $('#af_content_link_audit_box .inside').html(response.data.html);
                    } else {
                        location.reload();
                    }
                } else {
                    alert(response.data.message || 'Error rechecking post links.');
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                alert('Connection error during post link recheck.');
            }
        });
    });
});