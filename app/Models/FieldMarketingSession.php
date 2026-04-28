<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldMarketingSession extends Model
{
    protected $fillable = [
        'session_number', 'officer_id', 'area', 'date', 'notes', 'status', 'created_by',
    ];

    protected $casts = ['date' => 'date'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($session) {
            if (empty($session->session_number)) {
                $session->session_number = self::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        $prefix = 'FS-' . now()->format('Ym') . '-';
        $last = self::where('session_number', 'like', $prefix . '%')
            ->orderBy('session_number', 'desc')->first();
        $seq = $last ? (int) substr($last->session_number, -3) + 1 : 1;
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function officer()
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visits()
    {
        return $this->hasMany(FieldMarketingVisit::class, 'session_id');
    }
}
