<?php

namespace App\Policies;

use App\Models\SupportThread;
use App\Models\User;

class SupportThreadPolicy
{
  public function view(User $user, SupportThread $thread): bool {
    return $user->id === $thread->customer_id || $user->id === $thread->admin_id || $user->hasRole('admin');
  }

  public function update(User $user, SupportThread $thread): bool {
    return $user->hasRole('admin') || $user->id === $thread->customer_id;
  }

  public function list(User $user): bool {
    return true; // list endpoint içinde filtre uygulayacağız
  }
}