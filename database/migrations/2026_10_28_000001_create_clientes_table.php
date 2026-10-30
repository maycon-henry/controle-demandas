<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('nome');
            $table->string('cnpj', 14)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
        });

        DB::statement('CREATE UNIQUE INDEX clientes_nome_unique ON clientes (lower(nome))');
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
