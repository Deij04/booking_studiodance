<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassPayment extends Model
{
    protected $fillable = ['class_registration_id', 'payment_method', 'payment_type', 'payment_proof', 'notes', 'verified_by', 'verified_by_name', 'verified_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function classRegistration()
    {
        return $this->belongsTo(ClassRegistration::class);
    }
}
