@php
    $current = $comparison['current'];
    $previous = $comparison['previous'];
    $percent = fn (float $now, float $before) => $before == 0.0 ? null : round((($now - $before) / $before) * 100, 1);

    $change = function (float $now, float $before, string $label, string $icon, string $color, bool $asNumber = false) use ($percent) {
        $p = $percent($now, $before);
        $naik = $p !== null && $p > 0;
        $turun = $p !== null && $p < 0;

        $format = fn (float $value) => $asNumber
            ? number_format($value, 0, ',', '.')
            : rupiah($value);

        return [
            'label' => $label,
            'icon' => $icon,
            'color' => $color,
            'now' => $format($now),
            'before' => $format($before),
            'percent' => $p,
            'class' => $naik ? 'text-success' : ($turun ? 'text-danger' : 'text-muted-2'),
            'iconClass' => $naik ? 'bi-arrow-up-right' : ($turun ? 'bi-arrow-down-right' : 'bi-dash'),
        ];
    };

    $rows = [
        $change($current['total_sales'], $previous['total_sales'], 'Total Penjualan', 'bi-cash-stack', 'success'),
        $change((float) $current['transaction_count'], (float) $previous['transaction_count'], 'Jumlah Transaksi', 'bi-receipt', 'primary', true),
        $change($current['average_transaction'], $previous['average_transaction'], 'Rata-rata Transaksi', 'bi-calculator', 'info'),
        $change($current['gross_profit'], $previous['gross_profit'], 'Laba Kotor', 'bi-piggy-bank', 'success'),
        $change($current['total_discount'], $previous['total_discount'], 'Total Diskon', 'bi-tag', 'warning'),
    ];
@endphp

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-bar-chart-steps"></i> Perbandingan Periode</span>
        <span class="small text-muted-2">
            {{ \Carbon\Carbon::parse($comparison['prev_from'])->format('d/m/Y') }} &ndash;
            {{ \Carbon\Carbon::parse($comparison['prev_to'])->format('d/m/Y') }}
            (periode sebelumnya, {{ $comparison['days'] }} hari)
        </span>
    </div>
    <div class="card-body">
        <div class="row g-2">
            @foreach ($rows as $row)
                <div class="col-6 col-lg-4 col-xl">
                    <div class="border rounded p-2 h-100">
                        <div class="small text-muted-2">{{ $row['label'] }}</div>
                        <div class="fw-bold font-money">{{ $row['now'] }}</div>
                        <div class="small {{ $row['class'] }}">
                            <i class="bi {{ $row['iconClass'] }}"></i>
                            @if ($row['percent'] === null)
                                baru
                            @else
                                {{ $row['percent'] > 0 ? '+' : '' }}{{ $row['percent'] }}%
                            @endif
                            <span class="text-muted-2">vs {{ $row['before'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
