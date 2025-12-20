@props([
'baseUrl',
'prevValue',
'nextValue',
'prevLabel' => '',
'nextLabel' => '',
'prevAlt' => '',
'nextAlt' => '',
'inputType' => '',
'inputName' => '',
'inputValue',
'displayLabel'
])

<div class="attendances__pager">
    <a href="{{ $baseUrl }}?{{ $inputName }}={{ $prevValue }}" class="attendances__pager-prev">
        <img src="{{ asset('images/arrow-left.png') }}" alt="{{ $prevAlt }}" class="attendances__pager-prev-img">
        <p class="attendances__pager-prev-label">{{ $prevLabel }}</p>
    </a>
    <form action="{{ $baseUrl }}" method="get" class="attendances__pager-current">
        <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="attendances__pager-calendar-img" onclick="document.querySelector('.attendances__pager-current-input').showPicker()">
        <input type="{{ $inputType }}" name="{{ $inputName }}" class="attendances__pager-current-input" onchange="this.form.submit()" value="{{ $inputValue }}">
        <span class="attendances__pager-current-label">{{ $displayLabel }}</span>
    </form>
    <a href="{{ $baseUrl }}?{{ $inputName }}={{ $nextValue }}" class="attendances__pager-next">
        <p class="attendances__pager-next-label">{{ $nextLabel }}</p>
        <img src="{{ asset('images/arrow-right.png') }}" alt="{{ $nextAlt }}" class="attendances__pager-next-img">
    </a>
</div>