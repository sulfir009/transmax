<div class="table_container custom-scrollbar">
    <table class="custom-table">
        <thead>
        <tr>
            <th></th>
            @foreach($race->stops as $stop)
                <th>{!! $stop->stopCity . ' ' . $stop->stopTitle !!}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($race->stops as $stopFirst)
            <tr>
                <td class="left-column">{!! $stopFirst->stopCity . ' ' . $stopFirst->stopTitle !!}</td>
                @foreach($race->stops as $stopSecond)
                    <td>
                        {{ $tourStopPrices[$race->id][$stopFirst->stop_id][$stopSecond->stop_id]['price'] ?? '' }}
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>