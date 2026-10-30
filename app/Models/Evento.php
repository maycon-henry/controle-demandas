<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestamptz;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Histórico do sistema (seção 5.7). Nunca é editado nem apagado pela interface. */
class Evento extends Model
{
    use HasUuids, UsaTimestamptz;

    public const CRIADA = 'criada';

    public const EDITADA = 'editada';

    public const ADIADA = 'adiada';

    public const ETAPA = 'etapa';

    public const FINALIZADA = 'finalizada';

    public const REABERTA = 'reaberta';

    public const EXCLUIDA = 'excluida';

    public $timestamps = false;

    protected $table = 'eventos';

    protected $fillable = ['demanda_id', 'em', 'texto', 'tipo', 'usuario_id'];

    protected function casts(): array
    {
        return ['em' => 'immutable_datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
