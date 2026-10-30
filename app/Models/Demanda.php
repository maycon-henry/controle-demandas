<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestamptz;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property CarbonImmutable $entrada
 * @property string $cliente_id
 * @property string $descricao
 * @property string $canal
 * @property string $setor
 * @property ?string $responsavel_id
 * @property ?CarbonImmutable $acordo_em
 * @property ?CarbonImmutable $prazo
 * @property bool $primeiro_horario
 * @property ?CarbonImmutable $fim
 * @property ?string $conclusao
 * @property ?string $observacoes
 * @property string $criado_por
 * @property int $versao
 * @property ?string $id_legado
 * @property ?CarbonImmutable $excluido_em
 */
class Demanda extends Model
{
    use HasUuids, UsaTimestamptz;

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'demandas';

    protected $fillable = [
        'entrada', 'cliente_id', 'descricao', 'canal', 'setor', 'responsavel_id', 'acordo_em',
        'prazo', 'primeiro_horario', 'fim', 'conclusao', 'observacoes', 'criado_por', 'versao', 'id_legado',
    ];

    protected function casts(): array
    {
        return [
            'entrada' => 'immutable_datetime',
            'acordo_em' => 'immutable_datetime',
            'prazo' => 'immutable_datetime',
            'fim' => 'immutable_datetime',
            'excluido_em' => 'immutable_datetime',
            'criado_em' => 'immutable_datetime',
            'atualizado_em' => 'immutable_datetime',
            'primeiro_horario' => 'boolean',
            'versao' => 'integer',
        ];
    }

    /** Exclusão lógica: some de telas, relatórios e exportações (seção 5.3). */
    public function scopeAtivas(Builder $query): Builder
    {
        return $query->whereNull('demandas.excluido_em');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsavel_id');
    }

    public function adiamentos(): HasMany
    {
        return $this->hasMany(Adiamento::class)->orderBy('em');
    }

    public function etapas(): HasMany
    {
        return $this->hasMany(Etapa::class)->orderBy('ordem');
    }

    public function acoes(): HasMany
    {
        return $this->hasMany(Acao::class)->orderBy('em');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class)->orderBy('em');
    }
}
