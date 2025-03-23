@php
// if there is only one item:
if (!isset($items)) {
    $items = [$item];
}

$itemCount = count($items);
$rowCount = $lastHour - $firstHour;

// these are percentages
$dayCount = $firstDay->diffInDays($lastDay)+1;
$columnWidth = 100.0 / ($dayCount * $itemCount);

@endphp

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var elem = document.getElementById('firstDay');
        M.Datepicker.init(elem, {
            format: 'yyyy-mm-dd',
            firstDay: 1,
            showClearBtn: false,
            onClose: () => @this.firstDayUpdated(elem.value)
        });

        window.stepDays = function(days) {
            @this.step(days);
            const elem = document.getElementById('firstDay');
            let date = new Date(elem.value);
            date.setDate(date.getDate() + days);
            // HACK: This is the easiest way to get yyyy-mm-dd.
            elem.value = date.toISOString().slice(0, 10);
        };
    });
</script>
@endpush

<div>
    {{-- navigation buttons --}}
    @if($isPrintVersion)
    <div class="navbuttons">
        <div>{{$firstDay->isoFormat('MM.DD.')}} – {{$lastDay->isoFormat('MM.DD.')}}</div>
        <x-input.button wire:click="step(-7)" text="Egy héttel előbb" />
        <x-input.button wire:click="step(7)" text="Egy héttel később" />
    </div>
    @else
    <div class="row">
        <div class="col s4 left-align">
            <x-input.button floating onclick="stepDays({{ -1 * $dayCount }})" icon="chevron_left" />
        </div>
        <div class="col s4 center-align" wire:ignore>
            <input type="text" class="datepicker validate" id="firstDay" value="{{$firstDay->format('Y-m-d')}}"
                    style="color:#b38f2f; text-decoration: underline; border: none; box-shadow: none;
                    text-align: center; font-size: 1.2em; cursor: pointer" >
        </div>
        <div class="col s4 right-align">
            <x-input.button floating onclick="stepDays({{ $dayCount }})" icon="chevron_right" />
        </div>
    </div>
    @endif

    <div class="row s12" style="margin-bottom: 0;">
        <div class="col s1" style="padding: 0;">
        </div>
        <div class="col s11" style="padding: 0; display: flex; justify-content: space-between;">
            @for($day = 0; $day < $dayCount; ++$day)
                @for($item_index = 0; $item_index < $itemCount; ++$item_index)
                @php
                $currentDay = $firstDay->copy();
                $currentDay->addDays($day);
                $item = $items[$item_index];
                @endphp
                <div style="padding: 0; width: {{100.0 / ($dayCount * $itemCount)}}%;">
                    <div style="text-align: center; width: 100%;padding: 0; padding: 1em;"
                        @if($dayCount > 1 && $currentDay->isToday())
                            class="coli blue white-text"
                        @endif
                    >
                        @if($dayCount > 1)
                            <strong><span class="date">{{$firstDay->copy()->addDays($day)->isoFormat('MM.DD.')}} (</span>{{$firstDay->copy()->addDays($day)->isoFormat('dddd')}}<span class="date">)</span></strong>
                        @else
                            <a href="{{ route('reservations.items.show', $item) }}">
                                {{$item->name}}
                            </a>
                        @endif
                    </div>
                </div>
                @endfor
            @endfor
        </div>
    </div>
    <div class="row s12 table_docs">
        <div class="col s1" style="padding: 0; height: 100%;">
            @for($hour=$firstHour; $hour <= $lastHour; ++$hour)
                <div style="box-sizing: border-box;height: {{100.0 / ($lastHour-$firstHour+1)}}%; text-align: center;border-bottom: 0.25px solid black;border-top: 0.25px solid black;border-right: 1px solid black;">
                    {{$hour}}:00
                </div>
            @endfor
        </div>
        <div class="col s11" style="padding: 0; height: 100%; display: flex; justify-content: space-between;">
            <script>
                console.log("{{$dayCount}}");
            </script>
            @for($day = 0; $day < $dayCount; ++$day)
                @for($item_index = 0; $item_index < $itemCount; ++$item_index)
                @php
                $item = $items[$item_index];
                @endphp
                <div style="padding: 0; height: 100%; width: {{100.0 / ($dayCount * $itemCount)}}%;">
                    @foreach($this->blocks[$item_index] as $block)
                        @php
                            $isReservation = !$block->isFree();
                            $isDisabled = !$isReservation && ($item->isOutOfOrder() || $block->getUntil() < \Carbon\Carbon::now());
                            if ($isReservation) {
                                $reservation = $block->reservation();
                                $isOurs = $reservation->user?->is(user());
                            } else {
                                $reservation = null;
                                $isOurs = null;
                            }
                        @endphp
                        @if(floor($firstDay->diffInDays($block->getFrom())) == $day)
                            @if($isReservation)
                                <a href="{{ route('reservations.show', $block->reservation()) }}"
                                    style="text-decoration: none;">
                                    <div style="height: {{ $block->lengthInSeconds()/($lastHour-$firstHour+1)/3600.0*100 }}%; width: 100%;;padding: 0;"
                                    @class([
                                                        'timetable-block',
                                                        'valign-wrapper', 'center-align',
                                                        'red' => $isReservation && !$isOurs,
                                                        'orange' => $isOurs,
                                                        'green' => !$isReservation && !$isDisabled,
                                                        'grey' => $isDisabled,
                                                        'darken-4' => $isReservation && $reservation->verified
                                                                        || !$isReservation && !$isDisabled,
                                                        'lighten-2' => $isReservation && !$reservation->verified
                                                ])>
                                        @if($isReservation)
                                            {{$reservation->displayName()}}
                                            @if($isPrintVersion)
                                                ({{ $block->getFrom()->isoFormat('HH:mm') }}
                                                    –
                                                    {{ $block->getUntil()->isoFormat('HH:mm') }})
                                            @endif
                                        @endif
                                    </div>
                                </a>
                            @elseif(!$isDisabled && user()->can('requestReservation', $item))
                                <a href="{{ route('reservations.create', ['item' => $item])
                                            . "?from={$block->getFrom()}&until={$block->getUntil()}"
                                    }}"
                                    style="text-decoration: none;">
                                    <div style="height: {{ $block->lengthInSeconds()/($lastHour-$firstHour+1)/3600.0*100 }}%; width: 100%;;padding: 0;"
                                    @class([
                                                        'timetable-block',
                                                        'valign-wrapper', 'center-align',
                                                        'red' => $isReservation && !$isOurs,
                                                        'orange' => $isOurs,
                                                        'green' => !$isReservation && !$isDisabled,
                                                        'grey' => $isDisabled,
                                                        'darken-4' => $isReservation && $reservation->verified
                                                                        || !$isReservation && !$isDisabled,
                                                        'lighten-2' => $isReservation && !$reservation->verified
                                                ])>
                                        @if($isReservation)
                                            {{$reservation->displayName()}}
                                            @if($isPrintVersion)
                                                ({{ $block->getFrom()->isoFormat('HH:mm') }}
                                                    –
                                                    {{ $block->getUntil()->isoFormat('HH:mm') }})
                                            @endif
                                        @endif
                                    </div>
                                </a>
                            @else
                                <div style="height: {{ $block->lengthInSeconds()/($lastHour-$firstHour+1)/3600.0*100 }}%; width: 100%;;padding: 0;"
                                @class([
                                                    'timetable-block',
                                                    'valign-wrapper', 'center-align',
                                                    'red' => $isReservation && !$isOurs,
                                                    'orange' => $isOurs,
                                                    'green' => !$isReservation && !$isDisabled,
                                                    'grey' => $isDisabled,
                                                    'darken-4' => $isReservation && $reservation->verified
                                                                    || !$isReservation && !$isDisabled,
                                                    'lighten-2' => $isReservation && !$reservation->verified
                                            ])>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>
                @endfor
            @endfor
        </div>
    </div>
</div>
