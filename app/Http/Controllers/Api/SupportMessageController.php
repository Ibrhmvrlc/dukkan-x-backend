<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Notifications\SupportNewMessageToAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class SupportMessageController extends Controller
{
  public function index(Request $req, SupportThread $thread) {
    $this->authorize('view', $thread);

    return $thread->messages()
    ->with('sender:id,name,email')
    ->orderBy('created_at', 'asc')   // ✅ yönü belirt
    ->paginate(50);
  }

  public function store(Request $req, SupportThread $thread) {
    $this->authorize('update', $thread);

    $data = $req->validate([
      'body' => ['required','string','max:10000'],
      'importance' => [Rule::in(['info','warning','critical'])],
    ]);

    $msg = SupportMessage::create([
      'thread_id' => $thread->id,
      'sender_id' => $req->user()->id,
      'body' => $data['body'],
      'importance' => $data['importance'] ?? 'info',
    ]);

    $thread->update(['last_message_at' => now(), 'status' => $thread->status === 'resolved' ? 'open' : $thread->status]);

    // Kullanıcıdan admin’e giden mesajı maille
    $senderIsAdmin = $req->user()->hasRole('admin');
    if (!$senderIsAdmin) {
      // Öncelik: atanmış admin
      $targets = [];
      if ($thread->admin_id && $thread->admin?->email) {
          $targets[] = $thread->admin->email;
      } else {
          // Varsayılan admin ID (env veya 1)
          $defaultAdmin = \App\Models\User::find((int) env('SUPPORT_DEFAULT_ADMIN_ID', 1));
          if ($defaultAdmin && $defaultAdmin->email) {
              $targets[] = $defaultAdmin->email;
          }
      }
      $notifiables = \App\Models\User::whereIn('email', $targets)->get();
      if ($notifiables->count() > 0) {
        Notification::send($notifiables, new SupportNewMessageToAdmin($msg));
      }
    }

    return $msg->load('sender:id,name,email');
  }

  public function markRead(Request $req, SupportMessage $message) {
    $thread = $message->thread;
    $this->authorize('view', $thread);

    if (!$message->read_at) {
      $message->update(['read_at' => now()]);
    }
    return $message->fresh();
  }
}