<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestamptz;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acao extends Model
{
    use HasUuids, UsaTimestamptz;

    public $timestamps = false;

    protected $table = 'acoes';

    protected $fillable = ['demanda_id', 'em', 'texto', 'usuario_id'];

    protected function casts(): array
    {
        return ['em' => 'immutable_datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
