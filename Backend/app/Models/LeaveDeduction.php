<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class LeaveDeduction extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'created_by',
        'reason_type',
        'unit',
        'amount',
        'days_deducted',
        'motif',
        'date_incident',
    ];

    protected $casts = [
        'date_incident' => 'date',
        'amount' => 'float',
        'days_deducted' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
