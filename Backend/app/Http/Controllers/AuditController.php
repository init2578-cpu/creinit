<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')
            ->orderByDesc('created_at');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by event type
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by keyword in description
        if ($request->filled('search')) {
            $query->where('description', 'ilike', '%' . $request->search . '%');
        }

        $logs = $query->paginate(50)->withQueryString();

        // Map to safe DTO
        $logs->getCollection()->transform(function ($log) {
            // Derive a clean short label from auditable_type FQCN
            $auditableName = $log->auditable_name; // e.g. "Attendance"

            // If auditable_type is null but description contains ":" we can extract from description
            if ($auditableName === '—' && str_contains($log->description ?? '', ' : ')) {
                $auditableName = trim(explode(' : ', $log->description)[1] ?? '—');
            }

            return [
                'id'             => $log->id,
                'event'          => $log->event,
                'description'    => $log->description,
                'auditable_name' => $auditableName,
                'auditable_id'   => $log->auditable_id,
                'ip_address'     => $log->ip_address,
                'url'            => $log->url,
                'method'         => $log->method,
                'old_values'     => $log->old_values,
                'new_values'     => $log->new_values,
                'created_at'     => $log->created_at?->format('d/m/Y H:i:s'),
                'user'           => $log->user ? [
                    'id'   => $log->user->id,
                    'name' => $log->user->name,
                    'profile_photo_url' => $log->user->profile_photo_url ?? null,
                    'roles' => $log->user->getRoleNames()->toArray(),
                ] : null,
            ];
        });

        // Stats totals
        $totals = AuditLog::selectRaw('event, count(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event');

        // Users list for filter dropdown
        $users = User::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Audit/Index', [
            'logs'    => $logs,
            'totals'  => $totals,
            'users'   => $users,
            'filters' => $request->only(['user_id', 'event', 'date_from', 'date_to', 'search']),
        ]);
    }
}
