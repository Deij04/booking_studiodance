<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanceClass extends Model
{
    use HasFactory;

    public const LEVELS = [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        'all_level' => 'All Level',
    ];

    protected $fillable = [
        'name',
        'description',
        'level',
        'price',
        'capacity',
        'image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
