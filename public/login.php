<?php

declare(strict_types=1);

// Login: GET mostra o formulário; POST confere nome e senha.

require __DIR__ . '/../src/inicializar.php';

// Quem já entrou não precisa da tela de login.
if (usuarioLogado() !== null) {
    redirecionar('/');
}

$nome = '';
$erro = null;

if (ehPost()) {
    $nome = normalizarNome(lerCampo('nome'));
    $pdo = conectarAoBanco($configuracao['banco']);
    $usuario = conferirLogin($pdo, $nome, lerCampo('senha'));

    if ($usuario !== null) {
        entrar($usuario);
        redirecionar('/');
    }

    // A mesma mensagem para nome inexistente e senha errada.
    http_response_code(422);
    $erro = 'Usuário ou senha incorretos.';
}

cabecalho('Entrar', 'entrar');
?>

<h1>Entrar</h1>

<form class="cartao formulario" method="post" action="/login.php">
    <?= campoCsrf() ?>

    <label for="nome">Usuário</label>
    <input id="nome" name="nome" type="text" value="<?= e($nome) ?>"
           autocomplete="username" autocapitalize="none" required autofocus>

    <label for="senha">Senha</label>
    <input id="senha" name="senha" type="password"
           autocomplete="current-password" required>

    <?php if ($erro !== null): ?>
        <p class="erro" role="alert"><?= e($erro) ?></p>
    <?php endif; ?>

    <button type="submit" class="botao">Entrar</button>
</form>

<p>Ainda não tem conta? <a href="/cadastro.php">Crie uma conta</a>.</p>

<?php rodape(); ?>
