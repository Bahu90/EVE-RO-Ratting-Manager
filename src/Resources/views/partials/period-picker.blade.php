{{-- Period, and optionally corporation or character, as a GET form so the choice survives a reload and can be shared as a link. --}}
<form method="GET" action="{{ $action ?? url()->current() }}" class="rm-filters" @if(!empty($formId)) id="{{ $formId }}" @endif>
    @if(!empty($corporations) && count($corporations) > 1)
        <select name="corporation" class="form-control form-control-sm" aria-label="{{ trans('ratting-manager::common.corporation') }}">
            <option value="">{{ trans('ratting-manager::common.all_corporations') }}</option>
            @foreach($corporations as $id => $name)
                <option value="{{ $id }}" @if(($pickedCorporation ?? null) === $id) selected @endif>{{ $name }}</option>
            @endforeach
        </select>
    @endif
    @if(!empty($characterOptions) && count($characterOptions) > 1)
        <select name="character" class="form-control form-control-sm" aria-label="{{ trans('ratting-manager::common.character') }}">
            <option value="">{{ trans('ratting-manager::common.all_characters') }}</option>
            @foreach($characterOptions as $id => $name)
                <option value="{{ $id }}" @if(($pickedCharacter ?? null) === $id) selected @endif>{{ $name }}</option>
            @endforeach
        </select>
    @endif
    @if($showPeriod ?? true)
        <select name="period" class="form-control form-control-sm" aria-label="{{ trans('ratting-manager::common.period') }}">
            @foreach(\RattingManager\Services\Ratting\Period::CHOICES as $choice)
                <option value="{{ $choice }}" @if($period->key === $choice) selected @endif>{{ trans('ratting-manager::common.period_' . $choice) }}</option>
            @endforeach
            <optgroup label="{{ trans('ratting-manager::common.period_months') }}">
                @foreach(\RattingManager\Services\Ratting\Period::monthChoices() as $choice => $label)
                    <option value="{{ $choice }}" @if($period->key === $choice) selected @endif>{{ $label }}</option>
                @endforeach
            </optgroup>
        </select>
    @else
        <input type="hidden" name="period" value="{{ $period->key }}">
    @endif
    <button type="submit" class="btn btn-sm btn-rm-primary">{{ trans('ratting-manager::common.apply') }}</button>
</form>
