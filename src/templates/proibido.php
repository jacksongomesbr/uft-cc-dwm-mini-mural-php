<?php // Usa $mensagem, recebida pela função acessoNegado(). ?>
<?php cabecalho('Ação não permitida', ''); ?>

<h1>Ação não permitida</h1>
<section class="cartao">
    <p><?= e($mensagem) ?></p>
    <a class="botao" href="/">Voltar ao mural</a>
</section>

<?php rodape(); ?>
