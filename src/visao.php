<?php

declare(strict_types=1);

/**
 * Escapa um texto para dentro do HTML.
 *
 * Todo dado que veio do usuário ou do banco passa por aqui antes de ir para
 * a página. Sem isso, um recado com "<script>" seria executado no navegador
 * de quem abrir o mural.
 */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

/** "1 publicado", "2 publicados". */
function plural(int $quantidade, string $singular, string $plural): string
{
    return $quantidade === 1 ? "1 $singular" : "$quantidade $plural";
}

/** Imprime o topo comum: <head>, título e navegação. */
function cabecalho(string $titulo, string $paginaAtual): void
{
    require __DIR__ . '/templates/cabecalho.php';
}

/** Imprime o fim comum da página. */
function rodape(): void
{
    require __DIR__ . '/templates/rodape.php';
}

/** Responde 404 com a página de recado ausente e encerra. */
function recadoNaoEncontrado(): never
{
    http_response_code(404);
    require __DIR__ . '/templates/nao-encontrado.php';
    exit;
}

/** Responde 403 com uma página que explica a recusa e encerra. */
function acessoNegado(string $mensagem): never
{
    http_response_code(403);
    require __DIR__ . '/templates/proibido.php';
    exit;
}
