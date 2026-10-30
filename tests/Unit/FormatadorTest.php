<?php

namespace Tests\Unit;

use App\Domain\Shared\Formatador;
use App\Rules\Cnpj;
use PHPUnit\Framework\TestCase;
use Tests\Support\DadosExemplo;

/** Formatos da seção 6.8. */
class FormatadorTest extends TestCase
{
    public function test_datas(): void
    {
        $m = DadosExemplo::agora('2026-10-28 09:05');

        $this->assertSame('28/10/26', Formatador::dataCurta($m));
        $this->assertSame('09h05', Formatador::hora($m));
        $this->assertSame('28/10/26 09h05', Formatador::dataHora($m));
        $this->assertSame('28/10/26 09:05', Formatador::csv($m));
        $this->assertSame('quarta-feira, 28 de outubro de 2026', Formatador::extenso('2026-10-28'));
    }

    public function test_duracoes(): void
    {
        $this->assertSame('1h', Formatador::duracao(20 * 60));
        $this->assertSame('8h', Formatador::duracao(7.5 * 3600));
        $this->assertSame('2,1 dias', Formatador::duracao(50 * 3600 + 600));
        $this->assertSame('12 dias', Formatador::duracao(12 * 86400));
        $this->assertSame('–', Formatador::duracao(null));
    }

    public function test_cnpj(): void
    {
        $this->assertTrue(Cnpj::valido('11222333000181'));
        $this->assertFalse(Cnpj::valido('11222333000182'));
        $this->assertFalse(Cnpj::valido('11111111111111'));
    }
}
