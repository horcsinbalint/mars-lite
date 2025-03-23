@php
    $value = $model::notificationCount();
@endphp
@if ($value != 0)
    <span class="new badge" data-badge-caption="">{{ $value }}</span>
@endif
