<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestamptz;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adiamento extends Model
{
    use HasUuids, UsaTimestamptz;

    public $timestamps = false;

    protected $table = 'adiamentos';

    protected $fillable = ['demanda_id', 'de', 'para', 'em', 'motivo', 'usuario_id'];

    protected function casts(): array
    {
        return ['de' => 'immutable_datetime', 'para' => 'immutable_datetime', 'em' => 'immutable_datetime'];
    }

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class);
    }
}
