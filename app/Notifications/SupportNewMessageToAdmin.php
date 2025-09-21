<?php

namespace App\Notifications;

use App\Models\SupportMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportNewMessageToAdmin extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(public SupportMessage $message) {}

  public function via($notifiable): array { return ['mail']; }

  public function toMail($notifiable): MailMessage
  {
    $thread = $this->message->thread;
    $sender = $this->message->sender;

    return (new MailMessage)
      ->subject('Yeni Destek Mesajı')
      ->greeting('Merhaba Admin,')
      ->line("Gönderen: {$sender->name} <{$sender->email}>")
      ->line("Konu ID: #{$thread->id}")
      ->line("Önem: {$this->message->importance}")
      ->line("Mesaj:")
      ->line($this->message->body)
      ->action('Sohbete Git', url("/admin/support/threads/{$thread->id}"))
      ->line('Bu e-posta, sistem tarafından otomatik gönderilmiştir.');
  }
}