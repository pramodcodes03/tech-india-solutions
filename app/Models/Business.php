<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Business extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'slug', 'name', 'legal_name',
        'gst', 'pan', 'cin',
        // Establishment identity (Module E) — heads every statutory register.
        'establishment_code', 'lin', 'employer_name', 'employer_designation',
        'employer_address', 'nature_of_work', 'place_of_work',
        'statutory_state', 'wage_period',
        'address', 'city', 'state', 'pincode', 'country',
        'phone', 'email', 'website',
        'logo',
        // Letterhead Foundation (Module D) — shared by all 42 documents.
        'signature_path', 'seal_path', 'signatory_name', 'signatory_role',
        'letterhead_footer', 'letterhead_enabled',
        'currency_code', 'currency_symbol',
        'invoice_prefix', 'quotation_prefix', 'sales_order_prefix',
        'po_prefix', 'grn_prefix', 'proforma_prefix', 'employee_code_prefix',
        'terms_and_conditions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'letterhead_enabled' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => "Business was {$event}");
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
