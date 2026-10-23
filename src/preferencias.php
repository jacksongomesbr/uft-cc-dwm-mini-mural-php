<?php

declare(strict_types=1);

// A ordem do mural é uma preferência de quem usa o navegador. Sem login, o
// servidor não distingue as pessoas, por isso ela fica em cookie.

const COOKIE_DA_ORDEM = 'mini_mural_ordem';

const ORDENS = [
    'mais-recentes' => 'Mais recentes primeiro',
    'mais-antigos'  => 'Mais antigos primeiro',
];

/** Lê a ordem do cookie. Valor ausente ou desconhecido vira a ordem padrão. */
function lerOrdem(): string
{
    $valor = $_COOKIE[COOKIE_DA_ORDEM] ?? '';

    return $valor === 'mais-antigos' ? 'mais-antigos' : 'mais-recentes';
}

/** Grava a ordem em um cookie que dura um ano. */
function salvarOrdem(string $ordem): void
{
    setcookie(COOKIE_DA_ORDEM, $ordem, [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function ordemValida(string $ordem): bool
{
    return array_key_exists($ordem, ORDENS);
}
