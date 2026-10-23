<?php

declare(strict_types=1);

// Primeiro arquivo que toda página de public/ inclui. Carrega a configuração,
// as funções do projeto e o tratamento de erro. Não abre conexão com o banco:
// cada página chama conectarAoBanco() quando precisa.

$arquivoDeConfiguracao = __DIR__ . '/../config.php';

if (!file_exists($arquivoDeConfiguracao)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Arquivo config.php não encontrado.\n"
        . "Copie config.exemplo.php para config.php e ajuste a conexão com o banco.\n");
}

$configuracao = require $arquivoDeConfiguracao;

date_default_timezone_set($configuracao['fuso_horario']);

require __DIR__ . '/banco.php';
require __DIR__ . '/recados.php';
require __DIR__ . '/validacao.php';
require __DIR__ . '/preferencias.php';
require __DIR__ . '/formatacao.php';
require __DIR__ . '/http.php';
require __DIR__ . '/visao.php';

// Qualquer exceção não tratada termina aqui: o detalhe vai para o log de
// erros (do Apache ou do PHP-FPM) e o usuário vê uma página de erro sem
// informação interna.
set_exception_handler(function (Throwable $erro): void {
    error_log((string) $erro);
    http_response_code(500);
    require __DIR__ . '/templates/erro.php';
});
