<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class BookingSetting extends Model
{
    use HasFactory;
    protected $fillable = ['kind', 'studio_id', 'date', 'start_time', 'end_time', 'status', 'block_reason', 'opens_at', 'closes_at'];
    protected $attributes = ['kind' => 'block', 'status' => 'blocked'];
    public function studio() { return $this->belongsTo(Studio::class); }
    public function scopeBlocked($query) { return $query->where('kind', 'block')->where('status', 'blocked'); }
}
