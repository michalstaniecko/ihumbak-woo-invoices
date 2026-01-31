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
     * Counter adjustment handler for super-admins.
     */
    var counterAdjustmentHandler = {
        init: function() {
            var $table = $('#ihumbak-numbering-adjustment');
            if (!$table.length) {
                return;
            }

            this.$table = $table;
            this.$loading = $('#ihumbak-numbering-loading');
            this.loadCounterStates();
        },

        loadCounterStates: function() {
            var self = this;

            $.ajax({
                url: ihumbakInvoices.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ihumbak_get_numbering_state',
                    nonce: ihumbakInvoices.nonce
                },
                success: function(response) {
                    self.$loading.hide();
                    if (response.success) {
                        self.renderTable(response.data);
                    } else {
                        self.showError(response.data.message || ihumbakInvoices.i18n.error);
                    }
                },
                error: function() {
                    self.$loading.hide();
                    self.showError(ihumbakInvoices.i18n.error);
                }
            });
        },

        renderTable: function(states) {
            var self = this;
            var $tbody = this.$table.find('tbody');
            $tbody.empty();

            $.each(states, function(type, state) {
                var $row = $('<tr></tr>');

                // Label column.
                $row.append(
                    $('<th scope="row"></th>').text(state.label)
                );

                // Input and button column.
                var $td = $('<td></td>');

                var periodInfo = state.month
                    ? state.year + '/' + String(state.month).padStart(2, '0')
                    : state.year;

                $td.append(
                    $('<span class="description" style="margin-right: 10px;"></span>')
                        .text('(' + periodInfo + ') ')
                );

                $td.append(
                    $('<label style="margin-right: 5px;"></label>')
                        .text(ihumbakInvoices.i18n.nextNumber + ': ')
                );

                var $input = $('<input type="number">')
                    .attr('id', 'counter-' + type)
                    .attr('min', state.min_allowed)
                    .attr('step', 1)
                    .val(state.current_next)
                    .css({width: '100px', marginRight: '10px'})
                    .data('original', state.current_next)
                    .data('type', type);

                $td.append($input);

                var $button = $('<button type="button" class="button"></button>')
                    .text(ihumbakInvoices.i18n.adjust)
                    .data('type', type)
                    .on('click', function() {
                        self.handleAdjust(type, $input);
                    });

                $td.append($button);

                $row.append($td);
                $tbody.append($row);
            });
        },

        handleAdjust: function(type, $input) {
            var self = this;
            var newValue = parseInt($input.val(), 10);
            var originalValue = $input.data('original');

            if (isNaN(newValue) || newValue < 1) {
                alert(ihumbakInvoices.i18n.numberTooLow);
                return;
            }

            if (newValue === originalValue) {
                return;
            }

            var confirmMsg = ihumbakInvoices.i18n.confirmAdjust
                .replace('%1$d', originalValue)
                .replace('%2$d', newValue);

            if (!confirm(confirmMsg)) {
                $input.val(originalValue);
                return;
            }

            $input.prop('disabled', true);

            $.ajax({
                url: ihumbakInvoices.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ihumbak_adjust_numbering',
                    nonce: ihumbakInvoices.nonce,
                    document_type: type,
                    next_number: newValue
                },
                success: function(response) {
                    $input.prop('disabled', false);
                    if (response.success) {
                        $input.data('original', newValue);
                        alert(ihumbakInvoices.i18n.counterAdjusted);
                    } else {
                        alert(response.data.message || ihumbakInvoices.i18n.error);
                        $input.val(originalValue);
                    }
                },
                error: function() {
                    $input.prop('disabled', false);
                    alert(ihumbakInvoices.i18n.error);
                    $input.val(originalValue);
                }
            });
        },

        showError: function(message) {
            this.$table.find('tbody').html(
                '<tr><td colspan="2" style="color: #d63638;">' + message + '</td></tr>'
            );
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

        // Initialize counter adjustment handler.
        counterAdjustmentHandler.init();
    });

})(jQuery);
