<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Models\User;
use App\Notifications\SupportNewMessageToAdmin;
use App\Notifications\SupportNewMessageToCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class SupportMessageController extends Controller
{
    public function index(Request $req, SupportThread $thread)
    {
        $this->authorize('view', $thread);

        return $thread->messages()
            ->with('sender:id,name,email')
            ->orderBy('created_at', 'asc')
            ->paginate(50);
    }

    public function store(Request $req, SupportThread $thread)
    {
        $this->authorize('update', $thread);

        $data = $req->validate([
            'body'       => ['required', 'string', 'max:10000'],
            'importance' => [Rule::in(['info', 'warning', 'critical'])],
        ]);

        $msg = SupportMessage::create([
            'thread_id'  => $thread->id,
            'sender_id'  => $req->user()->id,
            'body'       => $data['body'],
            'importance' => $data['importance'] ?? 'info',
        ]);

        // Thread meta güncelle
        $thread->update([
            'last_message_at' => now(),
            'status'          => in_array($thread->status, ['resolved', 'closed'], true) ? 'open' : $thread->status,
        ]);

        // --- KUYRUKLU E-POSTA BİLDİRİMİ ---
        // .env: SUPPORT_MAIL_ENABLED=true ise çalışır (false yaparsan kapatır)
        try {
            if (filter_var(env('SUPPORT_MAIL_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
                $sender  = $req->user();
                $isAdmin =
                    (method_exists($sender, 'hasRole') && $sender->hasRole('admin')) ||
                    (method_exists($sender, 'roles') && $sender->roles()->where('name', 'admin')->exists()) ||
                    (property_exists($sender, 'role') && $sender->role === 'admin') ||
                    ((int) env('SUPPORT_DEFAULT_ADMIN_ID', 0) === (int) $sender->id);

                if ($isAdmin) {
                    // Admin yazdı → müşteriye mail (queued)
                    $to = $thread->customer;
                    if ($to?->email) {
                        $to->notify(new SupportNewMessageToCustomer($msg)); // ShouldQueue sayesinde kuyruklanır
                    }
                } else {
                    // Müşteri yazdı → admin(ler)e mail (queued)
                    if ($thread->admin?->email) {
                        // Atanmış admin varsa doğrudan ona
                        $thread->admin->notify(new SupportNewMessageToAdmin($msg));
                    } else {
                        // Fallback: env ID → rol bazlı tüm adminler
                        $targets = collect();

                        $defaultId = (int) env('SUPPORT_DEFAULT_ADMIN_ID', 0);
                        if ($defaultId > 0) {
                            if ($u = User::find($defaultId)) {
                                $targets->push($u);
                            }
                        }

                        if ($targets->isEmpty()) {
                            if (method_exists(User::class, 'role')) {
                                // Spatie
                                $targets = User::role('admin')->get();
                            } elseif (method_exists(new User, 'roles')) {
                                // Pivot
                                $targets = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
                            } else {
                                // Tek kolon
                                $targets = User::where('role', 'admin')->get();
                            }
                        }

                        if ($targets->isNotEmpty()) {
                            Notification::send($targets, new SupportNewMessageToAdmin($msg));
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Mail gönderimi asla sohbeti bozmasın
            Log::warning('Support mail bildirimi gönderilemedi: ' . $e->getMessage());
        }

        return $msg->load('sender:id,name,email');
    }

    public function markRead(Request $req, SupportMessage $message)
    {
        $thread = $message->thread;
        $this->authorize('view', $thread);

        if (!$message->read_at) {
            $message->update(['read_at' => now()]);
        }

        return $message->fresh();
    }
}