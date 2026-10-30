<?php

namespace Tests\Unit;

use App\Domain\Demandas\AdiamentoDados;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\CalculadoraStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\DadosExemplo;

/** Seção 14.1 e casos de borda de status da 14.4. */
class CalculadoraStatusTest extends TestCase
{
    public static function statusEsperados(): array
    {
        return [
            ['t01', 'Concluída (no prazo)'],
            ['t02', 'Concluída (com atraso)'],
            ['t03', 'Adiada para 29/10/26 (08h00, 1º horário)'],
            ['t04', 'Pendente (atrasada)'],
            ['t05', 'Pendente (no prazo)'],
            ['t06', 'Pendente (no prazo)'],
            ['t07', 'Pendente (no prazo)'],
            ['t08', 'Adiada para 29/10/26 (08h30, 1º horário)'],
            ['t09', 'Concluída (no prazo)'],
            ['t10', 'Pendente (no prazo)'],
        ];
    }

    #[DataProvider('statusEsperados')]
    public function test_status_de_cada_demanda_do_apendice_a(string $id, string $esperado): void
    {
        $status = (new CalculadoraStatus)->calcular(DadosExemplo::demandas()[$id], DadosExemplo::agora());

        $this->assertSame($esperado, $status->completo());
    }

    public function test_sem_prazo_e_nao_finalizada(): void
    {
        $t = new DemandaDados(id: 'x', entrada: DadosExemplo::agora('2026-10-28 08:00'));

        $this->assertSame('Pendente · sem prazo', (new CalculadoraStatus)->calcular($t, DadosExemplo::agora())->exibicao());
    }

    public function test_vence_hoje_vira_atrasada_depois_do_prazo(): void
    {
        $t = new DemandaDados(id: 'x', entrada: DadosExemplo::agora('2026-10-28 08:00'), prazo: DadosExemplo::agora('2026-10-28 17:00'));
        $calc = new CalculadoraStatus;

        $this->assertSame('Pendente · vence hoje', $calc->calcular($t, DadosExemplo::agora('2026-10-28 16:59'))->exibicao());
        $this->assertSame('Pendente · atrasada', $calc->calcular($t, DadosExemplo::agora('2026-10-28 17:01'))->exibicao());
    }

    public function test_adiada_que_passa_do_novo_prazo_volta_a_ser_atrasada(): void
    {
        $t = DadosExemplo::demandas()['t03'];

        $this->assertSame('Pendente · atrasada', (new CalculadoraStatus)->calcular($t, DadosExemplo::agora('2026-10-29 08:01'))->exibicao());
    }

    public function test_adiada_sem_primeiro_horario_nao_mostra_complemento(): void
    {
        $agora = DadosExemplo::agora();
        $t = new DemandaDados(
            id: 'x', entrada: $agora->subDay(), prazo: $agora->addDay()->setTime(10, 0),
            adiamentos: [new AdiamentoDados($agora->subHour(), $agora->addDay()->setTime(10, 0), $agora)],
        );

        $this->assertSame('Adiada para 29/10/26 (10h00)', (new CalculadoraStatus)->calcular($t, $agora)->completo());
    }
}
