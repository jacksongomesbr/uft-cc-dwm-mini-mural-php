<?php

declare(strict_types=1);

// Sessão de login e proteção CSRF.
//
// A sessão do PHP guarda dados no servidor e entrega ao navegador só um
// identificador, no cookie mini_mural_sessao. O usuário logado fica em
// $_SESSION['usuario'], com id e nome.

function iniciarSessao(): void
{
    // Recusa identificadores de sessão que o servidor não criou. Sem isso,
    // alguém poderia escolher o identificador da vítima antes do login.
    ini_set('session.use_strict_mode', '1');

    session_name('mini_mural_sessao');
    session_set_cookie_params([
        'path'     => '/',
        // O JavaScript da página não lê o cookie da sessão.
        'httponly' => true,
        // O navegador não manda o cookie em POST vindo de outro site.
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** O usuário logado, com id e nome, ou null para quem não entrou. */
function usuarioLogado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** Marca o usuário como logado nesta sessão. */
function entrar(array $usuario): void
{
    // Troca o identificador da sessão no login. O identificador de antes do
    // login, se alguém o conhecia, deixa de valer.
    session_regenerate_id(true);
    $_SESSION['usuario'] = ['id' => $usuario['id'], 'nome' => $usuario['nome']];
}

/** Encerra a sessão: apaga os dados no servidor e o cookie no navegador. */
function sair(): void
{
    $_SESSION = [];
    $parametros = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $parametros['path'],
        'httponly' => $parametros['httponly'],
        'samesite' => $parametros['samesite'],
    ]);
    session_destroy();
}

/** Manda quem não entrou para a tela de login e encerra a página. */
function exigirLogin(): array
{
    $usuario = usuarioLogado();
    if ($usuario === null) {
        redirecionar('/login.php');
    }
    return $usuario;
}

/**
 * Token CSRF desta sessão, criado na primeira vez.
 *
 * Todo formulário POST leva o token em um campo oculto. Um site externo pode
 * fazer o navegador enviar um formulário para o mural, mas não consegue ler o
 * token, então o envio dele chega sem o valor certo.
 */
function tokenCsrf(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** O campo oculto com o token, para colocar dentro de cada <form method="post">. */
function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . tokenCsrf() . '">';
}

/**
 * Recusa com 403 um POST sem o token certo. src/inicializar.php chama esta
 * função em todo POST, antes de qualquer página tratar o formulário.
 */
function conferirCsrf(): void
{
    $enviado = $_POST['csrf'] ?? '';

    // hash_equals compara em tempo constante: o tempo da resposta não indica
    // quantos caracteres do token estavam certos.
    if (!is_string($enviado) || !hash_equals(tokenCsrf(), $enviado)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Formulário expirado ou enviado de fora do mural. Recarregue a página e tente de novo.\n");
    }
}
