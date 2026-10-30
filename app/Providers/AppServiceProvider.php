<?php

namespace App\Providers;

use App\Domain\Shared\Relogio;
use App\Domain\Shared\RelogioFixo;
use App\Domain\Shared\RelogioSistema;
use App\Models\Demanda;
use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Relogio::class, function ($app) {
            $fixo = config('escritorio.relogio_fixo');

            return $fixo && ! $app->isProduction() ? new RelogioFixo($fixo) : new RelogioSistema;
        });
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Senha mínima de 8 caracteres, com letras e números (seção 4.3).
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Matriz de permissões (seção 4.2): só o gestor exclui demandas e gerencia usuários.
        Gate::define('excluir-demanda', fn (Usuario $u, ?Demanda $d = null) => $u->ehGestor());
        Gate::define('gerir-usuarios', fn (Usuario $u) => $u->ehGestor());
    }
}
