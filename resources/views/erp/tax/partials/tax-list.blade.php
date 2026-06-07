<div class="table-responsive">
    <table class="table table-hover align-middle summary-tax-table w-100">
        <thead>
        <tr>
            <th>Date</th>
            <th>Document</th>
            <th>{{ $partyLabel }}</th>
            <th>Shipment</th>
            <th>VAT Class</th>
            <th>Currency</th>
            <th class="text-end">Base</th>
            <th class="text-end">VAT</th>
            <th class="text-end">WHT/CWT</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $index => $cell)
                    <td class="{{ $index >= 6 ? 'text-end fw-semibold' : '' }}">{{ $index >= 6 ? number_format($cell, 2) : $cell }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
