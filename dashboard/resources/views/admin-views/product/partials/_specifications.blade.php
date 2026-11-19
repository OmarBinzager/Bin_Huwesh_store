@php
    $existingSpecs = [];
    if (isset($product) && !empty($product->specifications)) {
        $existingSpecs = is_string($product->specifications)
            ? (json_decode($product->specifications, true) ?? [])
            : (is_array($product->specifications) ? $product->specifications : []);
    }
@endphp

<div class="card mb-4 border shadow-xs" style="border-radius: 12px; border-color: #E5E7EB !important; overflow: hidden;">
    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-1 d-flex align-items-center gap-2" style="font-weight: 700; color: #171717;">
                <span class="rounded-circle d-inline-flex justify-content-center align-items-center text-white shadow-xs" style="width: 28px; height: 28px; background-color: #1F2937; font-size: 14px;">
                    <i class="tio-list-outlined"></i>
                </span>
                <span>{{ translate('مواصفات وتفاصيل المنتج الثابتة (Specifications)') }}</span>
            </h5>
            <small class="text-muted font-size-12">
                {{ translate('أضف مواصفات المنتج الوصفية (مثل: الخامة، الوارد، التصميم، إرشادات العناية) دون أن تؤثر على جدول المتغيرات أو الأسعار.') }}
            </small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addSpecRowBtn">
            <i class="tio-add"></i> {{ translate('إضافة خاصية') }}
        </button>
    </div>

    <div class="card-body p-3 p-md-4" style="background: #FAFAFA;">
        <!-- Quick Spec Presets -->
        <div class="mb-3 d-flex align-items-center flex-wrap gap-2">
            <span class="font-size-11 font-weight-bold text-muted mr-1">{{ translate('إضافة سريعة:') }}</span>
            @foreach(['نوع القماش / الخامة', 'الوارد / المنشأ', 'التصميم / القصة', 'الموسم', 'نوع الياقة', 'نوع الأكمام', 'إرشادات العناية', 'الضمان / الجودة'] as $preset)
                <button type="button" class="btn btn-xs btn-outline-secondary quick-spec-btn" data-label="{{ $preset }}">
                    + {{ $preset }}
                </button>
            @endforeach
        </div>

        <div id="productSpecsContainer" class="d-flex flex-column gap-2">
            @foreach($existingSpecs as $spec)
                @php
                    $sLabel = $spec['label'] ?? '';
                    $sValue = $spec['value'] ?? '';
                @endphp
                @if($sLabel !== '' || $sValue !== '')
                    <div class="row g-2 align-items-center spec-row bg-white p-2 border rounded mx-0">
                        <div class="col-md-4">
                            <input type="text" name="spec_keys[]" class="form-control form-control-sm spec-key-input" value="{{ $sLabel }}" placeholder="{{ translate('اسم الخاصية (مثال: نوع القماش)') }}">
                        </div>
                        <div class="col-md-7">
                            <input type="text" name="spec_values[]" class="form-control form-control-sm spec-val-input" value="{{ $sValue }}" placeholder="{{ translate('القيمة (مثال: قطن 100%)') }}">
                        </div>
                        <div class="col-md-1 text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-spec-row-btn" title="{{ translate('حذف') }}">
                                <i class="tio-delete-outlined"></i>
                            </button>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div id="noSpecsNotice" class="text-center py-3 text-muted bg-white border rounded {{ count($existingSpecs) > 0 ? 'd-none' : '' }}" style="border-style: dashed !important; border-color: #D1D5DB !important;">
            <small>{{ translate('لا توجد مواصفات مضافة بعد. اضغط على "إضافة خاصية" أو اختر من الإضافة السريعة أعلاه.') }}</small>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function toggleNoSpecsNotice() {
            const hasRows = $('#productSpecsContainer .spec-row').length > 0;
            $('#noSpecsNotice').toggleClass('d-none', hasRows);
        }

        function addSpecRow(label, value) {
            const safeLabel = $('<div>').text(label || '').html();
            const safeValue = $('<div>').text(value || '').html();
            const html = `
                <div class="row g-2 align-items-center spec-row bg-white p-2 border rounded mx-0">
                    <div class="col-md-4">
                        <input type="text" name="spec_keys[]" class="form-control form-control-sm spec-key-input" value="${safeLabel}" placeholder="{{ translate('اسم الخاصية (مثال: نوع القماش)') }}">
                    </div>
                    <div class="col-md-7">
                        <input type="text" name="spec_values[]" class="form-control form-control-sm spec-val-input" value="${safeValue}" placeholder="{{ translate('القيمة (مثال: قطن 100%)') }}">
                    </div>
                    <div class="col-md-1 text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-spec-row-btn" title="{{ translate('حذف') }}">
                            <i class="tio-delete-outlined"></i>
                        </button>
                    </div>
                </div>
            `;
            const $row = $(html);
            $('#productSpecsContainer').append($row);
            toggleNoSpecsNotice();
            if (label) {
                $row.find('.spec-val-input').focus();
            } else {
                $row.find('.spec-key-input').focus();
            }
        }

        $('#addSpecRowBtn').on('click', function () {
            addSpecRow('', '');
        });

        $('.quick-spec-btn').on('click', function () {
            const label = $(this).data('label');
            let exists = false;
            $('#productSpecsContainer .spec-key-input').each(function () {
                if (($(this).val() || '').trim() === label) {
                    exists = true;
                    $(this).closest('.spec-row').find('.spec-val-input').focus();
                    return false;
                }
            });
            if (!exists) {
                addSpecRow(label, '');
            }
        });

        $(document).on('click', '.remove-spec-row-btn', function () {
            $(this).closest('.spec-row').remove();
            toggleNoSpecsNotice();
        });
    });
</script>
