<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
  protected $fillable = ['thread_id','sender_id','body','importance','read_at'];

  protected $casts = [
    'read_at' => 'datetime',
  ];

  public function thread(): BelongsTo { return $this->belongsTo(SupportThread::class); }
  public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
}
