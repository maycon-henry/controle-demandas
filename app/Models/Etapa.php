<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestamptz;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Etapa extends Model
{
    use HasUuids, UsaTimestamptz;

    public $timestamps = false;

    protected $table = 'etapas';

    protected $fillable = ['demanda_id', 'ordem', 'texto', 'feita', 'feita_em', 'usuario_id'];

    protected function casts(): array
    {
        return ['feita' => 'boolean', 'feita_em' => 'immutable_datetime', 'ordem' => 'integer'];
    }

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class);
    }
}
