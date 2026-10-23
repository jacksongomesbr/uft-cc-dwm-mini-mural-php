<?php

declare(strict_types=1);

// Detalhe de um recado: /recado.php?id=42
//
// É a única página que atende quem não entrou, e só para recado público.
// Nesse caso ela mostra o recado sem o botão de arquivar.

require __DIR__ . '/../src/inicializar.php';

$usuario = usuarioLogado();

// Um id que não é número inteiro nem chega ao banco. Mandar "abc" para uma
// coluna bigint faria o PostgreSQL responder com erro, e a pessoa veria a
// página de erro do servidor em vez de "não encontrado".
$id = lerId(INPUT_GET);
if ($id === null) {
    recadoNaoEncontrado();
}

$pdo = conectarAoBanco($configuracao['banco']);
$recado = buscarRecado($pdo, $id);
if ($recado === null) {
    recadoNaoEncontrado();
}

// Recado privado só para quem entrou. Quem não entrou vai para o login, como
// em qualquer outra página.
if (!$recado['publico'] && $usuario === null) {
    redirecionar('/login.php');
}

cabecalho('Recado', 'mural');
?>

<h1>Recado</h1>

<section class="cartao <?= $recado['status'] === 'arquivado' ? 'arquivado' : '' ?>">
    <p class="texto-completo"><?= e($recado['texto']) ?></p>
    <p class="apoio">
        <time datetime="<?= formatarIso($recado['criado_em']) ?>"><?= formatarDataHora($recado['criado_em']) ?></time>
        · por <?= e($recado['autor']) ?>
        · <?= $recado['publico'] ? 'Público' : 'Privado' ?>
    </p>

    <?php if ($recado['status'] === 'arquivado'): ?>
        <p class="etiqueta">Arquivado</p>
    <?php elseif ($usuario !== null): ?>
        <form method="post" action="/arquivar.php">
            <?= campoCsrf() ?>
            <input type="hidden" name="id" value="<?= $recado['id'] ?>">
            <input type="hidden" name="voltar_para" value="detalhe">
            <button type="submit" class="botao">Arquivar recado</button>
        </form>
    <?php endif; ?>
</section>

<?php if ($usuario !== null): ?>
    <a href="/">Ver todos os recados</a>
<?php else: ?>
    <p class="apoio">Você está lendo um recado público. <a href="/login.php">Entre</a> para ver o mural.</p>
<?php endif; ?>

<?php rodape(); ?>
