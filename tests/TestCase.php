<?php

namespace Tests;

use App\Domain\Shared\Relogio;
use App\Domain\Shared\RelogioFixo;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Momento de referência da seção 14: quarta-feira, 28/10/2026, 19h30. */
    public const MOMENTO_SECAO_14 = '2026-10-28 19:30';

    protected function fixarRelogio(string $momento = self::MOMENTO_SECAO_14): void
    {
        $this->app->instance(Relogio::class, new RelogioFixo($momento));
    }
}
