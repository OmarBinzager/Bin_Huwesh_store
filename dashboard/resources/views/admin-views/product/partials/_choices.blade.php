@foreach ($choice_options as $key => $choice)
    @if(isset($choice['name']) && ($choice['name'] === 'choice_color' || $choice['title'] === 'Color' || $choice['title'] === 'اللون'))
        @continue
    @endif
    <div class="row">
        <div class="col-md-3">
            <input type="hidden" name="choice_no[]" value="{{ $choice_no[$key] ?? null }}">
            <input type="text" class="form-control" name="choice[]" value="{{ $choice['title'] }}"
                placeholder="{{ translate('Choice Title') }}" readonly>
        </div>
        <div class="col-lg-9">
            <input type="text" class="form-control call-update-sku"
                name="choice_options_{{ $choice_no[$key] ?? null }}[]" data-role="tagsinput"
                value="@foreach ($choice['options'] as $c) {{ $c . ',' }} @endforeach">
        </div>
    </div>
@endforeach
