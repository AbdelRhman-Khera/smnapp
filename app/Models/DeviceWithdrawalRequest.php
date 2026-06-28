<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeviceWithdrawalRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public const STATUS_PENDING_CUSTOMER_APPROVAL = 'pending_customer_approval';
    public const STATUS_APPROVED_BY_CUSTOMER = 'approved_by_customer';
    public const STATUS_REJECTED_BY_CUSTOMER = 'rejected_by_customer';
    public const STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN = 'assigned_to_delivery_technician';
    public const STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN = 'received_by_delivery_technician';
    public const STATUS_DELIVERED_TO_BRANCH = 'delivered_to_branch';
    public const STATUS_RECEIVED_BY_BRANCH = 'received_by_branch';
    public const STATUS_UNDER_REPAIR = 'under_repair';
    public const STATUS_REPAIR_COMPLETED = 'repair_completed';
    public const STATUS_FOLLOW_UP_REQUEST_CREATED = 'follow_up_request_created';
    public const STATUS_DELIVERED_TO_CUSTOMER = 'delivered_to_customer';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'maintenance_request_id',
        'customer_id',
        'technician_id',
        'handoff_technician_id',
        'branch_id',
        'received_by_user_id',
        'follow_up_maintenance_request_id',
        'status',
        'customer_decision_notes',
        'technician_notes',
        'handoff_notes',
        'branch_notes',
        'workshop_notes',
        'customer_delivery_notes',
        'customer_decision_at',
        'assigned_to_handoff_technician_at',
        'received_by_handoff_technician_at',
        'delivered_to_branch_at',
        'received_by_branch_at',
        'repair_started_at',
        'repair_completed_at',
        'delivered_to_customer_at',
        'customer_received_at',
    ];

    protected $casts = [
        'customer_decision_notes' => 'array',
        'customer_decision_at' => 'datetime',
        'assigned_to_handoff_technician_at' => 'datetime',
        'received_by_handoff_technician_at' => 'datetime',
        'delivered_to_branch_at' => 'datetime',
        'received_by_branch_at' => 'datetime',
        'repair_started_at' => 'datetime',
        'repair_completed_at' => 'datetime',
        'delivered_to_customer_at' => 'datetime',
        'customer_received_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING_CUSTOMER_APPROVAL => 'Pending Customer Approval',
            self::STATUS_APPROVED_BY_CUSTOMER => 'Approved By Customer',
            self::STATUS_REJECTED_BY_CUSTOMER => 'Rejected By Customer',
            self::STATUS_ASSIGNED_TO_DELIVERY_TECHNICIAN => 'Assigned To Delivery Technician',
            self::STATUS_RECEIVED_BY_DELIVERY_TECHNICIAN => 'Received By Delivery Technician',
            self::STATUS_DELIVERED_TO_BRANCH => 'Delivered To Branch',
            self::STATUS_RECEIVED_BY_BRANCH => 'Received By Branch',
            self::STATUS_UNDER_REPAIR => 'Under Repair',
            self::STATUS_REPAIR_COMPLETED => 'Repair Completed',
            self::STATUS_FOLLOW_UP_REQUEST_CREATED => 'Follow-up Request Created',
            self::STATUS_DELIVERED_TO_CUSTOMER => 'Delivered To Customer',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELED => 'Canceled',
        ];
    }

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function handoffTechnician()
    {
        return $this->belongsTo(Technician::class, 'handoff_technician_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function followUpMaintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class, 'follow_up_maintenance_request_id');
    }

    public function items()
    {
        return $this->hasMany(DeviceWithdrawalItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
