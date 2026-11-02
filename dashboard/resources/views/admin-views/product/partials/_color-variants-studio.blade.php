<div class="card mb-4 border shadow-xs" style="border-radius: 12px; border-color: #E5E7EB !important; overflow: hidden;">
    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-1 d-flex align-items-center gap-2" style="font-weight: 700; color: #171717;">
                <span class="rounded-circle d-inline-flex justify-content-center align-items-center text-white shadow-xs" style="width: 28px; height: 28px; background-color: #A71927; font-size: 14px;">
                    <i class="tio-palette"></i>
                </span>
                <span>{{ translate('متغيرات الألوان والصور المربوطة (Color Variants & Images)') }}</span>
            </h5>
            <small class="text-muted font-size-12">
                {{ translate('أضف ألوان المنتج واربط كل لون بصورته المخصصة لتوليد متغيرات المنتج (الأسعار والمخزون) وعرضها للعميل وفلترة المنتجات.') }}
            </small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleCustomColorBarBtn" style="border-radius: 8px; font-weight: 600;">
                <i class="tio-tune mr-1"></i> {{ translate('تخصيص لون جديد') }}
            </button>
        </div>
    </div>

    <div class="card-body p-3 p-md-4" style="background: #FAFAFA;">
        <!-- Custom Color Creator Toolbar -->
        <div class="p-3 mb-3 bg-white border rounded shadow-xs" id="customColorToolbar" style="border-radius: 10px; border-color: #E5E7EB !important;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center flex-wrap gap-2 flex-grow-1">
                    <label class="font-size-12 font-weight-bold text-muted mb-0 mr-1">
                        {{ translate('اختر الدرجة واسم اللون:') }}
                    </label>
                    <!-- Color Picker Box -->
                    <div class="d-flex align-items-center gap-2 px-2 py-1 border rounded bg-light" style="border-color: #E5E7EB !important;">
                        <div class="position-relative rounded-circle shadow-xs" style="width: 28px; height: 28px; min-width: 28px; cursor: pointer; border: 2px solid #FFFFFF; box-shadow: 0 0 0 1px #D1D5DB; overflow: hidden; background-color: #A71927;" id="toolbarColorPreview">
                            <input type="color" id="toolbarHexPicker" value="#A71927" style="position: absolute; top: -10px; left: -10px; width: 50px; height: 50px; opacity: 0; cursor: pointer;">
                        </div>
                        <span id="toolbarHexLabel" class="font-weight-bold font-size-12 text-uppercase text-secondary" style="letter-spacing: 0.5px;">#A71927</span>
                    </div>

                    <!-- Color Name Input -->
                    <div class="flex-grow-1" style="min-width: 220px; max-width: 380px;">
                        <input type="text" id="toolbarColorName" class="form-control form-control-sm" placeholder="{{ translate('أدخل اسم اللون (مثال: عنابي ملكي، كحلي، زيتي...)') }}" style="border-radius: 6px; font-weight: 600;">
                    </div>

                    <!-- Add Button -->
                    <button type="button" class="btn btn-sm text-white px-3" id="toolbarAddBtn" style="background-color: #A71927; border-radius: 6px; font-weight: 600; white-space: nowrap;">
                        <i class="tio-add"></i> {{ translate('إضافة اللون للمنتج') }}
                    </button>
                </div>
            </div>

            <!-- Quick Fashion Palette Chips -->
            <div class="mt-3 pt-2 border-top">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="font-size-11 font-weight-bold text-muted mr-1">{{ translate('لوحة ألوان الأزياء السريعة:') }}</span>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="عنابي بن هويش" data-hex="#A71927">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #A71927;"></span> عنابي بن هويش
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="أسود فاخر" data-hex="#171717">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #171717;"></span> أسود فاخر
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="أبيض ناصع" data-hex="#FFFFFF">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #FFFFFF;"></span> أبيض ناصع
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="كحلي ملكي" data-hex="#1B365D">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #1B365D;"></span> كحلي ملكي
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="بيج رملي" data-hex="#E8DCC4">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #E8DCC4;"></span> بيج رملي
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="رصاصي كلاسيك" data-hex="#707070">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #707070;"></span> رصاصي
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="زيتي هادئ" data-hex="#4B5320">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #4B5320;"></span> زيتي
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="بني دافئ" data-hex="#5C4033">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #5C4033;"></span> بني
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="وردي بودري" data-hex="#E07A5F">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #E07A5F;"></span> وردي
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="سماوي" data-hex="#709775">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #709775;"></span> سماوي
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary quick-color-btn d-inline-flex align-items-center gap-1" data-name="ذهبي" data-hex="#D4AF37">
                        <span class="rounded-circle d-inline-block border shadow-xs" style="width: 12px; height: 12px; background: #D4AF37;"></span> ذهبي
                    </button>
                </div>
            </div>
        </div>

        <!-- Color Cards Grid -->
        <div class="d-flex flex-wrap gap-3" id="colorCardsContainer" style="min-height: 50px;">
            @php
                $existingColors = isset($product) ? ($product->color_images_list ?? []) : [];
            @endphp

            @foreach($existingColors as $idx => $cData)
                @php
                    $cHex = $cData['color_code'] ?? '#000000';
                    $cName = $cData['color_name'] ?? '';
                    $hasImg = !empty($cData['image']);
                    $cImgUrl = $cData['image_url'] ?? asset('public/assets/admin/img/160x160/2.png');
                    $uid = 'col_' . $idx . '_' . substr(md5($cName . $idx), 0, 6);
                @endphp
                <div class="color-variant-card bg-white" id="card_{{ $uid }}" style="width: 210px; border: 1.5px solid #E5E7EB; border-radius: 12px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.2s;">
                    <!-- Card Top Header (Swatch + Hex + Delete Button) -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Clickable Color Circle with Picker -->
                            <div class="position-relative rounded-circle card-color-circle shadow-xs" style="width: 26px; height: 26px; min-width: 26px; cursor: pointer; border: 2px solid #FFFFFF; box-shadow: 0 0 0 1px #D1D5DB; overflow: hidden; background-color: {{ $cHex }};" title="{{ translate('انقر لتغيير درجة اللون') }}">
                                <input type="color" class="card-color-picker" value="{{ $cHex }}" style="position: absolute; top: -10px; left: -10px; width: 50px; height: 50px; opacity: 0; cursor: pointer;">
                            </div>
                            <span class="badge badge-soft-secondary font-weight-bold card-hex-label font-size-11" style="direction: ltr;">{{ $cHex }}</span>
                        </div>
                        <input type="hidden" name="color_codes[{{ $uid }}]" class="card-color-code" value="{{ $cHex }}">

                        <!-- Delete button with generous spacing, no overlap! -->
                        <button type="button" class="btn btn-sm btn-icon remove-color-btn text-danger" style="background: #FEE2E2; border: none; border-radius: 6px; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;" data-target="card_{{ $uid }}" title="{{ translate('حذف اللون') }}">
                            <i class="tio-delete-outlined" style="font-size: 15px;"></i>
                        </button>
                    </div>

                    <!-- Color Name Input -->
                    <div class="mb-2">
                        <input type="text" name="color_names[{{ $uid }}]" class="form-control form-control-sm color-name-input text-center font-weight-bold" value="{{ $cName }}" placeholder="{{ translate('Color Name') }}" required style="border-radius: 8px; font-size: 13px; color: #171717; border: 1px solid #D1D5DB; background: #FFFFFF;">
                    </div>

                    <!-- Clean Image Dropzone (replacing default file button) -->
                    <div class="color-image-dropzone position-relative d-flex flex-column align-items-center justify-content-center rounded" style="width: 100%; height: 125px; border: 2px dashed {{ $hasImg ? '#10B981' : '#D1D5DB' }}; background: #F9FAFB; cursor: pointer; overflow: hidden; transition: all 0.2s;">
                        <img src="{{ $cImgUrl }}" class="color-preview-img w-100 h-100 position-absolute {{ $hasImg ? '' : 'd-none' }}" style="top: 0; left: 0; object-fit: cover; border-radius: 8px;">
                        
                        <div class="color-image-overlay position-absolute w-100 h-100 d-flex flex-column align-items-center justify-content-center {{ $hasImg ? '' : 'd-none' }}" style="top: 0; left: 0; background: rgba(0,0,0,0.5); color: #FFFFFF; border-radius: 8px; opacity: 0; transition: opacity 0.2s;">
                            <i class="tio-edit mb-1" style="font-size: 18px;"></i>
                            <span style="font-size: 11px; font-weight: 600;">{{ translate('تغيير الصورة') }}</span>
                        </div>

                        <div class="color-placeholder-box text-center p-2 {{ $hasImg ? 'd-none' : '' }}">
                            <i class="tio-camera text-muted" style="font-size: 26px;"></i>
                            <div class="text-muted font-size-11 mt-1 font-weight-600">{{ translate('اختر صورة للون') }}</div>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file" name="color_images[{{ $uid }}]" class="color-file-input" accept="image/*" style="display: none;">
                        <input type="hidden" name="color_existing_images[{{ $uid }}]" value="{{ $cData['image'] ?? '' }}">
                    </div>
                </div>
            @endforeach
        </div>

        <div id="noColorsNotice" class="text-center py-4 text-muted bg-white border rounded {{ count($existingColors) > 0 ? 'd-none' : '' }}" style="border-radius: 10px; border-style: dashed !important; border-color: #D1D5DB !important;">
            <div class="mb-2">
                <i class="tio-palette-outlined" style="font-size: 32px; color: #9CA3AF;"></i>
            </div>
            <p class="mb-1 font-weight-bold" style="color: #4B5563;">{{ translate('لم تتم إضافة أي ألوان لهذا المنتج بعد.') }}</p>
            <small class="text-muted">{{ translate('اختر لوناً سريعاً من اللوحة أعلاه أو استخدم ميزة تخصيص لون جديد، وسيتم توليد جدول بيانات المتغيرات تلقائياً.') }}</small>
        </div>
    </div>
</div>

<style>
.color-variant-card:hover {
    border-color: #A71927 !important;
    box-shadow: 0 4px 14px rgba(167, 25, 39, 0.08) !important;
}
.color-image-dropzone:hover {
    border-color: #A71927 !important;
    background-color: #FDF2F2 !important;
}
.color-image-dropzone:hover .color-image-overlay {
    opacity: 1 !important;
}
.remove-color-btn:hover {
    background: #FCA5A5 !important;
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let cardSequence = {{ count($existingColors) + 10 }};

        // Toggle Custom Color Toolbar
        $('#toggleCustomColorBarBtn').on('click', function() {
            $('#customColorToolbar').slideToggle(200);
        });

        // Update toolbar hex label when color picker moves
        $('#toolbarHexPicker').on('input change', function() {
            const hex = $(this).val();
            $('#toolbarColorPreview').css('background-color', hex);
            $('#toolbarHexLabel').text(hex);
        });

        function createColorCard(name, hex) {
            $('#noColorsNotice').addClass('d-none');
            const uid = 'col_' + Date.now() + '_' + (cardSequence++);
            const defaultImgUrl = '{{ asset("public/assets/admin/img/160x160/2.png") }}';

            const html = `
                <div class="color-variant-card bg-white" id="card_${uid}" style="width: 210px; border: 1.5px solid #E5E7EB; border-radius: 12px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.2s;">
                    <!-- Card Top Header -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="position-relative rounded-circle card-color-circle shadow-xs" style="width: 26px; height: 26px; min-width: 26px; cursor: pointer; border: 2px solid #FFFFFF; box-shadow: 0 0 0 1px #D1D5DB; overflow: hidden; background-color: ${hex};" title="{{ translate('انقر لتغيير درجة اللون') }}">
                                <input type="color" class="card-color-picker" value="${hex}" style="position: absolute; top: -10px; left: -10px; width: 50px; height: 50px; opacity: 0; cursor: pointer;">
                            </div>
                            <span class="badge badge-soft-secondary font-weight-bold card-hex-label font-size-11" style="direction: ltr;">${hex}</span>
                        </div>
                        <input type="hidden" name="color_codes[${uid}]" class="card-color-code" value="${hex}">

                        <button type="button" class="btn btn-sm btn-icon remove-color-btn text-danger" style="background: #FEE2E2; border: none; border-radius: 6px; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;" data-target="card_${uid}" title="{{ translate('حذف اللون') }}">
                            <i class="tio-delete-outlined" style="font-size: 15px;"></i>
                        </button>
                    </div>

                    <!-- Color Name Input -->
                    <div class="mb-2">
                        <input type="text" name="color_names[${uid}]" class="form-control form-control-sm color-name-input text-center font-weight-bold" value="${name}" placeholder="{{ translate('Color Name') }}" required style="border-radius: 8px; font-size: 13px; color: #171717; border: 1px solid #D1D5DB; background: #FFFFFF;">
                    </div>

                    <!-- Clean Image Dropzone -->
                    <div class="color-image-dropzone position-relative d-flex flex-column align-items-center justify-content-center rounded" style="width: 100%; height: 125px; border: 2px dashed #D1D5DB; background: #F9FAFB; cursor: pointer; overflow: hidden; transition: all 0.2s;">
                        <img src="${defaultImgUrl}" class="color-preview-img w-100 h-100 position-absolute d-none" style="top: 0; left: 0; object-fit: cover; border-radius: 8px;">
                        
                        <div class="color-image-overlay position-absolute w-100 h-100 d-flex flex-column align-items-center justify-content-center d-none" style="top: 0; left: 0; background: rgba(0,0,0,0.5); color: #FFFFFF; border-radius: 8px; opacity: 0; transition: opacity 0.2s;">
                            <i class="tio-edit mb-1" style="font-size: 18px;"></i>
                            <span style="font-size: 11px; font-weight: 600;">{{ translate('تغيير الصورة') }}</span>
                        </div>

                        <div class="color-placeholder-box text-center p-2">
                            <i class="tio-camera text-muted" style="font-size: 26px;"></i>
                            <div class="text-muted font-size-11 mt-1 font-weight-600">{{ translate('اختر صورة للون') }}</div>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file" name="color_images[${uid}]" class="color-file-input" accept="image/*" style="display: none;">
                        <input type="hidden" name="color_existing_images[${uid}]" value="">
                    </div>
                </div>
            `;
            $('#colorCardsContainer').append(html);

            // Trigger combinations update immediately!
            if (typeof combination_update === 'function') {
                combination_update();
            }
        }

        // Add from Custom Color Toolbar
        $('#toolbarAddBtn').on('click', function() {
            const name = $('#toolbarColorName').val().trim();
            const hex = $('#toolbarHexPicker').val();
            if (!name) {
                toastr.warning('{{ translate("يرجى إدخال اسم اللون أولاً") }}');
                $('#toolbarColorName').focus();
                return;
            }
            createColorCard(name, hex);
            $('#toolbarColorName').val('');
        });

        // Quick Palette One-Click Add
        $('.quick-color-btn').on('click', function() {
            const name = $(this).data('name');
            const hex = $(this).data('hex');
            createColorCard(name, hex);
        });

        // Interactive Card Swatch Picker (Live change per card)
        $(document).on('input change', '.card-color-picker', function() {
            const hex = $(this).val();
            const $card = $(this).closest('.color-variant-card');
            $card.find('.card-color-circle').css('background-color', hex);
            $card.find('.card-hex-label').text(hex);
            $card.find('.card-color-code').val(hex);
        });

        // Remove Color Card
        $(document).on('click', '.remove-color-btn', function() {
            const target = $(this).data('target');
            $('#' + target).remove();
            if ($('#colorCardsContainer .color-variant-card').length === 0) {
                $('#noColorsNotice').removeClass('d-none');
            }
            // Trigger combinations update
            if (typeof combination_update === 'function') {
                combination_update();
            }
        });

        // Click Dropzone to open hidden file input
        $(document).on('click', '.color-image-dropzone', function(e) {
            if ($(e.target).is('input[type="file"]')) return;
            $(this).find('.color-file-input').click();
        });

        // Live Image Preview on File Change
        $(document).on('change', '.color-file-input', function() {
            const input = this;
            const $dropzone = $(this).closest('.color-image-dropzone');
            const $previewImg = $dropzone.find('.color-preview-img');
            const $overlay = $dropzone.find('.color-image-overlay');
            const $placeholder = $dropzone.find('.color-placeholder-box');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $previewImg.attr('src', e.target.result).removeClass('d-none');
                    $overlay.removeClass('d-none');
                    $placeholder.addClass('d-none');
                    $dropzone.css('border-color', '#10B981');
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
    });
</script>
