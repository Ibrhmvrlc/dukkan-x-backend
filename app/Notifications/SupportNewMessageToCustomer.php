<?php

namespace App\Notifications;

use App\Models\SupportMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportNewMessageToCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportMessage $message)
    {
        $this->onQueue('mail'); // opsiyonel
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->message->loadMissing(['thread','sender']);
        $thread = $m->thread;

        $impMap = ['info'=>'Bilgi','warning'=>'Uyarı','critical'=>'Kritik'];
        $imp = $impMap[$m->importance] ?? $m->importance;

        return (new MailMessage)
            ->subject("✉️ Destek #{$thread->id} • {$imp} • Yanıtınız var")
            ->greeting("Merhaba {$notifiable->name},")
            ->line('Destek talebinize yeni bir yanıt var.')
            ->line("Önem: {$imp}")
            ->line('Yanıt:')
            ->line($m->body)
            ->line('Bu e-posta otomatik gönderilmiştir.');
    }
}