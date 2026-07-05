<?php

namespace App\Models;

use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TechnicianPayoutRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'technician_id',
        'total_amount',
        'requests_count',
        'status',
        'notes',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'processed_at' => 'datetime',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function earnings()
    {
        return $this->hasMany(TechnicianEarning::class, 'payout_request_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approve(?int $processedById = null, ?string $adminNotes = null): void
    {
        DB::transaction(function () use ($processedById, $adminNotes) {
            $this->earnings()->update(['status' => 'paid']);

            $this->update([
                'status' => 'approved',
                'admin_notes' => $adminNotes,
                'processed_by' => $processedById,
                'processed_at' => now(),
            ]);
        });

        NotificationService::notifyTechnicianTranslated(
            $this->technician_id,
            'notifications.technician.payout_approved',
            ['id' => $this->id, 'amount' => number_format($this->total_amount, 2)],
            null
        );
    }

    public function reject(?int $processedById = null, ?string $adminNotes = null): void
    {
        DB::transaction(function () use ($processedById, $adminNotes) {
            $this->earnings()->update([
                'status' => 'pending',
                'payout_request_id' => null,
            ]);

            $this->update([
                'status' => 'rejected',
                'admin_notes' => $adminNotes,
                'processed_by' => $processedById,
                'processed_at' => now(),
            ]);
        });

        NotificationService::notifyTechnicianTranslated(
            $this->technician_id,
            'notifications.technician.payout_rejected',
            ['id' => $this->id],
            null
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
