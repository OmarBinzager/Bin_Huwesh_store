window.ignoreChoiceChange = false;
window.variantValuesStore = window.variantValuesStore || {};
let renderDebounceTimer = null;

// Escape HTML special characters for safe attribute/text rendering
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Helper to reliably get variantType string from a table row
function getRowVariantType($row) {
    if (!$row || !$row.length) return '';
    const attrVal = $row.attr('data-variant-type');
    if (attrVal) return String(attrVal).trim();

    const priceInput = $row.find('input[name^="price_"]');
    if (priceInput.length) {
        const inputAttr = priceInput.attr('data-variant-type');
        if (inputAttr) return String(inputAttr).trim();
        const name = priceInput.attr('name') || '';
        if (name.startsWith('price_')) {
            return name.substring('price_'.length).trim();
        }
    }
    const anyInput = $row.find('input[name^="purchase_price_"], input[name^="stock_"]');
    if (anyInput.length) {
        const inputAttr = anyInput.attr('data-variant-type');
        if (inputAttr) return String(inputAttr).trim();
    }
    return '';
}

// Function to collect current values from the DOM into the store
function collectCurrentVariantValues(markAsUserData) {
    $('#variant_combination tbody tr').each(function () {
        const $row = $(this);
        const variantType = getRowVariantType($row);
        if (!variantType) return;

        const purchaseInput = $row.find('input[name^="purchase_price_"]');
        const priceInput = $row.find('input[name^="price_"]');
        const stockInput = $row.find('input[name^="stock_"]');

        const existing = window.variantValuesStore[variantType] || {};
        const isUserRow = Boolean(markAsUserData || existing.hasUserData || $row.attr('data-user-edited') === '1');

        const purchaseVal = purchaseInput.length ? purchaseInput.val() : '';
        const priceVal = priceInput.length ? priceInput.val() : '';
        const stockVal = stockInput.length ? stockInput.val() : '';

        window.variantValuesStore[variantType] = {
            purchase_price: purchaseVal !== '' ? purchaseVal : (existing.purchase_price ?? ''),
            price: priceVal !== '' ? priceVal : (existing.price ?? ''),
            stock: stockVal !== '' ? stockVal : (existing.stock ?? ''),
            hasUserData: isUserRow
        };
    });
}

// Track input changes in real time
$(document).on('input change', '#variant_combination .variant-input', function () {
    const $this = $(this);
    const $row = $this.closest('tr');
    $row.attr('data-user-edited', '1');
    $row.find('.variant-new-badge').remove();

    let variantType = getRowVariantType($row);
    const name = $this.attr('name') || '';

    let field = '';
    if (name.startsWith('purchase_price_')) {
        field = 'purchase_price';
        if (!variantType) variantType = name.substring('purchase_price_'.length).trim();
    } else if (name.startsWith('price_')) {
        field = 'price';
        if (!variantType) variantType = name.substring('price_'.length).trim();
    } else if (name.startsWith('stock_')) {
        field = 'stock';
        if (!variantType) variantType = name.substring('stock_'.length).trim();
    }

    if (field && variantType) {
        if (!window.variantValuesStore[variantType]) {
            window.variantValuesStore[variantType] = {};
        }
        window.variantValuesStore[variantType][field] = $this.val();
        window.variantValuesStore[variantType].hasUserData = true;
        updateVariantToolbarStats();
    }
});

// Select all text on focus for fast numeric entry
$(document).on('focus', '#variant_combination .variant-input', function () {
    this.select();
});

// Smart token-based matching for finding previously entered data for any combination
function findBestVariantMatch(variantType) {
    if (!variantType) return null;
    variantType = String(variantType).trim();

    // 1. Exact match (prefer entries with user data)
    const exact = window.variantValuesStore[variantType];
    if (exact && exact.hasUserData) {
        return { key: variantType, data: exact, matchType: 'exact' };
    }

    const targetTokens = variantType.split('-').map(s => s.trim()).filter(Boolean);
    if (targetTokens.length === 0) return null;

    let bestCandidate = null;
    let bestScore = -1;

    for (const key in window.variantValuesStore) {
        const entry = window.variantValuesStore[key];
        if (!entry || !entry.hasUserData) continue;

        const keyTokens = key.split('-').map(s => s.trim()).filter(Boolean);
        if (keyTokens.length === 0) continue;

        let overlap = 0;
        for (let i = 0; i < keyTokens.length; i++) {
            if (targetTokens.includes(keyTokens[i])) {
                overlap++;
            }
        }
        if (overlap === 0) continue;

        const isKeySubsetOfTarget = overlap === keyTokens.length;
        const isTargetSubsetOfKey = overlap === targetTokens.length;

        // Scoring:
        // - Expanding an existing variant (old tokens are a subset of new tokens): highest priority
        // - Shrinking an existing variant (new tokens are a subset of old tokens): second priority
        // - Partial token overlap (e.g. renamed one attribute/color): third priority
        let score = 0;
        let matchType = 'partial';
        if (isKeySubsetOfTarget) {
            score = 10000 + keyTokens.length * 100 - Math.abs(targetTokens.length - keyTokens.length);
            matchType = 'superset';
        } else if (isTargetSubsetOfKey) {
            score = 5000 + targetTokens.length * 100 - Math.abs(keyTokens.length - targetTokens.length);
            matchType = 'subset';
        } else {
            score = overlap * 100 - Math.abs(targetTokens.length - keyTokens.length);
            matchType = 'partial';
        }

        if (score > bestScore) {
            bestScore = score;
            bestCandidate = { key: key, data: entry, matchType: matchType };
        }
    }

    if (bestCandidate) {
        return bestCandidate;
    }

    // Fallback to exact non-user-data entry if present
    if (exact) {
        return { key: variantType, data: exact, matchType: 'exact' };
    }

    return null;
}

// Compute active options array from colors & choice rows
function getActiveOptions() {
    const options = [];

    // 1. Color variants (first dimension)
    const colors = [];
    $('#colorCardsContainer .color-variant-card').each(function () {
        const val = ($(this).find('input[name^="color_names"]').val() || '').trim().replace(/\s+/g, ' ');
        if (val !== '' && !colors.includes(val)) {
            colors.push(val);
        }
    });
    if (colors.length > 0) {
        options.push(colors);
    }

    // 2. Attribute Choice options
    $('#customer_choice_options input[name="choice_no[]"]').each(function () {
        const no = $(this).val();
        const $tagsInput = $('#customer_choice_options input[name="choice_options_' + no + '[]"]');
        if ($tagsInput.length) {
            const raw = $tagsInput.val() || '';
            const items = raw.split(',')
                .map(function (s) { return s.trim().replace(/\s+/g, ' '); })
                .filter(function (s) { return s !== ''; });
            if (items.length > 0) {
                options.push(items);
            }
        }
    });
    return options;
}

// Compute combinations Cartesian product
function computeCombinations(options) {
    if (!options || options.length === 0) return [];
    let result = [[]];
    for (let i = 0; i < options.length; i++) {
        const currentVals = options[i];
        const next = [];
        for (let j = 0; j < result.length; j++) {
            for (let k = 0; k < currentVals.length; k++) {
                next.push(result[j].concat(currentVals[k]));
            }
        }
        result = next;
    }
    return result;
}

// Ensure the UX toolbar and scrollable table wrapper exist around #variant_combination table
function ensureVariantUXShell() {
    const $container = $('#variant_combination');
    if (!$container.length) return;

    const $table = $container.find('table');
    if (!$table.length) return;

    if (!$container.find('.variant-ux-toolbar').length) {
        const toolbarHtml = `
            <div class="variant-ux-toolbar border rounded p-3 mb-2 bg-light" style="border-color: #E5E7EB !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="font-weight-bold text-dark" style="font-size: 14px;">
                            <i class="tio-table mr-1"></i> جدول بيانات المتغيرات
                        </span>
                        <span class="badge badge-soft-primary font-size-12" id="variantCountBadge">0 متغير</span>
                        <span class="badge badge-soft-success font-size-12" id="variantTotalStockBadge">إجمالي المخزون: 0</span>
                    </div>
                    <div class="d-flex align-items-center gap-2" style="min-width: 220px; max-width: 300px; flex: 1;">
                        <input type="text" id="variantSearchInput" class="form-control form-control-sm" placeholder="ابحث باسم المتغير..." autocomplete="off">
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-end gap-2">
                    <div style="width: 145px;">
                        <label class="font-size-11 text-muted mb-1 d-block">سعر الشراء للكل</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="any" min="0" id="bulkPurchasePriceInput" class="form-control" placeholder="0">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary bulk-apply-btn" data-field="purchase_price" title="تطبيق على الصفوف">تطبيق</button>
                            </div>
                        </div>
                    </div>
                    <div style="width: 145px;">
                        <label class="font-size-11 text-muted mb-1 d-block">سعر البيع للكل</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="any" min="0" id="bulkPriceInput" class="form-control" placeholder="0">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary bulk-apply-btn" data-field="price" title="تطبيق على الصفوف">تطبيق</button>
                            </div>
                        </div>
                    </div>
                    <div style="width: 145px;">
                        <label class="font-size-11 text-muted mb-1 d-block">المخزون للكل</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="1" min="0" max="1000000" id="bulkStockInput" class="form-control" placeholder="0">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary bulk-apply-btn" data-field="stock" title="تطبيق على الصفوف">تطبيق</button>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2 ml-auto">
                        <div class="custom-control custom-checkbox mr-2">
                            <input type="checkbox" class="custom-control-input" id="bulkOnlyEmptyCheckbox">
                            <label class="custom-control-label font-size-12 text-muted" for="bulkOnlyEmptyCheckbox">تطبيق على الفارغة/الصفرية فقط</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="copyFirstRowPricesBtn" title="نسخ أسعار أول صف لبقية الصفوف">
                            <i class="tio-copy mr-1"></i> نسخ أسعار أول صف
                        </button>
                    </div>
                </div>
            </div>
        `;
        $table.before(toolbarHtml);
    }

    if (!$table.parent().hasClass('variant-table-scroll-wrap')) {
        $table.wrap('<div class="variant-table-scroll-wrap table-responsive border rounded" style="max-height: 480px; overflow-y: auto; border-color: #E5E7EB !important;"></div>');
        $table.addClass('mb-0');
        $table.find('thead th, thead td').css({
            position: 'sticky',
            top: 0,
            backgroundColor: '#F8FAFD',
            zIndex: 2
        });
    }

    updateVariantToolbarStats();
}

function updateVariantToolbarStats() {
    const $rows = $('#variant_combination tbody tr');
    const count = $rows.length;
    let totalStock = 0;
    $rows.find('input[name^="stock_"]').each(function () {
        totalStock += parseInt($(this).val(), 10) || 0;
    });
    $('#variantCountBadge').text(count + ' متغير');
    $('#variantTotalStockBadge').text('إجمالي المخزون: ' + totalStock);
}

// Search/filter inside variants table without removing inputs from form submission
$(document).on('input', '#variantSearchInput', function () {
    const query = ($(this).val() || '').trim().toLowerCase();
    $('#variant_combination tbody tr').each(function () {
        const type = (getRowVariantType($(this)) || '').toLowerCase();
        if (!query || type.includes(query)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});

// Bulk apply price / purchase_price / stock
$(document).on('click', '.bulk-apply-btn', function () {
    const field = $(this).data('field');
    const onlyEmpty = $('#bulkOnlyEmptyCheckbox').is(':checked');
    let sourceVal = '';

    if (field === 'purchase_price') sourceVal = $('#bulkPurchasePriceInput').val();
    else if (field === 'price') sourceVal = $('#bulkPriceInput').val();
    else if (field === 'stock') sourceVal = $('#bulkStockInput').val();

    if (sourceVal === '' || sourceVal === null || sourceVal === undefined) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('يرجى إدخال قيمة أولاً للتطبيق الجماعي');
        }
        return;
    }

    $('#variant_combination tbody tr:visible').each(function () {
        const $row = $(this);
        const $input = $row.find(`input[name^="${field}_"]`);
        if (!$input.length) return;

        const currentVal = $input.val();
        if (onlyEmpty && currentVal !== '' && parseFloat(currentVal) !== 0) {
            return;
        }

        $input.val(sourceVal).trigger('change');
    });

    update_qty();
    updateVariantToolbarStats();
});

// Copy first visible row's purchase_price and price to all visible rows
$(document).on('click', '#copyFirstRowPricesBtn', function () {
    const $firstRow = $('#variant_combination tbody tr:visible').first();
    if (!$firstRow.length) return;

    const firstPurchase = $firstRow.find('input[name^="purchase_price_"]').val();
    const firstPrice = $firstRow.find('input[name^="price_"]').val();
    const onlyEmpty = $('#bulkOnlyEmptyCheckbox').is(':checked');

    $('#variant_combination tbody tr:visible').each(function (idx) {
        if (idx === 0) return;
        const $row = $(this);
        const $pInput = $row.find('input[name^="purchase_price_"]');
        const $prInput = $row.find('input[name^="price_"]');

        if ($pInput.length && (!onlyEmpty || $pInput.val() === '' || parseFloat($pInput.val()) === 0)) {
            $pInput.val(firstPurchase).trigger('change');
        }
        if ($prInput.length && (!onlyEmpty || $prInput.val() === '' || parseFloat($prInput.val()) === 0)) {
            $prInput.val(firstPrice).trigger('change');
        }
    });

    updateVariantToolbarStats();
});

// Pure client-side instant combination renderer preserving data and keeping existing data in first rows
function renderCombinationsClientSide() {
    collectCurrentVariantValues(false);

    const options = getActiveOptions();
    const combinations = computeCombinations(options);

    if (combinations.length === 0) {
        $('#variant_combination').empty();
        $('#quantity').show();
        update_qty();
        return;
    }

    $('#quantity').hide();

    const neededTypes = combinations.map(function (combo) {
        return combo.filter(function (s) { return s !== ''; }).join('-');
    }).filter(function (s) { return s !== ''; });

    if (neededTypes.length === 0) {
        $('#variant_combination').empty();
        $('#quantity').show();
        update_qty();
        return;
    }

    // Ensure table structure exists
    let $table = $('#variant_combination').find('table');
    if (!$table.length) {
        const labelVariant = $('#variant_combination').data('label-variant') || 'Variant';
        const labelPurchasePrice = $('#variant_combination').data('label-purchase-price') || 'Purchase Price';
        const labelPrice = $('#variant_combination').data('label-price') || 'Variant Price';
        const labelStock = $('#variant_combination').data('label-stock') || 'Variant Stock';

        const tableHtml = `
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <td class="text-center"><label class="control-label font-weight-bold mb-0">${escapeHtml(labelVariant)}</label></td>
                        <td class="text-center"><label class="control-label font-weight-bold mb-0">${escapeHtml(labelPurchasePrice)}</label></td>
                        <td class="text-center"><label class="control-label font-weight-bold mb-0">${escapeHtml(labelPrice)}</label></td>
                        <td class="text-center"><label class="control-label font-weight-bold mb-0">${escapeHtml(labelStock)}</label></td>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        `;
        $('#variant_combination').html(tableHtml);
        $table = $('#variant_combination').find('table');
    }

    ensureVariantUXShell();

    const $tbody = $table.find('tbody');
    const existingRowMap = {};

    // Map existing DOM rows and remove obsolete ones
    $tbody.find('tr').each(function () {
        const rowType = getRowVariantType($(this));
        if (!neededTypes.includes(rowType)) {
            $(this).remove();
        } else {
            existingRowMap[rowType] = $(this);
        }
    });

    const defaultPrice = $('#price').val() || '0';
    const defaultPurchasePrice = $('#purchase_price').val() || '0';

    // Track which source keys have already transferred their stock to a primary expanded row
    const stockClaimedFromSource = {};

    // Partition neededTypes so rows with existing/preserved data appear in the FIRST rows,
    // and newly added combinations appear after them.
    const existingOrPrimaryRows = [];
    const newlyAddedRows = [];

    neededTypes.forEach(function (variantType) {
        if (existingRowMap[variantType]) {
            existingOrPrimaryRows.push({ type: variantType, $row: existingRowMap[variantType], isExisting: true });
            return;
        }

        const match = findBestVariantMatch(variantType);
        const saved = match ? match.data : null;
        const sourceKey = match ? match.key : null;
        const isFirstSplitFromSource = Boolean(sourceKey && !stockClaimedFromSource[sourceKey]);

        if (sourceKey && saved && saved.hasUserData) {
            stockClaimedFromSource[sourceKey] = true;
        }

        let valPurchase = (saved && saved.purchase_price !== undefined && saved.purchase_price !== '') ? saved.purchase_price : defaultPurchasePrice;
        let valPrice = (saved && saved.price !== undefined && saved.price !== '') ? saved.price : defaultPrice;
        let valStock = '0';

        if (saved && saved.stock !== undefined && saved.stock !== '') {
            if (match.matchType === 'exact' || isFirstSplitFromSource) {
                valStock = saved.stock;
            }
        }

        const isPreservedPrimary = Boolean(saved && saved.hasUserData && (match.matchType === 'exact' || isFirstSplitFromSource));
        const safeType = escapeHtml(variantType);
        const badgeHtml = isPreservedPrimary ? '' : ' <span class="badge badge-soft-info variant-new-badge ml-1" style="font-size: 10px;">جديد</span>';

        const $newRow = $(`
            <tr data-variant-type="${safeType}" data-user-edited="${isPreservedPrimary ? '1' : '0'}">
                <td>
                    <label class="control-label font-weight-bold mb-0">${safeType}${badgeHtml}</label>
                </td>
                <td>
                    <input type="number" name="purchase_price_${safeType}" data-variant-type="${safeType}" data-field="purchase_price" value="${escapeHtml(valPurchase)}" min="0" step="any" class="form-control variant-input" required>
                </td>
                <td>
                    <input type="number" name="price_${safeType}" data-variant-type="${safeType}" data-field="price" value="${escapeHtml(valPrice)}" min="0" step="any" class="form-control variant-input" required>
                </td>
                <td>
                    <input type="number" name="stock_${safeType}" data-variant-type="${safeType}" data-field="stock" value="${escapeHtml(valStock)}" min="0" max="1000000" onkeyup="update_qty()" class="form-control variant-input" required>
                </td>
            </tr>
        `);

        // Store values; only mark hasUserData if it came from preserved user data
        window.variantValuesStore[variantType] = {
            purchase_price: valPurchase,
            price: valPrice,
            stock: valStock,
            hasUserData: isPreservedPrimary
        };

        if (isPreservedPrimary) {
            existingOrPrimaryRows.push({ type: variantType, $row: $newRow, isExisting: false });
        } else {
            newlyAddedRows.push({ type: variantType, $row: $newRow, isExisting: false });
        }
    });

    // Reorder tbody so preserved/existing data stays in the first rows and new rows follow
    const orderedRows = existingOrPrimaryRows.concat(newlyAddedRows);
    orderedRows.forEach(function (item) {
        item.$row.attr('data-variant-type', item.type);
        $tbody.append(item.$row);
    });

    // Re-apply active search filter if any
    const activeSearch = ($('#variantSearchInput').val() || '').trim().toLowerCase();
    if (activeSearch) {
        $tbody.find('tr').each(function () {
            const type = (getRowVariantType($(this)) || '').toLowerCase();
            $(this).toggle(type.includes(activeSearch));
        });
    }

    update_qty();
    updateVariantToolbarStats();
}

function combination_update() {
    clearTimeout(renderDebounceTimer);
    renderDebounceTimer = setTimeout(function () {
        renderCombinationsClientSide();
    }, 60);
}

// When choice attributes change in select dropdown
$('#choice_attributes').on('change', function () {
    collectCurrentVariantValues(false);

    const selectedOptions = $("#choice_attributes option:selected");
    const selectedIds = [];

    selectedOptions.each(function () {
        selectedIds.push(String($(this).val()));
    });

    let removedAny = false;
    // Remove any rows for attributes that were deselected
    $('#customer_choice_options input[name="choice_no[]"]').each(function () {
        const choiceNo = String($(this).val());
        if (!selectedIds.includes(choiceNo)) {
            $(this).closest('.row').remove();
            removedAny = true;
        }
    });

    // Add newly selected attributes without wiping existing ones
    selectedOptions.each(function () {
        const id = String($(this).val());
        const name = $(this).text();
        const existingRow = $('#customer_choice_options input[name="choice_no[]"][value="' + id + '"]');
        if (existingRow.length === 0) {
            add_more_customer_choice_option(id, name);
        }
    });

    if (selectedIds.length === 0) {
        $('#customer_choice_options').empty();
        // Do NOT wipe #variant_combination blindly if color variants still exist
        combination_update();
    } else if (removedAny) {
        combination_update();
    }
});

function add_more_customer_choice_option(i, name) {
    let n = String(name || '').trim().replace(/\s+/g, ' ');
    let $newRow = $(
        '<div class="row g-1 mb-2"><div class="col-md-3 col-sm-4"><input type="hidden" name="choice_no[]" value="' +
        escapeHtml(i) + '"><input type="text" class="form-control" name="choice[]" value="' + escapeHtml(n) +
        '" placeholder="Choice Title" readonly></div><div class="col-lg-9 col-sm-8"><input type="text" class="form-control" name="choice_options_' +
        escapeHtml(i) +
        '[]" placeholder="Enter choice values" data-role="tagsinput"></div></div>'
    );
    $('#customer_choice_options').append($newRow);
    $newRow.find('input[data-role=tagsinput]').tagsinput();
}

// When tags are added or removed
$(document).on('change itemAdded itemRemoved', '[name^="choice_options_"]', function () {
    if (window.ignoreChoiceChange) return;
    combination_update();
});

// When color variant names change
$(document).on('input change', '#colorCardsContainer input[name^="color_names"]', function () {
    combination_update();
});

// Update total quantity
function update_qty() {
    setTimeout(function () {
        let total_qty = 0;
        const qty_elements = $('input[name^="stock_"]');
        qty_elements.each(function () {
            total_qty += parseInt($(this).val(), 10) || 0;
        });

        if (qty_elements.length > 0) {
            $('input[name="total_stock"]').attr("readonly", true).val(total_qty);
        } else {
            $('input[name="total_stock"]').attr("readonly", false);
        }
        updateVariantToolbarStats();
    }, 100);
}

// Pre-cache existing values and initialize UX shell on page load (especially for edit page)
$(document).ready(function () {
    collectCurrentVariantValues(true);
    ensureVariantUXShell();
});
