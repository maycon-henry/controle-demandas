<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestampTz('entrada');
            $table->foreignUuid('cliente_id')->constrained('clientes');
            $table->text('descricao');
            $table->text('canal');
            $table->string('setor')->default('Fiscal');
            $table->foreignUuid('responsavel_id')->nullable()->constrained('usuarios');
            $table->timestampTz('acordo_em')->nullable();
            $table->timestampTz('prazo')->nullable();
            $table->boolean('primeiro_horario')->default(false);
            $table->timestampTz('fim')->nullable();
            $table->text('conclusao')->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignUuid('criado_por')->constrained('usuarios');
            // Controle de concorrência otimista (seção 10).
            $table->unsignedInteger('versao')->default(1);
            $table->string('id_legado')->nullable()->unique();
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
            $table->timestampTz('excluido_em')->nullable();

            $table->index('entrada');
            $table->index('fim');
            $table->index('prazo');
            $table->index('responsavel_id');
        });

        Schema::create('adiamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('demanda_id')->constrained('demandas')->cascadeOnDelete();
            $table->timestampTz('de');
            $table->timestampTz('para');
            $table->timestampTz('em');
            $table->text('motivo')->nullable();
            $table->foreignUuid('usuario_id')->constrained('usuarios');

            $table->index(['demanda_id', 'em']);
            $table->index('de');
        });

        Schema::create('etapas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('demanda_id')->constrained('demandas')->cascadeOnDelete();
            $table->unsignedInteger('ordem');
            $table->text('texto');
            $table->boolean('feita')->default(false);
            $table->timestampTz('feita_em')->nullable();
            $table->foreignUuid('usuario_id')->nullable()->constrained('usuarios');

            $table->index(['demanda_id', 'ordem']);
        });

        Schema::create('acoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('demanda_id')->constrained('demandas')->cascadeOnDelete();
            $table->timestampTz('em');
            $table->text('texto');
            $table->foreignUuid('usuario_id')->constrained('usuarios');

            $table->index(['demanda_id', 'em']);
        });

        Schema::create('eventos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('demanda_id')->constrained('demandas')->cascadeOnDelete();
            $table->timestampTz('em');
            $table->text('texto');
            $table->enum('tipo', ['criada', 'editada', 'adiada', 'etapa', 'finalizada', 'reaberta', 'excluida']);
            $table->foreignUuid('usuario_id')->constrained('usuarios');

            $table->index(['demanda_id', 'em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
        Schema::dropIfExists('acoes');
        Schema::dropIfExists('etapas');
        Schema::dropIfExists('adiamentos');
        Schema::dropIfExists('demandas');
    }
};
