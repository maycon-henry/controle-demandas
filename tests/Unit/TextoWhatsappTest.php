<?php

namespace Tests\Unit;

use App\Application\Relatorio\TextoWhatsapp;
use App\Domain\Relatorio\GeradorRelatorioDia;
use PHPUnit\Framework\TestCase;
use Tests\Support\DadosExemplo;

/** Seção 8.3: texto idêntico ao modelo (T-19). */
class TextoWhatsappTest extends TestCase
{
    public function test_texto_identico_ao_exemplo(): void
    {
        $r = (new GeradorRelatorioDia)->gerar(DadosExemplo::demandas(), '2026-10-28', null, DadosExemplo::agora());

        $this->assertSame(
            rtrim(file_get_contents(__DIR__.'/../Fixtures/texto-whatsapp-exemplo.txt')),
            (new TextoWhatsapp)->gerar($r, null),
        );
    }

    public function test_filtrado_nao_repete_o_responsavel_e_secao_vazia_mostra_traco(): void
    {
        $r = (new GeradorRelatorioDia)->gerar(DadosExemplo::demandas(), '2026-10-28', 'Carla Mendes', DadosExemplo::agora());
        $texto = (new TextoWhatsapp)->gerar($r, 'Carla Mendes');

        $this->assertStringContainsString('*Relatório de demandas – quarta-feira, 28 de outubro de 2026 · Carla Mendes*', $texto);
        $this->assertStringContainsString("*Feito (0)*\n—", $texto);
        $this->assertStringNotContainsString('(Carla Mendes)', $texto);
    }
}
