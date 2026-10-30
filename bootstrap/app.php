<?php

use App\Application\Excecoes\ConflitoDeVersao;
use App\Application\Excecoes\RegraViolada;
use App\Http\Middleware\ExigeTrocaDeSenha;
use App\Http\Middleware\SomenteAtivos;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // A Render termina o HTTPS no balanceador.
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/relatorio');
        $middleware->alias([
            'ativo' => SomenteAtivos::class,
            'senha-trocada' => ExigeTrocaDeSenha::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $json = fn (Request $r) => $r->is('api/*') || $r->expectsJson();

        $exceptions->render(fn (ConflitoDeVersao $e, Request $r) => $json($r)
            ? response()->json(['message' => $e->getMessage(), 'conflito' => true], 409) : null);

        $exceptions->render(fn (RegraViolada $e, Request $r) => $json($r)
            ? response()->json(['message' => $e->getMessage()], 422) : null);

        $exceptions->render(fn (AccessDeniedHttpException|AuthorizationException $e, Request $r) => $json($r)
            ? response()->json(['message' => 'Você não tem permissão para esta ação.'], 403) : null);

        $exceptions->render(fn (AuthenticationException $e, Request $r) => $json($r)
            ? response()->json(['message' => 'Sua sessão terminou. Entre novamente.'], 401) : null);

        $exceptions->render(fn (NotFoundHttpException|ModelNotFoundException $e, Request $r) => $json($r)
            ? response()->json(['message' => 'Tarefa não encontrada. Ela pode ter sido excluída.'], 404) : null);
    })->create();
