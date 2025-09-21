<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportThread extends Model
{
  protected $fillable = ['customer_id','admin_id','status','priority','last_message_at'];

  public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
  public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_id'); }
  public function messages(): HasMany { return $this->hasMany(SupportMessage::class, 'thread_id'); }
}
