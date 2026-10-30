<?php

namespace App\Notifications;

use App\Models\Usuario;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConviteAcesso extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Usuario $usuario): MailMessage
    {
        return (new MailMessage)
            ->subject('Seu acesso ao Controle de Demandas – Prazo Certo')
            ->greeting('Olá, '.$usuario->nome)
            ->line('Você foi cadastrado no Controle de Demandas da Bitarães Assessoria e Consultoria Contábil.')
            ->action('Criar minha senha', route('convite.aceitar', ['token' => $this->token, 'email' => $usuario->email]))
            ->line('O link vale por 7 dias e só pode ser usado uma vez.')
            ->salutation('Bitarães Assessoria e Consultoria Contábil');
    }
}
