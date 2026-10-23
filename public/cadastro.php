<?php

declare(strict_types=1);

// Cadastro: GET mostra o formulário; POST valida, cria o usuário e já faz o
// login com ele.

require __DIR__ . '/../src/inicializar.php';

if (usuarioLogado() !== null) {
    redirecionar('/');
}

$nome = '';
$erros = [];

if (ehPost()) {
    $nome = normalizarNome(lerCampo('nome'));
    $senha = lerCampo('senha');
    $erros = validarCadastro($nome, $senha, lerCampo('confirmacao'));

    if ($erros === []) {
        $pdo = conectarAoBanco($configuracao['banco']);
        $usuario = criarUsuario($pdo, $nome, $senha);

        if ($usuario !== null) {
            entrar($usuario);
            redirecionar('/');
        }

        $erros['nome'] = 'Esse nome de usuário já existe.';
    }

    http_response_code(422);
}

cabecalho('Criar conta', 'cadastro');
?>

<h1>Criar conta</h1>

<form class="cartao formulario" method="post" action="/cadastro.php" novalidate>
    <?= campoCsrf() ?>

    <label for="nome">Usuário</label>
    <input id="nome" name="nome" type="text" value="<?= e($nome) ?>"
           autocomplete="username" autocapitalize="none" required
           aria-describedby="ajuda-nome erro-nome"
           <?= isset($erros['nome']) ? 'aria-invalid="true" autofocus' : '' ?>>
    <p id="ajuda-nome" class="apoio">De 3 a 30 caracteres: letras sem acento, números, ponto, hífen ou sublinhado.</p>
    <p id="erro-nome" class="erro"><?= e($erros['nome'] ?? '') ?></p>

    <label for="senha">Senha</label>
    <input id="senha" name="senha" type="password"
           autocomplete="new-password" required
           aria-describedby="erro-senha"
           <?= isset($erros['senha']) ? 'aria-invalid="true"' : '' ?>>
    <p id="erro-senha" class="erro"><?= e($erros['senha'] ?? '') ?></p>

    <label for="confirmacao">Repita a senha</label>
    <input id="confirmacao" name="confirmacao" type="password"
           autocomplete="new-password" required
           aria-describedby="erro-confirmacao"
           <?= isset($erros['confirmacao']) ? 'aria-invalid="true"' : '' ?>>
    <p id="erro-confirmacao" class="erro"><?= e($erros['confirmacao'] ?? '') ?></p>

    <button type="submit" class="botao">Criar conta</button>
</form>

<p>Já tem conta? <a href="/login.php">Entre</a>.</p>

<?php rodape(); ?>
