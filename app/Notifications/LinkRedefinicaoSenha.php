<?php

namespace App\Notifications;

use App\Models\Usuario;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LinkRedefinicaoSenha extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Usuario $usuario): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinição de senha – Prazo Certo')
            ->greeting('Olá, '.$usuario->nome)
            ->line('Recebemos um pedido para criar uma nova senha de acesso ao Controle de Demandas.')
            ->action('Criar nova senha', route('password.reset', ['token' => $this->token, 'email' => $usuario->email]))
            ->line('O link vale por 1 hora e só pode ser usado uma vez.')
            ->line('Se você não pediu, ignore este e-mail.')
            ->salutation('Bitarães Assessoria e Consultoria Contábil');
    }
}
