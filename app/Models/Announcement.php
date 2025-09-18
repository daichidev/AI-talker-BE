<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title','content','type','status','start_date','end_date',
        'image_url','created_by','send_push'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'send_push' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status','published')
                     ->where(function($q){ $q->whereNull('start_date')->orWhere('start_date','<=',now()); })
                     ->where(function($q){ $q->whereNull('end_date')->orWhere('end_date','>=',now()); });
    }
}
