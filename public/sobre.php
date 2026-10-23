<?php

declare(strict_types=1);

// Informações do projeto e escolha da ordem do mural. Esta página não usa o
// banco: a ordem fica em um cookie do navegador.

require __DIR__ . '/../src/inicializar.php';

$erro = null;

if (ehPost()) {
    $ordemEscolhida = lerCampo('ordem');

    if (ordemValida($ordemEscolhida)) {
        salvarOrdem($ordemEscolhida);
        redirecionar('/sobre.php?salvo=1');
    }

    http_response_code(422);
    $erro = 'Escolha uma das ordens da lista.';
}

$ordem = lerOrdem();
$salvo = isset($_GET['salvo']);

cabecalho('Sobre', 'sobre');
?>

<h1>Sobre o Mini Mural</h1>

<section class="cartao">
    <p>
        Um mural de recados construído na disciplina de Desenvolvimento
        WebMobile, nesta versão como aplicação web em PHP com PostgreSQL.
    </p>
    <p class="apoio">
        Os recados ficam no banco de dados e são os mesmos para todo navegador
        que abrir o mural. A ordem escolhida abaixo fica guardada só neste
        navegador.
    </p>
</section>

<form class="cartao" method="post" action="/sobre.php">
    <fieldset>
        <legend><h2>Ordem do mural</h2></legend>

        <?php foreach (ORDENS as $valor => $rotulo): ?>
            <label class="opcao">
                <input type="radio" name="ordem" value="<?= $valor ?>"
                       <?= $valor === $ordem ? 'checked' : '' ?>>
                <?= $rotulo ?>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <button type="submit" class="botao">Salvar ordem</button>

    <p class="apoio" aria-live="polite"><?= $salvo ? 'Preferência salva.' : '' ?></p>
    <?php if ($erro !== null): ?>
        <p class="erro" aria-live="polite"><?= e($erro) ?></p>
    <?php endif; ?>
</form>

<?php rodape(); ?>
