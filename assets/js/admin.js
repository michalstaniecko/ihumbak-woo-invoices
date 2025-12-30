/**
 * iHumbak Invoices - Admin Scripts
 *
 * @package IHumbak\Invoices
 */

(function($) {
    'use strict';

    /**
     * Logo upload handler.
     */
    var logoUploader = {
        frame: null,

        init: function() {
            $('#upload_logo_button').on('click', this.openMediaFrame.bind(this));
            $('#remove_logo_button').on('click', this.removeLogo.bind(this));
            this.loadPreview();
        },

        openMediaFrame: function(e) {
            e.preventDefault();

            if (this.frame) {
                this.frame.open();
                return;
            }

            this.frame = wp.media({
                title: ihumbakInvoices.selectLogo || 'Select Logo',
                button: {
                    text: ihumbakInvoices.useLogo || 'Use this logo'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            this.frame.on('select', this.onSelect.bind(this));
            this.frame.open();
        },

        onSelect: function() {
            var attachment = this.frame.state().get('selection').first().toJSON();
            $('#pdf_logo_id').val(attachment.id);
            this.showPreview(attachment.url);
        },

        removeLogo: function(e) {
            e.preventDefault();
            $('#pdf_logo_id').val(0);
            $('#logo_preview').empty();
        },

        loadPreview: function() {
            var logoId = $('#pdf_logo_id').val();
            if (logoId && logoId !== '0') {
                wp.media.attachment(logoId).fetch().then(function(data) {
                    logoUploader.showPreview(data.url);
                });
            }
        },

        showPreview: function(url) {
            $('#logo_preview').html('<img src="' + url + '" alt="Logo preview">');
        }
    };

    /**
     * Revert to draft form handler.
     */
    var revertFormHandler = {
        init: function() {
            var $form = $('#ihumbak-revert-form');
            if ($form.length) {
                $form.on('submit', this.handleSubmit.bind(this));
            }
        },

        handleSubmit: function(e) {
            var $form = $(e.currentTarget);
            var confirmMessage = $form.data('confirm-message');

            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }

            return true;
        }
    };

    /**
     * Resend email confirmation handler.
     */
    var resendEmailHandler = {
        init: function() {
            $(document).on('click', '.ihumbak-resend-email', this.handleClick.bind(this));
        },

        handleClick: function(e) {
            var $link = $(e.currentTarget);
            var confirmMessage = $link.data('confirm');

            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }

            return true;
        }
    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function() {
        // Initialize logo uploader if on settings page.
        if ($('#upload_logo_button').length) {
            logoUploader.init();
        }

        // Initialize revert form handler.
        revertFormHandler.init();

        // Initialize resend email handler.
        resendEmailHandler.init();
    });

})(jQuery);
