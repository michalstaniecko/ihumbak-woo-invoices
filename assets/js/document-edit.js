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
         * Whether the document is in readonly mode (issued, not draft).
         */
        isReadonly: false,

        /**
         * Initialize the module.
         */
        init: function() {
            var self = this;

            // Check if document is in readonly mode.
            this.isReadonly = ihumbakInvoices.isReadonly || false;

            this.itemIndex = this.getMaxItemIndex() + 1;
            this.bindEvents();
            this.initFetchOrder();
            this.initCreditNote();
            this.initReceiptReturn();

            // Form validation before submit.
            $('#ihumbak-document-form').on('submit', function(e) {
                // Validate corrected document selection for credit notes and receipt returns.
                if (!self.validateCorrectedDocument()) {
                    e.preventDefault();
                    return false;
                }

                if (!self.validateItems()) {
                    e.preventDefault();
                    return false;
                }
            });

            // Check for pre-filled order ID (from WC order metabox).
            if (window.ihumbakPreFilledOrderId) {
                // Auto-fetch order data when coming from WC order page.
                setTimeout(function() {
                    self.fetchOrderDataAutomatic(window.ihumbakPreFilledOrderId);
                }, AUTO_FETCH_DELAY);
            } else if (window.ihumbakPreSelectedInvoiceId) {
                // Auto-fetch invoice data when coming with pre-selected invoice.
                setTimeout(function() {
                    self.fetchInvoiceDataAutomatic(window.ihumbakPreSelectedInvoiceId);
                }, AUTO_FETCH_DELAY);
            } else if (window.ihumbakPreSelectedReceiptId) {
                // Auto-fetch receipt data when coming with pre-selected receipt.
                setTimeout(function() {
                    self.fetchReceiptDataAutomatic(window.ihumbakPreSelectedReceiptId);
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

            // Skip binding edit events if in readonly mode.
            if (this.isReadonly) {
                return;
            }

            // Add item button.
            $('#ihumbak-add-item').on('click', function(e) {
                e.preventDefault();
                self.addItemRow();
            });

            // Remove item button (delegated) - removes both rows with same data-index.
            $('#ihumbak-items-body').on('click', '.ihumbak-remove-item', function(e) {
                e.preventDefault();
                var $row = $(this).closest('tr');
                var index = $row.data('index');
                // Remove both rows with same data-index (two-row layout).
                $('#ihumbak-items-body tr[data-index="' + index + '"]').remove();
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
         * Add a new item row (two-row layout).
         */
        addItemRow: function() {
            var template = $('#ihumbak-item-row-template').html();
            var html = template.replace(/\{\{index\}\}/g, this.itemIndex);

            $('#ihumbak-items-body').append(html);
            this.itemIndex++;

            // Focus on the new row's name field (in the name row).
            $('#ihumbak-items-body .ihumbak-item-row-name:last .item-name').focus();
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
         * Validate corrected document selection for credit notes and receipt returns.
         * Checks that user has selected an original document to correct.
         *
         * @return {boolean} True if valid, false otherwise.
         */
        validateCorrectedDocument: function() {
            var $select = $('#corrected_document_id');

            // Skip validation if not on correction document page.
            if (!$select.length) {
                return true;
            }

            // Check if manual entry mode is enabled.
            var isManualEntry = $('#is_manual_entry').val() === '1';

            if (isManualEntry) {
                // Validate manual entry fields.
                var originalNumber = $.trim($('#original_document_number').val());
                if (!originalNumber) {
                    this.showNotice('error', ihumbakInvoices.i18n.selectOriginalDocument || 'Please enter the original document number.');
                    $('#original_document_number').addClass('ihumbak-input-error').focus();
                    return false;
                }
                $('#original_document_number').removeClass('ihumbak-input-error');
            } else {
                // Validate dropdown selection.
                var selectedId = parseInt($select.val(), 10);
                if (!selectedId || selectedId < 1) {
                    this.showNotice('error', ihumbakInvoices.i18n.selectOriginalDocument || 'Please select the original document to correct.');
                    $select.addClass('ihumbak-input-error').focus();
                    return false;
                }
                $select.removeClass('ihumbak-input-error');
            }

            return true;
        },

        /**
         * Validate items before form submission.
         * Checks that all items with values have a name.
         *
         * @return {boolean} True if valid, false otherwise.
         */
        validateItems: function() {
            var hasError = false;

            $('#ihumbak-items-body .ihumbak-item-row-name').each(function() {
                var $row = $(this);
                var $nameInput = $row.find('.item-name');
                var name = $.trim($nameInput.val());

                // Every item row must have a name (after trim).
                if (name === '') {
                    hasError = true;
                    $nameInput.addClass('ihumbak-input-error');
                } else {
                    $nameInput.removeClass('ihumbak-input-error');
                }
            });

            if (hasError) {
                alert(ihumbakInvoices.i18n.nameRequiredError || 'Please enter a product name for all items.');
                return false;
            }

            return true;
        },

        /**
         * Recalculate entire document via AJAX.
         */
        recalculateDocument: function() {
            var self = this;

            // Skip recalculation in readonly mode - values are already set server-side.
            if (this.isReadonly) {
                return;
            }

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

            // Iterate only through name rows (one per item) to avoid duplicates.
            $('#ihumbak-items-body .ihumbak-item-row-name').each(function() {
                var $nameRow = $(this);
                var index = $nameRow.data('index');

                // Get the corresponding values row.
                var $valuesRow = $('#ihumbak-items-body .ihumbak-item-row-values[data-index="' + index + '"]');

                var name = $nameRow.find('.item-name').val();
                if (!name) {
                    return; // Skip empty rows.
                }

                items.push({
                    index: index,
                    name: name,
                    quantity: parseFloat($valuesRow.find('.item-quantity').val()) || 1,
                    unit: $nameRow.find('.item-unit').val() || 'pcs',
                    unit_price_net: parseFloat($valuesRow.find('.item-price-net').val()) || 0,
                    tax_rate: parseFloat($valuesRow.find('.item-tax-rate').val()) || 23,
                    price_type: 'net'
                });
            });

            return items;
        },

        /**
         * Update item rows from AJAX response (two-row layout).
         *
         * @param {Object} itemsData Calculated items data keyed by index.
         */
        updateItemsFromResponse: function(itemsData) {
            $.each(itemsData, function(index, item) {
                var $nameRow = $('#ihumbak-items-body .ihumbak-item-row-name[data-index="' + index + '"]');
                var $valuesRow = $('#ihumbak-items-body .ihumbak-item-row-values[data-index="' + index + '"]');

                if ($nameRow.length === 0 || $valuesRow.length === 0) {
                    return;
                }

                // Update price gross in name row (hidden input).
                $nameRow.find('.item-price-gross').val(item.unit_price_gross.toFixed(2));

                // Update calculated values in values row.
                $valuesRow.find('.item-total-net').val(item.line_total_net.toFixed(2));
                $valuesRow.find('.item-tax-amount').val(item.tax_amount.toFixed(2));
                $valuesRow.find('.item-total-gross').val(item.line_total_gross.toFixed(2));

                // Update display values in values row.
                $valuesRow.find('.item-total-net-display').text(item.formatted.line_total_net);
                $valuesRow.find('.item-tax-amount-display').text(item.formatted.tax_amount);
                $valuesRow.find('.item-total-gross-display').text(item.formatted.line_total_gross);
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

            // Set payment method fields (invoice only).
            if (data.payment_method && $('#payment_method').length) {
                // Handle both old string format and new object format.
                if (typeof data.payment_method === 'object') {
                    // New format with type, id, title.
                    $('#payment_method').val(data.payment_method.type || '');
                    $('#payment_method_id').val(data.payment_method.id || '');
                    $('#payment_method_title').val(data.payment_method.title || '');
                } else {
                    // Old string format (backward compatibility).
                    $('#payment_method').val(data.payment_method);
                }
            }

            // Set payment date if order was paid.
            // Also set due_date to payment_date for paid orders.
            if (data.payment_date && $('#payment_date').length) {
                $('#payment_date').val(data.payment_date);
                $('#due_date').val(data.payment_date);
            }

            // Recalculate document totals.
            this.recalculateDocument();
        },

        /**
         * Add item row with pre-filled data (two-row layout).
         *
         * @param {Object} itemData Item data.
         */
        addItemRowWithData: function(itemData) {
            var template = $('#ihumbak-item-row-template').html();
            var html = template.replace(/\{\{index\}\}/g, this.itemIndex);

            var $rows = $(html);

            // Fill in the data (jQuery .find() searches across all elements in collection).
            $rows.find('.item-name').val(itemData.name || '');
            $rows.find('.item-sku').val(itemData.sku || '');
            $rows.find('.item-quantity').val(itemData.quantity || 1);
            $rows.find('.item-unit').val(itemData.unit || 'pcs');
            $rows.find('.item-price-net').val((itemData.unit_price_net || 0).toFixed(2));
            $rows.find('.item-tax-rate').val(itemData.tax_rate || 23);
            $rows.find('.item-price-gross').val((itemData.unit_price_gross || 0).toFixed(2));
            $rows.find('.item-total-net').val((itemData.line_total_net || 0).toFixed(2));
            $rows.find('.item-tax-amount').val((itemData.tax_amount || 0).toFixed(2));
            $rows.find('.item-total-gross').val((itemData.line_total_gross || 0).toFixed(2));

            // Update display values.
            if (itemData.formatted) {
                $rows.find('.item-total-net-display').text(itemData.formatted.line_total_net);
                $rows.find('.item-tax-amount-display').text(itemData.formatted.tax_amount);
                $rows.find('.item-total-gross-display').text(itemData.formatted.line_total_gross);
            }

            // Add hidden product_id if available (to the name row).
            if (itemData.product_id) {
                $rows.filter('.ihumbak-item-row-name').find('.column-name').append(
                    '<input type="hidden" name="items[' + this.itemIndex + '][product_id]" value="' + itemData.product_id + '">'
                );
            }

            $('#ihumbak-items-body').append($rows);
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
        },

        /**
         * Initialize credit note functionality.
         */
        initCreditNote: function() {
            var self = this;
            var $loadInvoiceButton = $('#ihumbak-load-invoice');
            var $loadRefundButton = $('#ihumbak-load-refund');
            var $invoiceSelect = $('#corrected_document_id');

            if (!$loadInvoiceButton.length && !$('#is_manual_entry').length) {
                return; // Not on credit note page.
            }

            // Entry mode toggle handler.
            this.initEntryModeToggle();

            // Load invoice data button click.
            $loadInvoiceButton.on('click', function(e) {
                e.preventDefault();
                var invoiceId = parseInt($invoiceSelect.val(), 10);
                if (invoiceId > 0) {
                    self.fetchInvoiceData(invoiceId);
                } else {
                    self.showNotice('error', ihumbakInvoices.i18n.selectInvoice || 'Please select an invoice first.');
                }
            });

            // Load refund data button click.
            if ($loadRefundButton.length) {
                $loadRefundButton.on('click', function(e) {
                    e.preventDefault();
                    var refundId = parseInt($('#refund_id').val(), 10);
                    if (refundId > 0) {
                        self.fetchRefundData(refundId);
                    } else {
                        self.showNotice('error', ihumbakInvoices.i18n.selectRefund || 'Please select a refund first.');
                    }
                });
            }

            // Enable/disable load button and show warning based on selection.
            $invoiceSelect.on('change', function() {
                var invoiceId = parseInt($(this).val(), 10);
                $loadInvoiceButton.prop('disabled', !invoiceId || invoiceId < 1);

                // Show/hide correction warning.
                var $selected = $(this).find('option:selected');
                var hasCorrections = $selected.data('has-corrections') === 1 || $selected.data('has-corrections') === '1';
                var $warning = $('#correction-warning');

                if (hasCorrections) {
                    $warning.show();
                } else {
                    $warning.hide();
                }
            });

            // Trigger on load.
            $invoiceSelect.trigger('change');
        },

        /**
         * Initialize receipt return functionality.
         */
        initReceiptReturn: function() {
            var self = this;
            var $loadReceiptButton = $('#ihumbak-load-receipt');
            var $loadRefundButton = $('#ihumbak-load-refund');
            var $receiptSelect = $('#corrected_document_id');

            // Check if we're on receipt return page (has load-receipt button OR manual entry field).
            // But NOT on credit note page (which has load-invoice button).
            if (!$loadReceiptButton.length || $('#ihumbak-load-invoice').length) {
                return; // Not on receipt return page.
            }

            // Entry mode toggle handler (shared with credit notes).
            this.initEntryModeToggle();

            // Load receipt data button click.
            $loadReceiptButton.on('click', function(e) {
                e.preventDefault();
                var receiptId = parseInt($receiptSelect.val(), 10);
                if (receiptId > 0) {
                    self.fetchReceiptData(receiptId);
                } else {
                    self.showNotice('error', ihumbakInvoices.i18n.selectReceipt || 'Please select a receipt first.');
                }
            });

            // Load refund data button click.
            if ($loadRefundButton.length) {
                $loadRefundButton.on('click', function(e) {
                    e.preventDefault();
                    var refundId = parseInt($('#refund_id').val(), 10);
                    if (refundId > 0) {
                        self.fetchRefundData(refundId);
                    } else {
                        self.showNotice('error', ihumbakInvoices.i18n.selectRefund || 'Please select a refund first.');
                    }
                });
            }

            // Enable/disable load button based on selection.
            $receiptSelect.on('change', function() {
                var receiptId = parseInt($(this).val(), 10);
                $loadReceiptButton.prop('disabled', !receiptId || receiptId < 1);

                // Show/hide return warning for receipts with existing returns.
                var $selected = $(this).find('option:selected');
                var hasReturns = $selected.data('has-returns') === 1 || $selected.data('has-returns') === '1';
                var $warning = $('#return-warning');

                if (hasReturns && $warning.length) {
                    $warning.show();
                } else if ($warning.length) {
                    $warning.hide();
                }
            });

            // Trigger on load.
            $receiptSelect.trigger('change');
        },

        /**
         * Initialize entry mode toggle for credit notes.
         * Handles switching between system invoice selection and manual entry.
         */
        initEntryModeToggle: function() {
            var $entryModeRadios = $('input[name="entry_mode"]');
            var $isManualEntryField = $('#is_manual_entry');
            var $systemModeRow = $('#system-mode-row');
            var $manualModeNumberRow = $('#manual-mode-number-row');
            var $manualModeDateRow = $('#manual-mode-date-row');
            var $invoiceSelect = $('#corrected_document_id');
            var $originalNumberInput = $('#original_document_number');

            if (!$entryModeRadios.length) {
                return;
            }

            /**
             * Toggle visibility of entry mode sections.
             *
             * @param {string} mode 'system' or 'manual'.
             */
            function toggleEntryMode(mode) {
                var isManual = mode === 'manual';

                // Update hidden field.
                $isManualEntryField.val(isManual ? '1' : '0');

                // Toggle visibility.
                if (isManual) {
                    $systemModeRow.hide();
                    $manualModeNumberRow.show();
                    $manualModeDateRow.show();

                    // Clear system mode selection.
                    $invoiceSelect.val('');

                    // Set required on manual field.
                    $originalNumberInput.prop('required', true);
                } else {
                    $systemModeRow.show();
                    $manualModeNumberRow.hide();
                    $manualModeDateRow.hide();

                    // Clear manual fields.
                    $originalNumberInput.val('');
                    $('#original_document_date').val('');

                    // Remove required from manual field.
                    $originalNumberInput.prop('required', false);
                }
            }

            // Handle radio change.
            $entryModeRadios.on('change', function() {
                toggleEntryMode($(this).val());
            });

            // Set initial state based on current selection.
            var currentMode = $entryModeRadios.filter(':checked').val() || 'system';
            toggleEntryMode(currentMode);
        },

        /**
         * Fetch invoice data via AJAX (manual trigger).
         *
         * @param {number} invoiceId Invoice ID.
         */
        fetchInvoiceData: function(invoiceId) {
            var self = this;
            var hasItems = $('#ihumbak-items-body .ihumbak-item-row').length > 0;
            var mode = 'replace';

            if (hasItems) {
                var confirmMsg = ihumbakInvoices.i18n.replaceItemsConfirm ||
                    'The form already contains items. Do you want to replace them with invoice data?';

                if (!confirm(confirmMsg)) {
                    mode = 'append';
                }
            }

            this._doFetchInvoiceData(invoiceId, function(data) {
                self.populateFromInvoiceData(data, mode);
                self.showNotice('success', ihumbakInvoices.i18n.invoiceDataLoaded || 'Invoice data loaded successfully.');
            });
        },

        /**
         * Fetch invoice data automatically (from pre-selected invoice).
         *
         * @param {number} invoiceId Invoice ID.
         */
        fetchInvoiceDataAutomatic: function(invoiceId) {
            var self = this;
            this._doFetchInvoiceData(invoiceId, function(data) {
                self.populateFromInvoiceData(data, 'replace');
                self.showNotice('success', ihumbakInvoices.i18n.invoiceDataLoaded || 'Invoice data loaded successfully.');
            });
        },

        /**
         * Internal method to fetch invoice data via AJAX.
         *
         * @param {number}   invoiceId  Invoice ID.
         * @param {Function} onSuccess  Callback on successful fetch.
         * @private
         */
        _doFetchInvoiceData: function(invoiceId, onSuccess) {
            var self = this;
            var $button = $('#ihumbak-load-invoice');
            var $spinner = $('#ihumbak-load-status');

            // Show loading state.
            $button.prop('disabled', true);
            $spinner.addClass('is-active');

            $.ajax({
                url: ihumbakInvoices.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ihumbak_fetch_invoice_data',
                    nonce: ihumbakInvoices.nonce,
                    invoice_id: invoiceId
                },
                success: function(response) {
                    if (response.success) {
                        onSuccess(response.data);
                    } else {
                        self.showNotice('error', response.data.message || ihumbakInvoices.i18n.error);
                        self.recalculateDocument();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    self.showNotice('error', ihumbakInvoices.i18n.error);
                    self.recalculateDocument();
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        },

        /**
         * Populate credit note form from invoice data.
         *
         * @param {Object} data Invoice data.
         * @param {string} mode 'replace' or 'append'.
         */
        populateFromInvoiceData: function(data, mode) {
            var self = this;

            if (mode === 'replace') {
                // Clear existing items.
                $('#ihumbak-items-body').empty();
                this.itemIndex = 0;
            }

            // Add items (with negative quantities for credit note).
            if (data.items && data.items.length > 0) {
                data.items.forEach(function(item) {
                    // Negate quantity for credit note.
                    item.quantity = -Math.abs(item.quantity || 1);
                    self.addItemRowWithData(item);
                });
            }

            // Populate buyer fields.
            if (data.buyer) {
                this.populateBuyerFields(data.buyer);
            }

            // Populate seller fields.
            if (data.seller) {
                this.populateSellerFields(data.seller);
            }

            // Update original invoice info display.
            if (data.invoice) {
                this.updateOriginalInvoiceInfo(data.invoice);
            }

            // Update refunds dropdown if available.
            if (data.refunds && data.refunds.length > 0) {
                this.updateRefundsDropdown(data.refunds);
            }

            // Recalculate document totals.
            this.recalculateDocument();
        },

        /**
         * Fetch receipt data via AJAX for receipt return (manual trigger).
         *
         * @param {number} receiptId Receipt ID.
         */
        fetchReceiptData: function(receiptId) {
            var self = this;
            var hasItems = $('#ihumbak-items-body .ihumbak-item-row').length > 0;
            var mode = 'replace';

            if (hasItems) {
                var confirmMsg = ihumbakInvoices.i18n.replaceItemsConfirm ||
                    'The form already contains items. Do you want to replace them with receipt data?';

                if (!confirm(confirmMsg)) {
                    mode = 'append';
                }
            }

            this._doFetchReceiptData(receiptId, function(data) {
                self.populateFromReceiptData(data, mode);
                self.showNotice('success', ihumbakInvoices.i18n.receiptDataLoaded || 'Receipt data loaded successfully.');
            });
        },

        /**
         * Fetch receipt data automatically (from pre-selected receipt).
         * Does not ask for confirmation, always replaces.
         *
         * @param {number} receiptId Receipt ID.
         */
        fetchReceiptDataAutomatic: function(receiptId) {
            var self = this;
            this._doFetchReceiptData(receiptId, function(data) {
                self.populateFromReceiptData(data, 'replace');
                self.showNotice('success', ihumbakInvoices.i18n.receiptDataLoaded || 'Receipt data loaded successfully.');
            });
        },

        /**
         * Internal method to fetch receipt data via AJAX.
         *
         * @param {number}   receiptId  Receipt ID.
         * @param {Function} onSuccess  Callback on successful fetch.
         * @private
         */
        _doFetchReceiptData: function(receiptId, onSuccess) {
            var self = this;
            var $button = $('#ihumbak-load-receipt');
            var $spinner = $('#ihumbak-load-status');

            // Show loading state.
            $button.prop('disabled', true);
            $spinner.addClass('is-active');

            $.ajax({
                url: ihumbakInvoices.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ihumbak_fetch_receipt_data',
                    nonce: ihumbakInvoices.nonce,
                    receipt_id: receiptId
                },
                success: function(response) {
                    if (response.success) {
                        onSuccess(response.data);
                    } else {
                        self.showNotice('error', response.data.message || ihumbakInvoices.i18n.error);
                        self.recalculateDocument();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    self.showNotice('error', ihumbakInvoices.i18n.error);
                    self.recalculateDocument();
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        },

        /**
         * Populate receipt return form from receipt data.
         *
         * @param {Object} data Receipt data.
         * @param {string} mode 'replace' or 'append'.
         */
        populateFromReceiptData: function(data, mode) {
            var self = this;

            if (mode === 'replace') {
                // Clear existing items.
                $('#ihumbak-items-body').empty();
                this.itemIndex = 0;
            }

            // Add items (with negative quantities for receipt return).
            if (data.items && data.items.length > 0) {
                data.items.forEach(function(item) {
                    // Negate quantity for receipt return.
                    item.quantity = -Math.abs(item.quantity || 1);
                    self.addItemRowWithData(item);
                });
            }

            // Populate buyer fields.
            if (data.buyer) {
                this.populateBuyerFields(data.buyer);
            }

            // Populate seller fields.
            if (data.seller) {
                this.populateSellerFields(data.seller);
            }

            // Update original receipt info display.
            if (data.receipt) {
                this.updateOriginalReceiptInfo(data.receipt);
            }

            // Update refunds dropdown if available.
            if (data.refunds && data.refunds.length > 0) {
                this.updateRefundsDropdown(data.refunds);
            }

            // Recalculate document totals.
            this.recalculateDocument();
        },

        /**
         * Update original receipt info display.
         *
         * @param {Object} receiptData Receipt data.
         */
        updateOriginalReceiptInfo: function(receiptData) {
            var $infoRow = $('#original-receipt-info');
            var $details = $('#original-receipt-details');

            if (!$infoRow.length) {
                // Create info row if it doesn't exist.
                var html = '<tr id="original-receipt-info">' +
                    '<th>' + (ihumbakInvoices.i18n.originalReceipt || 'Original Receipt') + '</th>' +
                    '<td><div id="original-receipt-details"></div></td>' +
                    '</tr>';
                $('#corrected_document_id').closest('tr').after(html);
                $details = $('#original-receipt-details');
            }

            $details.html(
                '<strong>' + this.escapeHtml(receiptData.document_number) + '</strong><br>' +
                (ihumbakInvoices.i18n.date || 'Date') + ': ' + this.escapeHtml(receiptData.issue_date)
            );
        },

        /**
         * Populate seller fields.
         *
         * Seller uses simplified format with just name and details.
         *
         * @param {Object} sellerData Seller data.
         */
        populateSellerFields: function(sellerData) {
            $('#seller_name').val(sellerData.name || '');
            $('#seller_details').val(sellerData.details || '');
        },

        /**
         * Update original invoice info display.
         *
         * @param {Object} invoiceData Invoice data.
         */
        updateOriginalInvoiceInfo: function(invoiceData) {
            var $infoRow = $('#original-invoice-info');
            var $details = $('#original-invoice-details');

            if (!$infoRow.length) {
                // Create info row if it doesn't exist.
                var html = '<tr id="original-invoice-info">' +
                    '<th>' + (ihumbakInvoices.i18n.originalInvoice || 'Original Invoice') + '</th>' +
                    '<td><div id="original-invoice-details"></div></td>' +
                    '</tr>';
                $('#corrected_document_id').closest('tr').after(html);
                $details = $('#original-invoice-details');
            }

            $details.html(
                '<strong>' + this.escapeHtml(invoiceData.document_number) + '</strong><br>' +
                (ihumbakInvoices.i18n.date || 'Date') + ': ' + this.escapeHtml(invoiceData.issue_date)
            );
        },

        /**
         * Update refunds dropdown with available refunds.
         *
         * @param {Array} refunds Array of refund data.
         */
        updateRefundsDropdown: function(refunds) {
            var $row = $('#refund-selection-row');
            var $select = $('#refund_id');

            if (!$row.length && refunds.length > 0) {
                // Create refund row if it doesn't exist.
                var html = '<tr id="refund-selection-row">' +
                    '<th><label for="refund_id">' + (ihumbakInvoices.i18n.linkToRefund || 'Link to WC Refund (Optional)') + '</label></th>' +
                    '<td>' +
                    '<select id="refund_id" name="refund_id"></select> ' +
                    '<button type="button" id="ihumbak-load-refund" class="button">' +
                    (ihumbakInvoices.i18n.applyRefundData || 'Apply Refund Data') +
                    '</button>' +
                    '</td>' +
                    '</tr>';
                $('#correction_reason').closest('tr').after(html);
                $select = $('#refund_id');

                // Bind click handler for new button.
                var self = this;
                $('#ihumbak-load-refund').on('click', function(e) {
                    e.preventDefault();
                    var refundId = parseInt($select.val(), 10);
                    if (refundId > 0) {
                        self.fetchRefundData(refundId);
                    }
                });
            }

            // Populate options.
            $select.empty();
            $select.append('<option value="">' + (ihumbakInvoices.i18n.noRefund || '-- No Refund --') + '</option>');

            refunds.forEach(function(refund) {
                var label = '#' + refund.id + ' - ' + parseFloat(refund.amount).toFixed(2) + ' - ' + refund.date;
                if (refund.reason) {
                    label += ' (' + refund.reason.substring(0, 30) + ')';
                }
                $select.append('<option value="' + refund.id + '">' + label + '</option>');
            });
        },

        /**
         * Fetch refund data via AJAX.
         *
         * @param {number} refundId Refund ID.
         */
        fetchRefundData: function(refundId) {
            var self = this;
            var $button = $('#ihumbak-load-refund');

            $button.prop('disabled', true);

            $.ajax({
                url: ihumbakInvoices.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ihumbak_fetch_refund_data',
                    nonce: ihumbakInvoices.nonce,
                    refund_id: refundId
                },
                success: function(response) {
                    if (response.success) {
                        self.populateFromRefundData(response.data);
                        self.showNotice('success', ihumbakInvoices.i18n.refundDataLoaded || 'Refund data applied successfully.');
                    } else {
                        self.showNotice('error', response.data.message || ihumbakInvoices.i18n.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    self.showNotice('error', ihumbakInvoices.i18n.error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Populate credit note from refund data.
         *
         * @param {Object} data Refund data.
         */
        populateFromRefundData: function(data) {
            var self = this;

            // Set correction reason from refund reason.
            if (data.reason) {
                $('#correction_reason').val(data.reason);
            }

            // Replace items with refund items.
            if (data.items && data.items.length > 0) {
                $('#ihumbak-items-body').empty();
                this.itemIndex = 0;

                data.items.forEach(function(item) {
                    self.addItemRowWithData({
                        name: item.name,
                        quantity: -Math.abs(item.quantity || 1),
                        unit_price_net: item.unit_price_net || 0,
                        tax_rate: 23 // Default, will be recalculated.
                    });
                });
            }

            // Recalculate.
            this.recalculateDocument();
        },

        /**
         * Escape HTML to prevent XSS.
         *
         * @param {string} text Text to escape.
         * @return {string} Escaped text.
         */
        escapeHtml: function(text) {
            if (!text) {
                return '';
            }
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
