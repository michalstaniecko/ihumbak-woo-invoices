/**
 * iHumbak Invoices - Document Edit Scripts
 *
 * Handles dynamic item rows and AJAX calculations.
 * All calculations are performed server-side via AJAX.
 *
 * @package IHumbak\Invoices
 */

(function($) {
    'use strict';

    var DocumentEdit = {
        /**
         * Current item index for new rows.
         */
        itemIndex: 0,

        /**
         * Debounce timer for calculations.
         */
        calculateTimer: null,

        /**
         * Initialize the module.
         */
        init: function() {
            this.itemIndex = this.getMaxItemIndex() + 1;
            this.bindEvents();
            this.recalculateDocument();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            var self = this;

            // Add item button.
            $('#ihumbak-add-item').on('click', function(e) {
                e.preventDefault();
                self.addItemRow();
            });

            // Remove item button (delegated).
            $('#ihumbak-items-body').on('click', '.ihumbak-remove-item', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
                self.recalculateDocument();
            });

            // Item value changes - trigger recalculation.
            $('#ihumbak-items-body').on('change', '.item-quantity, .item-price-net, .item-tax-rate', function() {
                self.debounceRecalculate();
            });

            // Allow typing in price net field.
            $('#ihumbak-items-body').on('input', '.item-price-net', function() {
                self.debounceRecalculate();
            });
        },

        /**
         * Get maximum existing item index.
         *
         * @return {number}
         */
        getMaxItemIndex: function() {
            var maxIndex = -1;
            $('#ihumbak-items-body .ihumbak-item-row').each(function() {
                var index = parseInt($(this).data('index'), 10);
                if (index > maxIndex) {
                    maxIndex = index;
                }
            });
            return maxIndex;
        },

        /**
         * Add a new item row.
         */
        addItemRow: function() {
            var template = $('#ihumbak-item-row-template').html();
            var html = template.replace(/\{\{index\}\}/g, this.itemIndex);

            $('#ihumbak-items-body').append(html);
            this.itemIndex++;

            // Focus on the new row's name field.
            $('#ihumbak-items-body tr:last .item-name').focus();
        },

        /**
         * Debounce recalculation to avoid too many AJAX calls.
         */
        debounceRecalculate: function() {
            var self = this;

            if (this.calculateTimer) {
                clearTimeout(this.calculateTimer);
            }

            this.calculateTimer = setTimeout(function() {
                self.recalculateDocument();
            }, 300);
        },

        /**
         * Recalculate entire document via AJAX.
         */
        recalculateDocument: function() {
            var self = this;
            var items = this.collectItemsData();

            if (items.length === 0) {
                this.updateTotals({
                    subtotal: 0,
                    tax_total: 0,
                    total: 0,
                    formatted: {
                        subtotal: '0,00',
                        tax_total: '0,00',
                        total: '0,00'
                    }
                });
                return;
            }

            $.ajax({
                url: ihumbakInvoices.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ihumbak_calculate_document',
                    nonce: ihumbakInvoices.nonce,
                    items: items
                },
                success: function(response) {
                    if (response.success) {
                        self.updateItemsFromResponse(response.data.items);
                        self.updateTotals(response.data);
                    } else {
                        console.error('Calculation error:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        },

        /**
         * Collect items data from form.
         *
         * @return {Array}
         */
        collectItemsData: function() {
            var items = [];

            $('#ihumbak-items-body .ihumbak-item-row').each(function() {
                var $row = $(this);
                var index = $row.data('index');

                var name = $row.find('.item-name').val();
                if (!name) {
                    return; // Skip empty rows.
                }

                items.push({
                    index: index,
                    name: name,
                    quantity: parseFloat($row.find('.item-quantity').val()) || 1,
                    unit: $row.find('.item-unit').val() || 'szt.',
                    unit_price_net: parseFloat($row.find('.item-price-net').val()) || 0,
                    tax_rate: parseFloat($row.find('.item-tax-rate').val()) || 23,
                    price_type: 'net'
                });
            });

            return items;
        },

        /**
         * Update item rows from AJAX response.
         *
         * @param {Object} itemsData Calculated items data keyed by index.
         */
        updateItemsFromResponse: function(itemsData) {
            var self = this;

            $.each(itemsData, function(index, item) {
                var $row = $('#ihumbak-items-body .ihumbak-item-row[data-index="' + index + '"]');

                if ($row.length === 0) {
                    return;
                }

                // Update calculated values.
                $row.find('.item-price-gross').val(item.unit_price_gross.toFixed(2));
                $row.find('.item-total-net').val(item.line_total_net.toFixed(2));
                $row.find('.item-tax-amount').val(item.tax_amount.toFixed(2));
                $row.find('.item-total-gross').val(item.line_total_gross.toFixed(2));

                // Update display values.
                $row.find('.item-total-net-display').text(item.formatted.line_total_net);
                $row.find('.item-tax-amount-display').text(item.formatted.tax_amount);
                $row.find('.item-total-gross-display').text(item.formatted.line_total_gross);
            });
        },

        /**
         * Update document totals.
         *
         * @param {Object} data Response data with totals.
         */
        updateTotals: function(data) {
            $('#document-subtotal').val(data.subtotal);
            $('#document-tax-total').val(data.tax_total);
            $('#document-total').val(data.total);

            $('#document-subtotal-display').text(data.formatted.subtotal);
            $('#document-tax-total-display').text(data.formatted.tax_total);
            $('#document-total-display').text(data.formatted.total);
        }
    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function() {
        if ($('#ihumbak-document-form').length) {
            DocumentEdit.init();
        }
    });

})(jQuery);
