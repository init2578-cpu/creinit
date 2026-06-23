<?php

namespace App\Traits;

use App\Models\AuditLog;

/**
 * Attach this trait to any Eloquent model to automatically log
 * created / updated / deleted events.
 *
 * Usage:  use Auditable;
 *
 * Override $auditableFields to restrict which columns are logged.
 */
trait Auditable
{
    // Columns to exclude from diff (passwords, tokens, etc.)
    protected array $auditExclude = ['password', 'remember_token', 'two_factor_secret'];

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAudit('created', $model->auditDescription('created'));
        });

        static::updated(function ($model) {
            $model->writeAudit('updated', $model->auditDescription('updated'), null, $model->getAuditDirty());
        });

        static::deleted(function ($model) {
            $model->writeAudit('deleted', $model->auditDescription('deleted'));
        });
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    protected function writeAudit(string $event, string $description, ?array $oldValues = null, ?array $newValues = null): void
    {
        $user = request()?->user();

        AuditLog::write(
            event: $event,
            description: $description,
            userId: $user?->id,
            auditableType: get_class($this),
            auditableId: $this->getKey(),
            oldValues: $oldValues,
            newValues: $newValues,
        );
    }

    protected function auditDescription(string $event): string
    {
        $user = request()?->user()?->name ?? 'Système';
        $modelName = class_basename($this);
        $id = $this->getKey();

        return match ($event) {
            'created' => "{$user} a créé {$modelName} #{$id}",
            'updated' => "{$user} a modifié {$modelName} #{$id}",
            'deleted' => "{$user} a supprimé {$modelName} #{$id}",
            default   => "{$user} — {$event} {$modelName} #{$id}",
        };
    }

    protected function getAuditDirty(): array
    {
        $dirty = $this->getDirty();
        // Remove sensitive fields
        foreach ($this->auditExclude as $field) {
            unset($dirty[$field]);
        }
        $original = collect($this->getOriginal())->only(array_keys($dirty))->toArray();

        return ['old' => $original, 'new' => $dirty];
    }
}
