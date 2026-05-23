<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CivilCertificateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CivilCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'reference_number',
        'applicant_first_name',
        'applicant_last_name',
        'applicant_cni',
        'data',
        'status',
        'validated_by',
        'validated_at',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'type' => CivilCertificateType::class,
            'data' => 'array',
            'validated_at' => 'datetime',
            'validated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CivilCertificate $cert): void {
            if (empty($cert->uuid)) {
                $cert->uuid = (string) Str::uuid();
            }
        });
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
