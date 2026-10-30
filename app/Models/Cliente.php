<?php

namespace App\Models;

use App\Models\Concerns\UsaTimestamptz;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $nome
 * @property ?string $cnpj
 * @property bool $ativo
 */
class Cliente extends Model
{
    use HasUuids, UsaTimestamptz;

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'clientes';

    protected $fillable = ['nome', 'cnpj', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function demandas(): HasMany
    {
        return $this->hasMany(Demanda::class);
    }
}
