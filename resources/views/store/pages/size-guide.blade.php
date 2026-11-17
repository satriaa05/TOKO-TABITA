@extends('layouts.store')

@section('title', 'Panduan Ukuran')

@section('content')
    <section class="container py-4 py-lg-5" style="max-width: 860px;">
        <nav class="st-breadcrumb mb-3" aria-label="Jejak navigasi">
            <a href="{{ route('home') }}">Home</a>
            <span aria-hidden="true">&nbsp;/&nbsp;</span>
            <span aria-current="page">Panduan Ukuran</span>
        </nav>

        <h1 class="store-section-title mb-1">Panduan Ukuran</h1>
        <p class="text-muted-2 mb-4">Semua ukuran dalam sentimeter (cm). Jika ragu antara dua ukuran, kami sarankan pilih satu ukuran di atas. Ukur lingkar dada/pinggang Anda dengan pita meter saat berdiri rileks.</p>

        @php($sets = config('size_charts.sets'))
        @foreach (['dress' => 'Dress & Gaun', 'top' => 'Atasan & Blouse', 'bottom' => 'Bawahan (Celana/Rok)', 'hijab' => 'Hijab'] as $key => $label)
            @continue(! isset($sets[$key]))
            <h2 class="h6 mt-4 mb-2" style="color: var(--rose); letter-spacing: .04em; text-transform: uppercase;">{{ $label }}</h2>
            <div class="table-responsive st-card">
                <table class="table table-sm table-bordered st-size-table mb-0">
                    <thead>
                        <tr>@foreach ($sets[$key]['headers'] as $header)<th>{{ $header }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach ($sets[$key]['rows'] as $row)
                            <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="st-card p-4 mt-4">
            <h2 class="h6">Masih ragu?</h2>
            <p class="small text-muted-2 mb-0">
                Tim kami siap membantu memilih ukuran yang paling pas untuk Anda —
                @if (config('pos.store.phone'))
                    @php($wa = '62'.ltrim(preg_replace('/\D+/', '', (string) config('pos.store.phone')), '0'))
                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">chat via WhatsApp</a>.
                @else
                    hubungi kami via WhatsApp setelah nomor toko diaktifkan.
                @endif
            </p>
        </div>
    </section>
@endsection
