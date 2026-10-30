<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\AplicacaoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SenhaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ExportacaoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Acesso (seção 4.3)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/login', [LoginController::class, 'entrar'])->middleware('throttle:20,1');
    Route::get('/esqueci', [SenhaController::class, 'esqueciForm'])->name('senha.esqueci');
    Route::post('/esqueci', [SenhaController::class, 'esqueciEnviar'])->middleware('throttle:6,1');
    Route::get('/redefinir/{token}', [SenhaController::class, 'redefinirForm'])->name('password.reset');
    Route::post('/redefinir', [SenhaController::class, 'redefinirSalvar'])->name('senha.redefinir');
    Route::get('/convite/{token}', [SenhaController::class, 'conviteForm'])->name('convite.aceitar');
    Route::post('/convite', [SenhaController::class, 'conviteSalvar'])->name('convite.salvar');
});

Route::middleware(['auth', 'ativo'])->group(function () {
    Route::post('/logout', [LoginController::class, 'sair'])->name('logout');
    Route::get('/trocar-senha', [SenhaController::class, 'trocarForm'])->name('senha.trocar');
    Route::post('/trocar-senha', [SenhaController::class, 'trocarSalvar']);

    Route::middleware('senha-trocada')->group(function () {
        Route::redirect('/', '/relatorio');
        Route::get('/relatorio', [AplicacaoController::class, 'mostrar'])->name('relatorio');
        Route::get('/tarefas', [AplicacaoController::class, 'mostrar'])->name('tarefas');
        Route::get('/equipe', [AplicacaoController::class, 'mostrar'])->name('equipe');
        Route::get('/ajuda', [AplicacaoController::class, 'ajuda'])->name('ajuda');

        Route::get('/relatorio-dia/pdf', [ExportacaoController::class, 'pdf'])->name('relatorio.pdf');
        Route::get('/demandas/export.csv', [ExportacaoController::class, 'csv'])->name('demandas.csv');

        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');

        Route::middleware('can:gerir-usuarios')->group(function () {
            Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
            Route::get('/usuarios/novo', [UsuarioController::class, 'create'])->name('usuarios.create');
            Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
            Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
            Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
            Route::post('/usuarios/{usuario}/situacao', [UsuarioController::class, 'alternarAtivo'])->name('usuarios.situacao');
            Route::post('/usuarios/{usuario}/convite', [UsuarioController::class, 'reenviarConvite'])->name('usuarios.convite');
        });

        // API JSON usada pela interface (seção 11.1)
        Route::prefix('api')->group(function () {
            Route::get('/sessao', [Api\SessaoController::class, 'sessao']);
            Route::get('/versao', [Api\SessaoController::class, 'versao']);

            Route::get('/relatorio-dia', [Api\RelatorioController::class, 'dia']);
            Route::get('/relatorio-dia/texto', [Api\RelatorioController::class, 'texto']);
            Route::get('/equipe', [Api\EquipeController::class, 'index']);

            Route::get('/clientes', [Api\ClienteController::class, 'index']);
            Route::post('/clientes', [Api\ClienteController::class, 'store']);

            Route::get('/demandas', [Api\DemandaController::class, 'index']);
            Route::post('/demandas', [Api\DemandaController::class, 'store']);
            Route::get('/demandas/{id}', [Api\DemandaController::class, 'show']);
            Route::patch('/demandas/{id}', [Api\DemandaController::class, 'update']);
            Route::delete('/demandas/{id}', [Api\DemandaController::class, 'destroy']);

            Route::post('/demandas/{id}/adiar', [Api\CicloDemandaController::class, 'adiar']);
            Route::post('/demandas/{id}/finalizar', [Api\CicloDemandaController::class, 'finalizar']);
            Route::post('/demandas/{id}/reabrir', [Api\CicloDemandaController::class, 'reabrir']);
            Route::post('/demandas/{id}/etapas', [Api\CicloDemandaController::class, 'adicionarEtapa']);
            Route::patch('/etapas/{id}', [Api\CicloDemandaController::class, 'marcarEtapa']);
            Route::post('/demandas/{id}/acoes', [Api\CicloDemandaController::class, 'registrarAcao']);
        });
    });
});
