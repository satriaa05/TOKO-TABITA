@extends('layouts.app')

@section('title', 'Audit Log')
@section('page_title', 'Audit Log')

@section('content')
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="q" class="form-label small mb-1">Cari</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="q" name="q" value="{{ $keyword }}"
                               placeholder="Deskripsi atau nama aksi">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <label for="aksi" class="form-label small mb-1">Jenis Aksi</label>
                    <select class="form-select form-select-sm" id="aksi" name="aksi">
                        <option value="">Semua Aksi</option>
                        @foreach ($actions as $item)
                            <option value="{{ $item }}" @selected($action === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="pengguna" class="form-label small mb-1">Pengguna</label>
                    <select class="form-select form-select-sm" id="pengguna" name="pengguna">
                        <option value="">Semua Pengguna</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $userId === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                        <i class="bi bi-funnel"></i> Terapkan
                    </button>
                    @if ($keyword || $action || $userId)
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-light">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-shield-check"></i> Jejak Audit Aktivitas</span>
            <span class="badge text-bg-secondary">{{ $logs->total() }} catatan</span>
        </div>
        <div class="card-body p-0">
            @if ($logs->isEmpty())
                <x-empty-state icon="bi-shield-check" title="Belum ada aktivitas"
                              message="Jejak audit akan terisi otomatis saat ada aktivitas penting." />
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Deskripsi</th>
                                <th>Objek</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="text-muted-2 small text-nowrap">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="fw-semibold">{{ $log->user?->name ?? 'Sistem' }}</td>
                                    <td><code class="small">{{ $log->action }}</code></td>
                                    <td class="small">{{ $log->description }}</td>
                                    <td class="small text-muted-2">
                                        @if ($log->model_type)
                                            {{ $log->subjectType() }} #{{ $log->model_id }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="small text-muted-2">{{ $log->ip_address ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="card-footer">{{ $logs->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
