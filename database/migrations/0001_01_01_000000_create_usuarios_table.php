<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('nome');
            $table->text('email');
            $table->text('senha_hash');
            $table->enum('perfil', ['gestor', 'colaborador'])->default('colaborador');
            $table->string('setor')->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('deve_trocar_senha')->default(false);
            $table->rememberToken();
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
        });

        DB::statement('ALTER TABLE usuarios ALTER COLUMN email TYPE citext');
        DB::statement('CREATE UNIQUE INDEX usuarios_email_unique ON usuarios (email)');
        DB::statement('CREATE UNIQUE INDEX usuarios_nome_ativos_unique ON usuarios (nome) WHERE ativo');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('convite_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('convite_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
