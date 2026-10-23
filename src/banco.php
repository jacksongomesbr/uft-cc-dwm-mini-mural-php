<?php

declare(strict_types=1);

/**
 * Abre a conexão com o PostgreSQL usando PDO.
 *
 * Com ERRMODE_EXCEPTION, um erro de SQL vira exceção e para a página, em vez
 * de devolver false e seguir adiante com dados errados.
 */
function conectarAoBanco(array $banco): PDO
{
    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        $banco['host'],
        $banco['porta'],
        $banco['nome'],
    );

    return new PDO($dsn, $banco['usuario'], $banco['senha'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
