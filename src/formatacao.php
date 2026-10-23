<?php

declare(strict_types=1);

const MESES = [
    1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
    'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
];

/**
 * Converte o criado_em que vem do banco para o fuso configurado.
 *
 * O PostgreSQL devolve o instante com o deslocamento do fuso da conexão,
 * por exemplo "2026-03-12 17:02:00+00". O DateTimeImmutable entende esse
 * formato, e setTimezone leva o instante para o fuso de quem lê.
 */
function dataLocal(string $criadoEm): DateTimeImmutable
{
    return (new DateTimeImmutable($criadoEm))
        ->setTimezone(new DateTimeZone(date_default_timezone_get()));
}

/** "14:02", usado na lista. */
function formatarHora(string $criadoEm): string
{
    return dataLocal($criadoEm)->format('H:i');
}

/** "12 de março de 2026 às 14:02", usado no detalhe. */
function formatarDataHora(string $criadoEm): string
{
    $data = dataLocal($criadoEm);
    $mes = MESES[(int) $data->format('n')];

    return $data->format('j') . " de $mes de " . $data->format('Y')
        . ' às ' . $data->format('H:i');
}

/** Valor para o atributo datetime da tag <time>. */
function formatarIso(string $criadoEm): string
{
    return dataLocal($criadoEm)->format(DATE_ATOM);
}
