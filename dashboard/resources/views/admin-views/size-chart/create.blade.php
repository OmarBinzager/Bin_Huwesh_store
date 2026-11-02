@extends('layouts.admin.app')

@section('title', translate('Size Guide Editor'))

@push('css_or_js')
<style>
    .size-matrix-table th {
        background-color: #F8F9FA;
        color: #171717;
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
        padding: 12px 8px;
    }
    .size-matrix-table td {
        text-align: center;
        vertical-align: middle;
        padding: 6px 8px;
    }
    .size-matrix-table input.form-control {
        text-align: center;
        font-weight: 500;
        border-radius: 4px;
    }
    .matrix-highlight-row {
        background-color: #FAEFF0 !important;
    }
    .matrix-highlight-row input {
        color: #A71927 !important;
        font-weight: 700 !important;
        border-color: #F5C6CB !important;
        background-color: #FFF8F8 !important;
    }
    .size-guide-footer-alert {
        background-color: #FAEFF0;
        border: 1px solid #F5C6CB;
        border-radius: 8px;
        padding: 14px 18px;
        color: #8E1521;
        font-weight: 500;
        font-size: 13.5px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <form action="{{ route('admin.size-chart.store') }}" method="POST" id="sizeChartForm">
        @csrf

        <!-- Top Header & Actions -->
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
            <div>
                <h2 class="page-header-title mb-1" style="font-weight: 700; color: #171717;">
                    {{ translate('Size Guide Editor') }}
                </h2>
                <div class="text-muted fs-12">
                    {{ translate('Bin Huwesh • Operations Panel') }} &bull; {{ translate('Store Management / Size Guide Editor') }}
                </div>
            </div>
            <div>
                <button type="submit" class="btn btn--primary" style="background-color: #A71927; border-color: #A71927; font-weight: 600; padding: 10px 24px; border-radius: 6px;">
                    <i class="tio-save mr-1"></i> {{ translate('Save Chart') }}
                </button>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card mb-4">
            <div class="card-header bg-white border-bottom pb-3">
                <h4 class="card-title mb-0" id="liveChartTitle" style="font-weight: 700; color: #171717;">
                    {{ translate('New Size Chart') }}
                </h4>
            </div>
            <div class="card-body">
                <!-- Row 1: Name, Unit, Garment Type -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-5">
                        <div class="form-group mb-0">
                            <label class="input-label font-weight-bold" for="chartName">{{ translate('Chart Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="chartName" class="form-control"
                                   placeholder="{{ translate('e.g. Polo Shirt Size Guide') }}"
                                   value="{{ old('name', 'دليل البولو') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <div class="form-group mb-0">
                            <label class="input-label font-weight-bold" for="chartUnit">{{ translate('Unit') }} <span class="text-danger">*</span></label>
                            <select name="unit" id="chartUnit" class="form-control js-select2-custom" required>
                                <option value="سم" selected>{{ translate('cm (سم)') }}</option>
                                <option value="بوصة">{{ translate('Inch (بوصة)') }}</option>
                                <option value="EU">EU (الأحذية والأوروبي)</option>
                                <option value="UK">UK (بريطاني)</option>
                                <option value="US">US (أمريكي)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="input-label font-weight-bold" for="garmentType">{{ translate('Garment / Piece Type') }}</label>
                            <select name="garment_type" id="garmentType" class="form-control js-select2-custom">
                                <option value="علوي" selected>{{ translate('Top (علوي)') }}</option>
                                <option value="سفلي">{{ translate('Bottom (سفلي)') }}</option>
                                <option value="فساتين">{{ translate('Dresses (فساتين)') }}</option>
                                <option value="أحذية">{{ translate('Shoes (أحذية)') }}</option>
                                <option value="أطفال">{{ translate('Kids (أطفال)') }}</option>
                                <option value="إكسسوارات">{{ translate('Accessories (إكسسوارات)') }}</option>
                                <option value="عام">{{ translate('General (عام)') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Matrix Table Header Actions -->
                <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                    <h5 class="mb-0 font-weight-bold text-dark">
                        {{ translate('Size Measurements Matrix') }}
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addColumnBtn">
                            <i class="tio-add"></i> {{ translate('Add Measurement Column') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="addRowBtn">
                            <i class="tio-add"></i> {{ translate('Add Size Row') }}
                        </button>
                    </div>
                </div>

                <!-- Editable Matrix Table -->
                <div class="table-responsive border rounded mb-4">
                    <table class="table size-matrix-table mb-0" id="matrixTable">
                        <thead>
                            <tr id="matrixHeaderRow">
                                <th style="width: 130px;">{{ translate('Size') }}</th>
                                <th>
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <span>{{ translate('Chest') }}</span>
                                        <button type="button" class="btn btn-xs text-danger border-0 p-0 remove-col-btn" title="{{ translate('Remove Column') }}">&times;</button>
                                    </div>
                                    <input type="hidden" name="columns[]" value="الصدر">
                                </th>
                                <th>
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <span>{{ translate('Length') }}</span>
                                        <button type="button" class="btn btn-xs text-danger border-0 p-0 remove-col-btn" title="{{ translate('Remove Column') }}">&times;</button>
                                    </div>
                                    <input type="hidden" name="columns[]" value="الطول">
                                </th>
                                <th>
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <span>{{ translate('Shoulder') }}</span>
                                        <button type="button" class="btn btn-xs text-danger border-0 p-0 remove-col-btn" title="{{ translate('Remove Column') }}">&times;</button>
                                    </div>
                                    <input type="hidden" name="columns[]" value="الكتف">
                                </th>
                                <th style="width: 50px;">#</th>
                            </tr>
                        </thead>
                        <tbody id="matrixBody">
                            <!-- Pre-populated standard fashion sizes matching screenshot -->
                            <tr>
                                <td>
                                    <input type="text" name="rows[0][المقاس]" class="form-control font-weight-bold" value="S" required>
                                </td>
                                <td><input type="text" name="rows[0][الصدر]" class="form-control" value="96"></td>
                                <td><input type="text" name="rows[0][الطول]" class="form-control" value="67"></td>
                                <td><input type="text" name="rows[0][الكتف]" class="form-control" value="42"></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-danger remove-row-btn">&times;</button>
                                </td>
                            </tr>
                            <tr class="matrix-highlight-row">
                                <td>
                                    <input type="text" name="rows[1][المقاس]" class="form-control font-weight-bold" value="M" required>
                                </td>
                                <td><input type="text" name="rows[1][الصدر]" class="form-control" value="100"></td>
                                <td><input type="text" name="rows[1][الطول]" class="form-control" value="69"></td>
                                <td><input type="text" name="rows[1][الكتف]" class="form-control" value="44"></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-danger remove-row-btn">&times;</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="text" name="rows[2][المقاس]" class="form-control font-weight-bold" value="L" required>
                                </td>
                                <td><input type="text" name="rows[2][الصدر]" class="form-control" value="104"></td>
                                <td><input type="text" name="rows[2][الطول]" class="form-control" value="71"></td>
                                <td><input type="text" name="rows[2][الكتف]" class="form-control" value="46"></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-danger remove-row-btn">&times;</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="text" name="rows[3][المقاس]" class="form-control font-weight-bold" value="XL" required>
                                </td>
                                <td><input type="text" name="rows[3][الصدر]" class="form-control" value="108"></td>
                                <td><input type="text" name="rows[3][الطول]" class="form-control" value="73"></td>
                                <td><input type="text" name="rows[3][الكتف]" class="form-control" value="48"></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-danger remove-row-btn">&times;</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="text" name="rows[4][المقاس]" class="form-control font-weight-bold" value="XXL" required>
                                </td>
                                <td><input type="text" name="rows[4][الصدر]" class="form-control" value="112"></td>
                                <td><input type="text" name="rows[4][الطول]" class="form-control" value="75"></td>
                                <td><input type="text" name="rows[4][الكتف]" class="form-control" value="50"></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-danger remove-row-btn">&times;</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Linkage Section (الربط) -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="input-label font-weight-bold" for="categoryId">
                                {{ translate('Link to Category (التصنيف)') }}
                            </label>
                            <select name="category_id" id="categoryId" class="form-control js-select2-custom">
                                <option value="">{{ translate('--- Select Category ---') }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ translate('All products under this category will automatically inherit this size chart unless overridden.') }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="input-label font-weight-bold" for="productIds">
                                {{ translate('Product Exception / Specific Override (ربط أو استثناء منتج)') }}
                            </label>
                            <select name="product_ids[]" id="productIds" class="form-control js-select2-custom" multiple="multiple">
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }} (ID: {{ $prod->id }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ translate('Specific products selected here will take highest priority over category charts.') }}</small>
                        </div>
                    </div>
                </div>

                <!-- Measurement Instructions (تعليمات القياس) -->
                <div class="form-group mb-0">
                    <label class="input-label font-weight-bold" for="instructions">
                        {{ translate('Measurement Instructions (تعليمات القياس)') }}
                    </label>
                    <textarea name="instructions" id="instructions" class="form-control" rows="3"
                              placeholder="{{ translate('e.g. Measure from armpit to armpit, with clarification on chest circumference...') }}">{{ old('instructions', 'من أسفل الإبط إلى الإبط، مع توضيح قياس محيط الصدر.') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Footer Note Banner Matching Screenshot -->
        <div class="size-guide-footer-alert d-flex align-items-center gap-2">
            <i class="tio-document-text font-size-18 mr-2" style="color: #A71927;"></i>
            <div>
                <strong style="color: #8E1521;">{{ translate('Linkage Priority (أولوية الربط)') }}:</strong>
                {{ translate('A specific product size chart takes precedence over the category size chart; publishing an incomplete size chart is prevented.') }}
            </div>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    $(document).ready(function() {
        // Live title update
        $('#chartName').on('input', function() {
            let val = $(this).val().trim();
            $('#liveChartTitle').text(val ? 'جدول مقاسات ' + val.replace(/^دليل\s*/, '') : '{{ translate("New Size Chart") }}');
        });
        $('#chartName').trigger('input');

        // Initial columns
        let currentColumns = ['الصدر', 'الطول', 'الكتف'];
        let rowCount = 5;

        // Add Measurement Column
        $('#addColumnBtn').on('click', function() {
            let colName = prompt('{{ translate("Enter Measurement Column Name (e.g. الخصر، الأرداف، الأكمام):") }}');
            if (colName && colName.trim() !== '') {
                colName = colName.trim();
                if (currentColumns.includes(colName) || colName === 'المقاس') {
                    alert('{{ translate("Column already exists!") }}');
                    return;
                }
                currentColumns.push(colName);

                // Add to thead before action column
                let thHtml = `
                    <th>
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <span>${colName}</span>
                            <button type="button" class="btn btn-xs text-danger border-0 p-0 remove-col-btn" title="{{ translate('Remove Column') }}">&times;</button>
                        </div>
                        <input type="hidden" name="columns[]" value="${colName}">
                    </th>
                `;
                $('#matrixHeaderRow th:last-child').before(thHtml);

                // Add input to each existing row
                $('#matrixBody tr').each(function(idx) {
                    let tdHtml = `<td><input type="text" name="rows[${idx}][${colName}]" class="form-control" value=""></td>`;
                    $(this).find('td:last-child').before(tdHtml);
                });
            }
        });

        // Remove Column
        $(document).on('click', '.remove-col-btn', function() {
            let $th = $(this).closest('th');
            let colIndex = $th.index();
            let colName = $th.find('input[name="columns[]"]').val();

            currentColumns = currentColumns.filter(c => c !== colName);
            $th.remove();

            // Remove td in each row
            $('#matrixBody tr').each(function() {
                $(this).find('td').eq(colIndex).remove();
            });
        });

        // Add Size Row
        $('#addRowBtn').on('click', function() {
            let sizeName = prompt('{{ translate("Enter Size Label (e.g. 3XL, 38, 40):") }}');
            if (!sizeName) return;

            let rowHtml = `
                <tr>
                    <td>
                        <input type="text" name="rows[${rowCount}][المقاس]" class="form-control font-weight-bold" value="${sizeName}" required>
                    </td>
            `;
            currentColumns.forEach(col => {
                rowHtml += `<td><input type="text" name="rows[${rowCount}][${col}]" class="form-control" value=""></td>`;
            });
            rowHtml += `
                    <td>
                        <button type="button" class="btn btn-xs btn-outline-danger remove-row-btn">&times;</button>
                    </td>
                </tr>
            `;
            $('#matrixBody').append(rowHtml);
            rowCount++;
        });

        // Remove Row
        $(document).on('click', '.remove-row-btn', function() {
            if ($('#matrixBody tr').length <= 1) {
                alert('{{ translate("You must keep at least one size row!") }}');
                return;
            }
            $(this).closest('tr').remove();
        });
    });
</script>
@endpush
