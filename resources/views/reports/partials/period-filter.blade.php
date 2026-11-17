@php
    $period = $period ?? '30';
    $presets = [
        'hari_ini' => 'Hari Ini',
        '7' => '7 Hari',
        '30' => '30 Hari',
        '90' => '90 Hari',
        '365' => '1 Tahun',
        'custom' => 'Rentang Tanggal',
    ];
    $basePath = $base ?? 'admin';
@endphp

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route($basePath.'.reports.sales') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label for="periode" class="form-label small mb-1">Periode</label>
                <select class="form-select form-select-sm" id="periode" name="periode">
                    @foreach ($presets as $value => $label)
                        <option value="{{ $value }}" @selected($period === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label for="dari" class="form-label small mb-1">Dari Tanggal</label>
                <input type="date" class="form-control form-control-sm" id="dari" name="dari" value="{{ $from ?? '' }}">
            </div>

            <div class="col-6 col-md-3">
                <label for="sampai" class="form-label small mb-1">Sampai Tanggal</label>
                <input type="date" class="form-control form-control-sm" id="sampai" name="sampai" value="{{ $to ?? '' }}">
            </div>

            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
                <a href="{{ route($basePath.'.reports.sales') }}" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>
