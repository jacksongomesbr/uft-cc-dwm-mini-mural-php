<?php
// Usa $titulo e $paginaAtual, recebidos pela função cabecalho(). A navegação
// muda conforme a pessoa entrou ou não.
$usuario = usuarioLogado();
$paginas = $usuario !== null
    ? [
        'mural' => ['endereco' => '/', 'rotulo' => 'Mural'],
        'sobre' => ['endereco' => '/sobre.php', 'rotulo' => 'Sobre'],
    ]
    : [
        'publicos' => ['endereco' => '/', 'rotulo' => 'Recados públicos'],
        'entrar'   => ['endereco' => '/login.php', 'rotulo' => 'Entrar'],
        'cadastro' => ['endereco' => '/cadastro.php', 'rotulo' => 'Criar conta'],
    ];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Mini Mural</title>
    <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
    <header class="topo">
        <nav class="coluna navegacao" aria-label="Principal">
            <?php foreach ($paginas as $chave => $pagina): ?>
                <a href="<?= $pagina['endereco'] ?>"
                   <?= $chave === $paginaAtual ? 'aria-current="page"' : '' ?>>
                    <?= $pagina['rotulo'] ?>
                </a>
            <?php endforeach; ?>

            <?php if ($usuario !== null): ?>
                <form class="sessao" method="post" action="/sair.php">
                    <?= campoCsrf() ?>
                    <span class="apoio"><?= e($usuario['nome']) ?></span>
                    <button type="submit" class="botao-texto">Sair</button>
                </form>
            <?php endif; ?>
        </nav>
    </header>
    <main class="coluna">
