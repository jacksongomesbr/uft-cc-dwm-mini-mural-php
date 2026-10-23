<?php cabecalho('Erro', ''); ?>

<h1>Algo deu errado</h1>
<section class="cartao">
    <p>O servidor não conseguiu atender a este pedido.</p>
    <p class="apoio">
        Se você está rodando o projeto, o motivo está no log de erros do
        Apache ou do PHP-FPM. O mais comum é o PostgreSQL desligado ou o
        <code>config.php</code> com usuário ou senha errados.
    </p>
    <a class="botao" href="/">Voltar ao mural</a>
</section>

<?php rodape(); ?>
