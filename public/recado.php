<?php

declare(strict_types=1);

// Detalhe de um recado: /recado.php?id=42

require __DIR__ . '/../src/inicializar.php';

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

cabecalho('Recado', 'mural');
?>

<h1>Recado</h1>

<section class="cartao <?= $recado['status'] === 'arquivado' ? 'arquivado' : '' ?>">
    <p class="texto-completo"><?= e($recado['texto']) ?></p>
    <time class="apoio" datetime="<?= formatarIso($recado['criado_em']) ?>">
        <?= formatarDataHora($recado['criado_em']) ?>
    </time>

    <?php if ($recado['status'] === 'arquivado'): ?>
        <p class="etiqueta">Arquivado</p>
    <?php else: ?>
        <form method="post" action="/arquivar.php">
            <input type="hidden" name="id" value="<?= $recado['id'] ?>">
            <input type="hidden" name="voltar_para" value="detalhe">
            <button type="submit" class="botao">Arquivar recado</button>
        </form>
    <?php endif; ?>
</section>

<a href="/">Ver todos os recados</a>

<?php rodape(); ?>
