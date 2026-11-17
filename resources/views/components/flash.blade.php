@php
    $messages = [];

    if (session('success')) {
        $messages[] = ['type' => 'success', 'text' => session('success')];
    }

    if (session('error')) {
        $messages[] = ['type' => 'danger', 'text' => session('error')];
    }

    if (session('warning')) {
        $messages[] = ['type' => 'warning', 'text' => session('warning')];
    }
@endphp

@foreach ($messages as $message)
    <div class="alert alert-{{ $message['type'] }} alert-dismissible fade show d-flex align-items-center gap-2"
         role="alert">
        <i class="bi {{ $message['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
        <div class="flex-grow-1">{{ $message['text'] }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endforeach
