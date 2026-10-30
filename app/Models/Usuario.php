<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestamptz;
use App\Notifications\ConviteAcesso;
use App\Notifications\LinkRedefinicaoSenha;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $id
 * @property string $nome
 * @property string $email
 * @property string $perfil
 * @property ?string $setor
 * @property bool $ativo
 * @property bool $deve_trocar_senha
 */
class Usuario extends Authenticatable implements CanResetPassword
{
    use HasUuids, Notifiable, UsaTimestamptz;

    public const PERFIL_GESTOR = 'gestor';

    public const PERFIL_COLABORADOR = 'colaborador';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'usuarios';

    protected $authPasswordName = 'senha_hash';

    protected $fillable = ['nome', 'email', 'senha_hash', 'perfil', 'setor', 'ativo', 'deve_trocar_senha'];

    protected $hidden = ['senha_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'senha_hash' => 'hashed',
            'ativo' => 'boolean',
            'deve_trocar_senha' => 'boolean',
        ];
    }

    public function ehGestor(): bool
    {
        return $this->perfil === self::PERFIL_GESTOR;
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeGestoresAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true)->where('perfil', self::PERFIL_GESTOR);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new LinkRedefinicaoSenha($token));
    }

    public function enviarConvite(string $token): void
    {
        $this->notify(new ConviteAcesso($token));
    }
}
