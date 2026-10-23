<?php

declare(strict_types=1);

// Todo o SQL de recados mora neste arquivo. As páginas chamam estas funções
// e não escrevem consultas.
//
// Um recado é um array com as chaves id, texto, criado_em, status, publico e
// autor (o nome de quem publicou).

// Colunas que toda consulta de recado devolve. O JOIN traz o nome do autor da
// tabela usuarios.
const COLUNAS_DO_RECADO = 'recados.id, recados.texto, recados.criado_em,
       recados.status, recados.publico, usuarios.nome AS autor
  FROM recados
  JOIN usuarios ON usuarios.id = recados.autor_id';

/**
 * Lista todos os recados na ordem escolhida.
 *
 * A ordem vem de uma lista fechada: o valor do cookie decide entre duas
 * strings fixas e nunca entra no SQL.
 */
function listarRecados(PDO $pdo, string $ordem): array
{
    $direcao = $ordem === 'mais-antigos' ? 'ASC' : 'DESC';

    $sql = 'SELECT ' . COLUNAS_DO_RECADO
        . " ORDER BY recados.criado_em $direcao, recados.id $direcao";

    return $pdo->query($sql)->fetchAll();
}

/** Devolve o recado com esse id, ou null se ele não existe. */
function buscarRecado(PDO $pdo, int $id): ?array
{
    $consulta = $pdo->prepare(
        'SELECT ' . COLUNAS_DO_RECADO . ' WHERE recados.id = :id'
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
function publicarRecado(PDO $pdo, string $texto, int $autorId, bool $publico): int
{
    $consulta = $pdo->prepare(
        'INSERT INTO recados (texto, autor_id, publico)
         VALUES (:texto, :autor_id, :publico) RETURNING id'
    );
    $consulta->bindValue('texto', $texto);
    $consulta->bindValue('autor_id', $autorId, PDO::PARAM_INT);
    // PARAM_BOOL faz o PDO enviar true/false, e não "1" ou "".
    $consulta->bindValue('publico', $publico, PDO::PARAM_BOOL);
    $consulta->execute();

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
