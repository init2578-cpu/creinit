<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    // -----------------------------------------------------------------------
    // Static helper
    // -----------------------------------------------------------------------

    /**
     * Write an audit entry from anywhere in the codebase.
     */
    public static function write(
        string $event,
        string $description,
        ?int $userId = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        try {
            $request = request();
            static::create([
                'user_id'        => $userId ?? ($request?->user()?->id),
                'event'          => $event,
                'auditable_type' => $auditableType,
                'auditable_id'   => $auditableId,
                'description'    => $description,
                'old_values'     => $oldValues,
                'new_values'     => $newValues,
                'ip_address'     => static::resolveClientIp($request),
                'user_agent'     => $request?->userAgent(),
                'url'            => $request?->fullUrl(),
                'method'         => $request?->method(),
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let audit failures crash the app
            logger()->error('AuditLog::write failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the real client IP address, handling reverse proxies (nginx).
     * Priority: X-Forwarded-For → X-Real-IP → REMOTE_ADDR → $request->ip()
     */
    private static function resolveClientIp($request): ?string
    {
        if (!$request) return null;

        // X-Forwarded-For can contain a chain: "client_ip, proxy1, proxy2"
        // The leftmost IP is the original client
        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));
            $clientIp = $ips[0] ?? null;
            if ($clientIp && filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }

        // X-Real-IP header (set by some nginx configurations)
        $realIp = $request->header('X-Real-IP');
        if ($realIp && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }

        // Standard Laravel resolution (works correctly when TrustProxies is configured)
        return $request->ip();
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    /**
     * Returns a short human-readable model name (e.g. "Attendance" from "App\Models\Attendance").
     */
    public function getAuditableNameAttribute(): string
    {
        if (!$this->auditable_type) return '—';
        $parts = explode('\\', $this->auditable_type);
        return end($parts);
    }
}
