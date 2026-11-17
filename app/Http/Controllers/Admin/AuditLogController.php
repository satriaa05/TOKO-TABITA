<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman jejak audit (aktivitas penting pengguna).
 */
class AuditLogController extends Controller
{
    /**
     * Daftar audit log dengan filter.
     */
    public function index(Request $request): View
    {
        $keyword = $request->query('q');
        $action = $request->query('aksi');
        $userId = $request->query('pengguna');

        $logs = AuditLog::query()
            ->with('user')
            ->when($keyword, function ($q) use ($keyword) {
                $q->where('description', 'like', '%'.$keyword.'%')
                    ->orWhere('action', 'like', '%'.$keyword.'%');
            })
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest('created_at')
            ->latest('id')
            ->paginate(config('pos.per_page'))
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'keyword' => $keyword,
            'action' => $action,
            'userId' => $userId,
            'actions' => AuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }
}
