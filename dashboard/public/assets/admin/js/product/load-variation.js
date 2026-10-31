window.ignoreChoiceChange = false;

$('#choice_attributes').on('change', function () {
    const selectedOptions = $("#choice_attributes option:selected");
    const selectedIds = [];
    
    selectedOptions.each(function () {
        selectedIds.push(String($(this).val()));
    });

    // Remove any rows for attributes that were deselected
    $('#customer_choice_options input[name="choice_no[]"]').each(function () {
        const choiceNo = String($(this).val());
        if (!selectedIds.includes(choiceNo)) {
            $(this).closest('.row').remove();
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
    } else {
        combination_update();
        update_qty();
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

function combination_update() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Capture currently entered variant values to preserve them across updates
    const existingPrices = {};
    const existingPurchasePrices = {};
    const existingStocks = {};
    $('#variant_combination input[name^="price_"]').each(function() {
        existingPrices[$(this).attr('name')] = $(this).val();
    });
    $('#variant_combination input[name^="purchase_price_"]').each(function() {
        existingPurchasePrices[$(this).attr('name')] = $(this).val();
    });
    $('#variant_combination input[name^="stock_"]').each(function() {
        existingStocks[$(this).attr('name')] = $(this).val();
    });

    $.ajax({
        type: "POST",
        url: $('.data-to-js').data('variant-combination-route'),
        data: $('#product_form').serialize(),
        success: function(data) {
            $('#variant_combination').html(data.view);

            // Re-apply preserved values
            $.each(existingPrices, function(name, val) {
                if (val !== undefined && val !== '') {
                    $('#variant_combination input[name="' + name + '"]').val(val);
                }
            });
            $.each(existingPurchasePrices, function(name, val) {
                if (val !== undefined && val !== '') {
                    $('#variant_combination input[name="' + name + '"]').val(val);
                }
            });
            $.each(existingStocks, function(name, val) {
                if (val !== undefined && val !== '') {
                    $('#variant_combination input[name="' + name + '"]').val(val);
                }
            });

            if ($('#variant_combination').find('tbody tr').length > 0) {
                $('#quantity').hide();
            } else {
                $('#quantity').show();
            }
            update_qty();
        }
    });
}

$(document).on('change', '[name^="choice_options_"]', function () {
    if (window.ignoreChoiceChange) return;
    combination_update();
    update_qty();
});

function update_qty() {
    setTimeout(() => {
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
    }, 1000);
}
