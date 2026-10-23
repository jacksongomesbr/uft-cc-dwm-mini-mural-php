<?php

declare(strict_types=1);

// Todo o SQL de recados mora neste arquivo. As páginas chamam estas funções
// e não escrevem consultas.
//
// Um recado é um array com as chaves id, texto, criado_em e status.

/**
 * Lista todos os recados na ordem escolhida.
 *
 * A ordem vem de uma lista fechada: o valor do cookie decide entre duas
 * strings fixas e nunca entra no SQL.
 */
function listarRecados(PDO $pdo, string $ordem): array
{
    $direcao = $ordem === 'mais-antigos' ? 'ASC' : 'DESC';

    $sql = "SELECT id, texto, criado_em, status
              FROM recados
             ORDER BY criado_em $direcao, id $direcao";

    return $pdo->query($sql)->fetchAll();
}

/** Devolve o recado com esse id, ou null se ele não existe. */
function buscarRecado(PDO $pdo, int $id): ?array
{
    $consulta = $pdo->prepare(
        'SELECT id, texto, criado_em, status FROM recados WHERE id = :id'
    );
    $consulta->execute(['id' => $id]);
    $recado = $consulta->fetch();

    return $recado === false ? null : $recado;
}

/**
 * Grava um recado novo e devolve o id gerado pelo banco.
 *
 * Todo recado nasce publicado; data e status vêm dos valores padrão da tabela.
 * Quem chama já validou o texto com validarRecado().
 */
function publicarRecado(PDO $pdo, string $texto): int
{
    $consulta = $pdo->prepare(
        'INSERT INTO recados (texto) VALUES (:texto) RETURNING id'
    );
    $consulta->execute(['texto' => $texto]);

    return (int) $consulta->fetchColumn();
}

/**
 * Arquiva um recado publicado.
 *
 * O filtro por status torna a operação repetível: arquivar duas vezes, ou
 * arquivar um id que não existe, não altera nenhuma linha e não gera erro.
 */
function arquivarRecado(PDO $pdo, int $id): void
{
    $consulta = $pdo->prepare(
        "UPDATE recados SET status = 'arquivado'
          WHERE id = :id AND status = 'publicado'"
    );
    $consulta->execute(['id' => $id]);
}

/** Conta publicados e arquivados de uma lista já carregada. */
function resumirRecados(array $recados): array
{
    $publicados = count(array_filter(
        $recados,
        fn (array $recado): bool => $recado['status'] === 'publicado',
    ));

    return [
        'publicados' => $publicados,
        'arquivados' => count($recados) - $publicados,
    ];
}
