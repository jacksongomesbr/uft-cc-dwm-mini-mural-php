<?php

declare(strict_types=1);

// Página inicial. Quem entrou vê o mural completo: lista, resumo e formulário
// de recado novo; GET mostra a página e POST tenta publicar. Quem não entrou
// vê só a lista dos recados públicos, para leitura.

require __DIR__ . '/../src/inicializar.php';

$usuario = usuarioLogado();
$pdo = conectarAoBanco($configuracao['banco']);

if ($usuario === null) {
    // Publicar continua exigindo login.
    if (ehPost()) {
        redirecionar('/login.php');
    }
    $recados = listarRecadosPublicos($pdo, lerOrdem());
    require __DIR__ . '/../src/templates/recados-publicos.php';
    exit;
}

$texto = '';
$publico = false;
$erro = null;

if (ehPost()) {
    $texto = normalizarQuebrasDeLinha(lerCampo('texto'));
    // Caixa desmarcada não é enviada pelo navegador; ausente quer dizer privado.
    $publico = lerCampo('publico') === '1';
    $erro = validarRecado($texto);

    if ($erro === null) {
        publicarRecado($pdo, trim($texto), $usuario['id'], $publico);
        redirecionar('/');
    }

    // Texto inválido: a página é mostrada de novo, com a mensagem e com o
    // que a pessoa digitou. O código 422 diz que o pedido foi recusado.
    http_response_code(422);
}

$recados = listarRecados($pdo, lerOrdem());
$resumo = resumirRecados($recados);
$restantes = LIMITE_DO_RECADO - mb_strlen($texto);

cabecalho('Mural', 'mural');
?>

<h1>Mini Mural</h1>

<form class="cartao novo-recado" method="post" action="/" novalidate
      data-novo-recado data-limite="<?= LIMITE_DO_RECADO ?>">
    <?= campoCsrf() ?>
    <label for="texto">Novo recado</label>
    <textarea id="texto" name="texto" rows="3"
              maxlength="<?= LIMITE_DO_RECADO ?>"
              placeholder="Escreva um recado"
              aria-describedby="erro-texto contador"
              <?= $erro !== null ? 'aria-invalid="true" autofocus' : '' ?>
    ><?= e($texto) ?></textarea>

    <p id="erro-texto" class="erro" aria-live="polite"><?= $erro !== null ? e($erro) : '' ?></p>

    <label class="caixa">
        <input type="checkbox" name="publico" value="1" <?= $publico ? 'checked' : '' ?>>
        Público: qualquer pessoa com o endereço pode ler, sem login
    </label>

    <div class="linha">
        <p id="contador" class="apoio <?= $restantes <= 20 ? 'perto-do-limite' : '' ?>">
            <?= $restantes ?> caracteres restantes
        </p>
        <button type="submit" class="botao">Publicar</button>
    </div>
</form>

<p class="resumo">
    <span><?= plural($resumo['publicados'], 'publicado', 'publicados') ?></span>
    <span><?= plural($resumo['arquivados'], 'arquivado', 'arquivados') ?></span>
</p>

<h2>Recados</h2>

<?php if ($recados === []): ?>
    <p class="vazio">Nenhum recado ainda.</p>
<?php else: ?>
    <ul class="lista">
        <?php foreach ($recados as $recado): ?>
            <?php $arquivado = $recado['status'] === 'arquivado'; ?>
            <li id="recado-<?= $recado['id'] ?>"
                class="cartao <?= $arquivado ? 'arquivado' : '' ?>">
                <?php // Texto colado às tags: o CSS preserva as quebras de linha do recado. ?>
                <a class="texto-do-recado" href="/recado.php?id=<?= $recado['id'] ?>"><?= e($recado['texto']) ?></a>

                <div class="linha">
                    <p class="apoio">
                        <time datetime="<?= formatarIso($recado['criado_em']) ?>"><?= formatarHora($recado['criado_em']) ?></time>
                        · <?= e($recado['autor']) ?>
                        · <?= $recado['publico'] ? 'Público' : 'Privado' ?>
                    </p>

                    <?php if ($arquivado): ?>
                        <span class="etiqueta">
                            Arquivado às <time datetime="<?= formatarIso($recado['arquivado_em']) ?>"><?= formatarHora($recado['arquivado_em']) ?></time>
                        </span>
                    <?php elseif (podeArquivar($recado, $usuario)): ?>
                        <form method="post" action="/arquivar.php">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="id" value="<?= $recado['id'] ?>">
                            <button type="submit" class="botao"
                                    aria-label="Arquivar recado: <?= e($recado['texto']) ?>">
                                Arquivar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php rodape(); ?>
