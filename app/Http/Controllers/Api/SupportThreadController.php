<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SupportThreadController extends Controller
{
    public function index(Request $req)
    {
        $user = $req->user();

        // Admin tespiti (Spatie varsa/yoksa ve pivot varsa çalışır)
        $isAdmin =
            (method_exists($user, 'hasRole') && $user->hasRole('admin')) ||
            (method_exists($user, 'roles') && $user->roles()->whereIn('name', ['admin'])->exists());

        $q = SupportThread::query()
            ->with(['customer','admin'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at');

        if ($isAdmin) {
            if ($req->filled('status'))   $q->where('status',   $req->input('status'));   // ← input()
            if ($req->filled('priority')) $q->where('priority', $req->input('priority')); // ← input()
        } else {
            $q->where('customer_id', $user->id);
        }

        return $q->paginate(20);
    }

    public function store(Request $req)
    {
        $user = $req->user();

        // 1) Müşterinin en son thread’i (duruma bakmadan) — varsa onu kullan
        $existing = SupportThread::where('customer_id', $user->id)
            ->orderByDesc('updated_at')
            ->first();

        if ($existing) {
            if (!$existing->admin_id) {
                $existing->admin_id = $this->resolveDefaultAdminId();
                $existing->save();
            }
            return $existing->load(['customer','admin']);
        }

        // 2) Hiç yoksa yeni oluştur ve varsayılan admini ata
        $thread = SupportThread::create([
            'customer_id'     => $user->id,
            'admin_id'        => $this->resolveDefaultAdminId(), // ✅ otomatik admin
            'status'          => 'open',
            'priority'        => $req->input('priority', 'normal'), // ← input()
            'last_message_at' => now(),
        ]);

        return $thread->load(['customer','admin']);
    }

    /** Varsayılan admin seçimi — Spatie / pivot / tek kolon hepsini destekler. */
   private function resolveDefaultAdminId(): ?int
  {
      // 0) ENV ile sabit ADMIN ID (öncelik)
      if (($id = (int) env('SUPPORT_DEFAULT_ADMIN_ID', 0)) > 0) {
          $u = \App\Models\User::find($id);
          if ($u) return $u->id;
      }

      // i) ENV ile sabit e-posta (opsiyonel)
      if ($email = env('SUPPORT_DEFAULT_ADMIN')) {
          $u = \App\Models\User::where('email', $email)->first();
          if ($u) return $u->id;
      }

      // ii) users.role kolonu varsa
      if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
          $u = \App\Models\User::where('role','admin')->first();
          if ($u) return $u->id;
      }

      // iii) Spatie varsa
      if (method_exists(\App\Models\User::class, 'role')) {
          $u = \App\Models\User::role('admin')->first();
          if ($u) return $u->id;
      }

      // iv) Pivot (roles ilişkisi) varsa
      if (method_exists(new \App\Models\User, 'roles')) {
          $u = \App\Models\User::whereHas('roles', function ($q) {
              $q->whereIn('name',['admin'])->orWhereIn('slug',['admin']);
          })->first();
          if ($u) return $u->id;
      }

      return null;
  }

    public function show(Request $req, SupportThread $thread)
    {
        $this->authorize('view', $thread);
        return $thread->load(['customer','admin']);
    }

    public function update(Request $req, SupportThread $thread)
    {
        $this->authorize('update', $thread);

        $data = $req->validate([
            'status'   => [Rule::in(['open','pending','resolved','closed'])],
            'priority' => [Rule::in(['low','normal','high'])],
            'admin_id' => ['nullable','integer','exists:users,id'],
        ]);

        $thread->fill($data)->save();

        return $thread->fresh(['customer','admin']);
    }
}