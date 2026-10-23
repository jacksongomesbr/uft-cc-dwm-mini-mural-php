<?php
// Usa $titulo e $paginaAtual, recebidos pela função cabecalho().
$paginas = [
    'mural' => ['endereco' => '/', 'rotulo' => 'Mural'],
    'sobre' => ['endereco' => '/sobre.php', 'rotulo' => 'Sobre'],
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
        </nav>
    </header>
    <main class="coluna">
