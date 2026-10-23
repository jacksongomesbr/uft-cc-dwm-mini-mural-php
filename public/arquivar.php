<?php

declare(strict_types=1);

// Recebe o botão "Arquivar" da lista e do detalhe. Não tem HTML: grava e
// redireciona.

require __DIR__ . '/../src/inicializar.php';

// Mudança de dados só por POST. Um link (GET) que arquivasse poderia ser
// disparado por um buscador ou por uma pré-carga do navegador.
exigirPost();
exigirLogin();

$id = lerId(INPUT_POST);
if ($id === null) {
    recadoNaoEncontrado();
}

$pdo = conectarAoBanco($configuracao['banco']);
arquivarRecado($pdo, $id);

// O destino sai de duas opções fixas. Aceitar um endereço qualquer vindo do
// formulário deixaria um site externo usar este para redirecionar pessoas.
if (lerCampo('voltar_para') === 'detalhe') {
    redirecionar('/recado.php?id=' . $id);
}

// Na lista, a âncora devolve a pessoa ao recado que ela acabou de arquivar.
redirecionar('/#recado-' . $id);
