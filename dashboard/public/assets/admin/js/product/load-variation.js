window.ignoreChoiceChange = false;
window.variantValuesStore = window.variantValuesStore || {};
let renderDebounceTimer = null;

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
function collectCurrentVariantValues() {
    $('#variant_combination tr').each(function () {
        const $row = $(this);
        const variantType = getRowVariantType($row);
        if (!variantType) return;

        const purchaseInput = $row.find('input[name^="purchase_price_"]');
        const priceInput = $row.find('input[name^="price_"]');
        const stockInput = $row.find('input[name^="stock_"]');

        if (!window.variantValuesStore[variantType]) {
            window.variantValuesStore[variantType] = {};
        }

        if (purchaseInput.length && purchaseInput.val() !== '') {
            window.variantValuesStore[variantType].purchase_price = purchaseInput.val();
        }
        if (priceInput.length && priceInput.val() !== '') {
            window.variantValuesStore[variantType].price = priceInput.val();
        }
        if (stockInput.length && stockInput.val() !== '') {
            window.variantValuesStore[variantType].stock = stockInput.val();
        }
    });
}

// Track input changes in real time
$(document).on('input change', '#variant_combination input', function () {
    const $this = $(this);
    const $row = $this.closest('tr');
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
    }
});

// Best matching algorithm for finding previously entered data for any combination
function findBestVariantData(variantType) {
    if (!variantType) return null;
    variantType = String(variantType).trim();

    // 1. Exact match
    if (window.variantValuesStore[variantType]) {
        return window.variantValuesStore[variantType];
    }

    // 2. Prefix / segment match
    const parts = variantType.split('-');

    // Check progressively smaller prefixes: e.g. "4-128", then "4"
    for (let len = parts.length - 1; len >= 1; len--) {
        const subKey = parts.slice(0, len).join('-');
        if (window.variantValuesStore[subKey]) {
            return window.variantValuesStore[subKey];
        }
    }

    // Check individual segments (e.g. "4")
    for (let i = 0; i < parts.length; i++) {
        const part = parts[i].trim();
        if (part && window.variantValuesStore[part]) {
            return window.variantValuesStore[part];
        }
    }

    // 3. Sibling match (any key that shares the leading segment)
    const lead = parts[0].trim();
    for (const key in window.variantValuesStore) {
        if (key.startsWith(lead + '-') || key === lead) {
            return window.variantValuesStore[key];
        }
    }

    // 4. Reverse match: if looking for "4" and store only has "4-128"
    for (const key in window.variantValuesStore) {
        if (key.startsWith(variantType + '-')) {
            return window.variantValuesStore[key];
        }
    }

    return null;
}

// Compute active options array from colors & choice rows
function getActiveOptions() {
    const options = [];

    // 1. Color variants (first dimension)
    const colors = [];
    $('#colorCardsContainer .color-variant-card').each(function () {
        const val = ($(this).find('input[name^="color_names"]').val() || '').trim().replace(/\s+/g, '');
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
                .map(function(s) { return s.trim().replace(/\s+/g, ''); })
                .filter(function(s) { return s !== ''; });
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

// Pure client-side instant combination renderer with Virtual DOM-style reconciliation
function renderCombinationsClientSide() {
    collectCurrentVariantValues();

    const options = getActiveOptions();
    const combinations = computeCombinations(options);

    if (combinations.length === 0) {
        $('#variant_combination').empty();
        $('#quantity').show();
        update_qty();
        return;
    }

    $('#quantity').hide();

    const neededTypes = combinations.map(function(combo) {
        return combo.filter(function(s) { return s !== ''; }).join('-');
    }).filter(function(s) { return s !== ''; });

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
                        <td class="text-center"><label class="control-label font-weight-bold">${labelVariant}</label></td>
                        <td class="text-center"><label class="control-label font-weight-bold">${labelPurchasePrice}</label></td>
                        <td class="text-center"><label class="control-label font-weight-bold">${labelPrice}</label></td>
                        <td class="text-center"><label class="control-label font-weight-bold">${labelStock}</label></td>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        `;
        $('#variant_combination').html(tableHtml);
        $table = $('#variant_combination').find('table');
    }

    const $tbody = $table.find('tbody');

    // 1. Remove obsolete rows that are no longer in neededTypes
    $tbody.find('tr').each(function () {
        const rowType = getRowVariantType($(this));
        if (!neededTypes.includes(rowType)) {
            $(this).remove();
        }
    });

    const defaultPrice = $('#price').val() || '0';
    const defaultPurchasePrice = $('#purchase_price').val() || '0';

    // 2. Add or update rows in order without destroying existing focused inputs
    neededTypes.forEach(function (variantType) {
        let $existingRow = null;
        $tbody.find('tr').each(function() {
            if (getRowVariantType($(this)) === variantType) {
                $existingRow = $(this);
                return false;
            }
        });

        if ($existingRow && $existingRow.length > 0) {
            // Existing row: ensure data attribute is present and leave it alone!
            $existingRow.attr('data-variant-type', variantType);
        } else {
            // New row: compute inherited values
            const saved = findBestVariantData(variantType);
            let valPurchase = (saved && saved.purchase_price !== undefined && saved.purchase_price !== '') ? saved.purchase_price : defaultPurchasePrice;
            let valPrice = (saved && saved.price !== undefined && saved.price !== '') ? saved.price : defaultPrice;
            let valStock = (saved && saved.stock !== undefined && saved.stock !== '') ? saved.stock : '0';

            const newRowHtml = `
                <tr data-variant-type="${variantType}">
                    <td>
                        <label class="control-label font-weight-bold">${variantType}</label>
                    </td>
                    <td>
                        <input type="number" name="purchase_price_${variantType}" data-variant-type="${variantType}" data-field="purchase_price" value="${valPurchase}" min="0" step="any" class="form-control variant-input" required>
                    </td>
                    <td>
                        <input type="number" name="price_${variantType}" data-variant-type="${variantType}" data-field="price" value="${valPrice}" min="0" step="any" class="form-control variant-input" required>
                    </td>
                    <td>
                        <input type="number" name="stock_${variantType}" data-variant-type="${variantType}" data-field="stock" value="${valStock}" min="0" max="1000000" onkeyup="update_qty()" class="form-control variant-input" required>
                    </td>
                </tr>
            `;
            $tbody.append(newRowHtml);

            // Record into store so sibling combinations can also inherit
            if (!window.variantValuesStore[variantType]) {
                window.variantValuesStore[variantType] = {};
            }
            window.variantValuesStore[variantType].purchase_price = valPurchase;
            window.variantValuesStore[variantType].price = valPrice;
            window.variantValuesStore[variantType].stock = valStock;
        }
    });

    update_qty();
}

function combination_update() {
    clearTimeout(renderDebounceTimer);
    renderDebounceTimer = setTimeout(function () {
        renderCombinationsClientSide();
    }, 60);
}

// When choice attributes change in select dropdown
$('#choice_attributes').on('change', function () {
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
        $('#variant_combination').empty();
        $('#quantity').show();
        update_qty();
    } else {
        collectCurrentVariantValues();
        if (removedAny) {
            combination_update();
        }
    }
});

function add_more_customer_choice_option(i, name) {
    let n = name.split(' ').join('');
    let $newRow = $(
        '<div class="row g-1 mb-2"><div class="col-md-3 col-sm-4"><input type="hidden" name="choice_no[]" value="' +
        i + '"><input type="text" class="form-control" name="choice[]" value="' + n +
        '" placeholder="Choice Title" readonly></div><div class="col-lg-9 col-sm-8"><input type="text" class="form-control" name="choice_options_' +
        i +
        '[]" placeholder="Enter choice values" data-role="tagsinput"></div></div>'
    );
    $('#customer_choice_options').append($newRow);
    $newRow.find('input[data-role=tagsinput]').tagsinput();
}

// When tags are added or removed
$(document).on('change', '[name^="choice_options_"]', function () {
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
            total_qty += parseInt($(this).val()) || 0;
        });

        if (qty_elements.length > 0) {
            $('input[name="total_stock"]').attr("readonly", true).val(total_qty);
        } else {
            $('input[name="total_stock"]').attr("readonly", false).val('');
        }
    }, 100);
}

// Pre-cache existing values on page load (especially for edit page)
$(document).ready(function () {
    collectCurrentVariantValues();
});
