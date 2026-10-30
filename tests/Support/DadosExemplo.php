<?php

namespace Tests\Support;

use App\Domain\Demandas\AcaoDados;
use App\Domain\Demandas\AdiamentoDados;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\EtapaDados;
use App\Domain\Shared\Fuso;
use Carbon\CarbonImmutable;

/** Monta os retratos de domínio do Apêndice A direto do JSON, sem banco. */
final class DadosExemplo
{
    /** @return array<string, array<string, mixed>> documentos por id legado */
    public static function documentos(): array
    {
        $docs = json_decode(file_get_contents(__DIR__.'/../../database/data/dados-exemplo.json'), true);

        return array_column($docs, null, 'id');
    }

    /** @return array<string, DemandaDados> por id legado (t01…t10); responsavelId = nome */
    public static function demandas(): array
    {
        return array_map(fn (array $d) => self::paraDados($d), self::documentos());
    }

    public static function paraDados(array $d): DemandaDados
    {
        $data = fn (?string $v) => $v === null || $v === '' ? null : CarbonImmutable::parse($v, Fuso::TZ);

        return new DemandaDados(
            id: $d['id'],
            entrada: $data($d['entrada']),
            cliente: $d['cliente'],
            descricao: $d['descricao'],
            canal: $d['canal'],
            setor: $d['setor'],
            responsavelId: $d['responsavel'] ?: null,
            responsavelNome: $d['responsavel'] ?: null,
            acordoEm: $data($d['acordoEm']),
            prazo: $data($d['prazo']),
            primeiroHorario: $d['primeiroHorario'],
            fim: $data($d['fim']),
            conclusao: $d['conclusao'] ?: null,
            observacoes: $d['obs'] ?: null,
            adiamentos: array_map(fn ($a) => new AdiamentoDados($data($a['de']), $data($a['para']), $data($a['em']), $a['motivo']), $d['adiamentos']),
            etapas: array_map(fn ($e) => new EtapaDados($e['txt'], $e['feita'], $data($e['em'])), $d['etapas']),
            acoes: array_map(fn ($a) => new AcaoDados($data($a['em']), $a['txt']), $d['acoes']),
        );
    }

    public static function agora(string $momento = '2026-10-28 19:30'): CarbonImmutable
    {
        return CarbonImmutable::parse($momento, Fuso::TZ);
    }
}
