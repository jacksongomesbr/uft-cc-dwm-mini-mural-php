<?php
// Página inicial de quem não entrou: só os recados públicos ainda publicados,
// sem formulário e sem botões. Usa $recados, montado em public/index.php.
cabecalho('Recados públicos', 'publicos');
?>

<h1>Recados públicos</h1>

<p class="apoio">
    Estes recados foram marcados como públicos por quem os publicou.
    <a href="/login.php">Entre</a> ou <a href="/cadastro.php">crie uma conta</a>
    para ver o mural completo e publicar.
</p>

<?php if ($recados === []): ?>
    <p class="vazio">Nenhum recado público no momento.</p>
<?php else: ?>
    <ul class="lista">
        <?php foreach ($recados as $recado): ?>
            <li id="recado-<?= $recado['id'] ?>" class="cartao">
                <?php // Texto colado às tags: o CSS preserva as quebras de linha do recado. ?>
                <a class="texto-do-recado" href="/recado.php?id=<?= $recado['id'] ?>"><?= e($recado['texto']) ?></a>
                <p class="apoio">
                    <time datetime="<?= formatarIso($recado['criado_em']) ?>"><?= formatarDataHora($recado['criado_em']) ?></time>
                    · <?= e($recado['autor']) ?>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php rodape(); ?>
