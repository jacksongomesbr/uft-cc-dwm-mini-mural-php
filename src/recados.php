<?php

declare(strict_types=1);

// Todo o SQL de recados mora neste arquivo. As páginas chamam estas funções
// e não escrevem consultas.
//
// Um recado é um array com as chaves id, texto, criado_em, status,
// arquivado_em, publico, autor_id e autor (o nome de quem publicou).

// Colunas que toda consulta de recado devolve. O JOIN traz o nome do autor da
// tabela usuarios.
const COLUNAS_DO_RECADO = 'recados.id, recados.texto, recados.criado_em,
       recados.status, recados.arquivado_em, recados.publico,
       recados.autor_id, usuarios.nome AS autor
  FROM recados
  JOIN usuarios ON usuarios.id = recados.autor_id';

/**
 * Lista todos os recados na ordem escolhida. É a lista de quem entrou.
 *
 * A ordem vem de uma lista fechada: o valor do cookie decide entre duas
 * strings fixas e nunca entra no SQL.
 */
function listarRecados(PDO $pdo, string $ordem): array
{
    $sql = 'SELECT ' . COLUNAS_DO_RECADO . ' ' . ordenacao($ordem);

    return $pdo->query($sql)->fetchAll();
}

/**
 * Lista só os recados que qualquer pessoa pode ler: públicos e ainda
 * publicados. É a lista de quem não entrou.
 */
function listarRecadosPublicos(PDO $pdo, string $ordem): array
{
    $sql = 'SELECT ' . COLUNAS_DO_RECADO
        . " WHERE recados.publico AND recados.status = 'publicado' "
        . ordenacao($ordem);

    return $pdo->query($sql)->fetchAll();
}

/** ORDER BY das listas, escolhido entre duas strings fixas. */
function ordenacao(string $ordem): string
{
    $direcao = $ordem === 'mais-antigos' ? 'ASC' : 'DESC';

    return "ORDER BY recados.criado_em $direcao, recados.id $direcao";
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

/** Um recado público e ainda publicado pode ser lido sem login. */
function leituraPublica(array $recado): bool
{
    return $recado['publico'] && $recado['status'] === 'publicado';
}

/** Só o autor arquiva, e só enquanto o recado está publicado. */
function podeArquivar(array $recado, ?array $usuario): bool
{
    return $usuario !== null
        && $recado['status'] === 'publicado'
        && (int) $recado['autor_id'] === (int) $usuario['id'];
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
 * Arquiva um recado publicado do autor e grava o instante do arquivamento.
 *
 * O filtro por autor repete, no banco, a regra que a página já conferiu com
 * podeArquivar(). O filtro por status torna a operação repetível: arquivar
 * duas vezes não altera nada na segunda, e a data do primeiro arquivamento
 * fica.
 */
function arquivarRecado(PDO $pdo, int $id, int $autorId): void
{
    $consulta = $pdo->prepare(
        "UPDATE recados SET status = 'arquivado', arquivado_em = now()
          WHERE id = :id AND autor_id = :autor_id AND status = 'publicado'"
    );
    $consulta->execute(['id' => $id, 'autor_id' => $autorId]);
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
