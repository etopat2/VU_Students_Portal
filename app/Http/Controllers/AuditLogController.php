<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): View|Response
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date' => ['nullable', 'date'],
        ]);
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['user_id'] ?? null, fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['date'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', $date))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $page = view('admin.audit-logs.index', compact('logs', 'filters'));

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', 'Audit logs');
        }

        return $page;
    }
}
