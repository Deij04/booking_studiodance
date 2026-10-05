@props(['value' => '', 'min' => '00:00', 'max' => '23:30'])
<select {{ $attributes }}>
    <option value="">Pilih jam</option>
    @for($minute = 0; $minute < 1440; $minute += 30)
        @php
            $time = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
        @endphp
        @if($time >= $min && $time <= $max)
            <option value="{{ $time }}" @selected($value === $time)>{{ $time }}</option>
        @endif
    @endfor
</select>
