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

    /**
     * Delay before auto-fetching order data (ms).
     * Allows DOM to fully render before triggering AJAX.
     *
     * @type {number}
     */
    var AUTO_FETCH_DELAY = 100;

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
            var self = this;

            this.itemIndex = this.getMaxItemIndex() + 1;
            this.bindEvents();
            this.initFetchOrder();

            // Check for pre-filled order ID (from WC order metabox).
            if (window.ihumbakPreFilledOrderId) {
                // Auto-fetch order data when coming from WC order page.
                setTimeout(function() {
                    self.fetchOrderDataAutomatic(window.ihumbakPreFilledOrderId);
                }, AUTO_FETCH_DELAY);
            } else {
                this.recalculateDocument();
            }
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
        },

        /**
         * Initialize fetch order functionality.
         */
        initFetchOrder: function() {
            var self = this;
            var $orderIdInput = $('#order_id');
            var $fetchButton = $('#ihumbak-fetch-order');

            if (!$fetchButton.length) {
                return;
            }

            // Enable/disable button based on input.
            $orderIdInput.on('input', function() {
                var orderId = parseInt($(this).val(), 10);
                $fetchButton.prop('disabled', !orderId || orderId < 1);
            });

            // Trigger on load.
            $orderIdInput.trigger('input');

            // Fetch button click.
            $fetchButton.on('click', function(e) {
                e.preventDefault();
                var orderId = parseInt($orderIdInput.val(), 10);
                if (orderId > 0) {
                    self.fetchOrderData(orderId);
                }
            });
        },

        /**
         * Fetch order data via AJAX (manual trigger with confirmation).
         *
         * @param {number} orderId Order ID.
         */
        fetchOrderData: function(orderId) {
            this._doFetchOrderData(orderId, this.confirmAndPopulate.bind(this), false);
        },

        /**
         * Fetch order data automatically (from WC order metabox).
         * Does not ask for confirmation, always replaces.
         *
         * @param {number} orderId Order ID.
         */
        fetchOrderDataAutomatic: function(orderId) {
            var self = this;
            this._doFetchOrderData(orderId, function(data) {
                self.populateFromOrderData(data, 'replace');
                self.showNotice('success', ihumbakInvoices.i18n.orderDataLoaded || 'Order data loaded successfully.');
            }, true);
        },

        /**
         * Internal method to fetch order data via AJAX.
         *
         * @param {number}   orderId            Order ID.
         * @param {Function} onSuccess          Callback on successful fetch.
         * @param {boolean}  recalculateOnError Whether to recalculate on error.
         * @private
         */
        _doFetchOrderData: function(orderId, onSuccess, recalculateOnError) {
            var self = this;
            var $button = $('#ihumbak-fetch-order');
            var $spinner = $('#ihumbak-fetch-status');

            // Show loading state.
            $button.prop('disabled', true);
            $spinner.addClass('is-active');

            $.ajax({
                url: ihumbakInvoices.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ihumbak_fetch_order_data',
                    nonce: ihumbakInvoices.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        onSuccess(response.data);
                    } else {
                        self.showNotice('error', response.data.message || ihumbakInvoices.i18n.error);
                        if (recalculateOnError) {
                            self.recalculateDocument();
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    self.showNotice('error', ihumbakInvoices.i18n.error);
                    if (recalculateOnError) {
                        self.recalculateDocument();
                    }
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        },

        /**
         * Confirm and populate form with order data.
         *
         * @param {Object} data Order data from AJAX response.
         */
        confirmAndPopulate: function(data) {
            var hasItems = $('#ihumbak-items-body .ihumbak-item-row').length > 0;
            var mode = 'replace';

            if (hasItems) {
                var confirmMsg = ihumbakInvoices.i18n.replaceItemsConfirm ||
                    'The form already contains items. Do you want to replace them with order data?';

                if (confirm(confirmMsg)) {
                    mode = 'replace';
                } else {
                    mode = 'append';
                }
            }

            this.populateFromOrderData(data, mode);
            this.showNotice('success', ihumbakInvoices.i18n.orderDataLoaded || 'Order data loaded successfully.');
        },

        /**
         * Populate form with order data.
         *
         * @param {Object} data Order data.
         * @param {string} mode 'replace' or 'append'.
         */
        populateFromOrderData: function(data, mode) {
            var self = this;

            if (mode === 'replace') {
                // Clear existing items.
                $('#ihumbak-items-body').empty();
                this.itemIndex = 0;
            }

            // Add items.
            if (data.items && data.items.length > 0) {
                data.items.forEach(function(item) {
                    self.addItemRowWithData(item);
                });
            }

            // Populate buyer fields.
            if (data.buyer) {
                this.populateBuyerFields(data.buyer);
            }

            // Set payment method (invoice only).
            if (data.payment_method && $('#payment_method').length) {
                $('#payment_method').val(data.payment_method);
            }

            // Recalculate document totals.
            this.recalculateDocument();
        },

        /**
         * Add item row with pre-filled data.
         *
         * @param {Object} itemData Item data.
         */
        addItemRowWithData: function(itemData) {
            var template = $('#ihumbak-item-row-template').html();
            var html = template.replace(/\{\{index\}\}/g, this.itemIndex);

            var $row = $(html);

            // Fill in the data.
            $row.find('.item-name').val(itemData.name || '');
            $row.find('.item-sku').val(itemData.sku || '');
            $row.find('.item-quantity').val(itemData.quantity || 1);
            $row.find('.item-unit').val(itemData.unit || 'szt.');
            $row.find('.item-price-net').val((itemData.unit_price_net || 0).toFixed(2));
            $row.find('.item-tax-rate').val(itemData.tax_rate || 23);
            $row.find('.item-price-gross').val((itemData.unit_price_gross || 0).toFixed(2));
            $row.find('.item-total-net').val((itemData.line_total_net || 0).toFixed(2));
            $row.find('.item-tax-amount').val((itemData.tax_amount || 0).toFixed(2));
            $row.find('.item-total-gross').val((itemData.line_total_gross || 0).toFixed(2));

            // Update display values.
            if (itemData.formatted) {
                $row.find('.item-total-net-display').text(itemData.formatted.line_total_net);
                $row.find('.item-tax-amount-display').text(itemData.formatted.tax_amount);
                $row.find('.item-total-gross-display').text(itemData.formatted.line_total_gross);
            }

            // Add hidden product_id if available.
            if (itemData.product_id) {
                $row.append('<input type="hidden" name="items[' + this.itemIndex + '][product_id]" value="' + itemData.product_id + '">');
            }

            $('#ihumbak-items-body').append($row);
            this.itemIndex++;
        },

        /**
         * Populate buyer fields from order data.
         *
         * @param {Object} buyerData Buyer data.
         */
        populateBuyerFields: function(buyerData) {
            $('#buyer_name').val(buyerData.name || '');
            $('#buyer_nip').val(buyerData.nip || '');
            $('#buyer_address').val(buyerData.address || '');
            $('#buyer_postcode').val(buyerData.postcode || '');
            $('#buyer_city').val(buyerData.city || '');
            $('#buyer_country').val(buyerData.country || 'PL');
            $('#buyer_email').val(buyerData.email || '');
            $('#buyer_phone').val(buyerData.phone || '');
        },

        /**
         * Show notice message.
         *
         * @param {string} type 'success' or 'error'.
         * @param {string} message Message to display.
         */
        showNotice: function(type, message) {
            // Remove existing notices.
            $('.ihumbak-ajax-notice').remove();

            // Build notice safely using jQuery methods to prevent XSS.
            var $notice = $('<div></div>')
                .addClass('notice is-dismissible ihumbak-ajax-notice')
                .addClass('notice-' + (type === 'error' ? 'error' : 'success'));

            var $message = $('<p></p>').text(message);
            $notice.append($message);

            $('.wp-header-end').after($notice);

            // Auto-dismiss after 5 seconds.
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
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
