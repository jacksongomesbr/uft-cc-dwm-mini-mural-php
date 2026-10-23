<?php

declare(strict_types=1);

const LIMITE_DO_RECADO = 280;

/**
 * Confere o texto de um recado e devolve a mensagem de erro, ou null se o
 * texto é válido.
 *
 * Esta é a regra que vale. O JavaScript do navegador repete a conferência só
 * para avisar mais cedo; um pedido que chega sem passar por ele é conferido
 * aqui do mesmo jeito.
 */
function validarRecado(string $texto, int $limite = LIMITE_DO_RECADO): ?string
{
    $textoLimpo = trim($texto);

    if ($textoLimpo === '') {
        return 'Escreva o recado antes de publicar.';
    }

    // mb_strlen conta caracteres; strlen contaria bytes, e "às" teria 3.
    if (mb_strlen($textoLimpo) > $limite) {
        return "O recado aceita até $limite caracteres.";
    }

    return null;
}

/**
 * O navegador envia cada quebra de linha do textarea como \r\n, dois
 * caracteres, mas o atributo maxlength conta como um. Trocar por \n faz o
 * PHP contar igual ao navegador.
 */
function normalizarQuebrasDeLinha(string $texto): string
{
    return str_replace("\r\n", "\n", $texto);
}

const SENHA_MINIMO = 8;

/**
 * Nome de usuário como fica gravado: sem espaços nas pontas e em minúsculas.
 * Assim "Ana" e "ana" são o mesmo usuário, no cadastro e no login.
 */
function normalizarNome(string $nome): string
{
    return mb_strtolower(trim($nome));
}

/**
 * Confere os campos do cadastro e devolve as mensagens de erro por campo.
 * Um array vazio quer dizer que está tudo certo.
 */
function validarCadastro(
    string $nome,
    #[\SensitiveParameter] string $senha,
    #[\SensitiveParameter] string $confirmacao,
): array {
    $erros = [];

    // A mesma regra do CHECK usuario_nome_valido, no schema.sql.
    if (preg_match('/^[a-z0-9_.-]{3,30}$/', $nome) !== 1) {
        $erros['nome'] = 'Use de 3 a 30 caracteres: letras sem acento, números, ponto, hífen ou sublinhado.';
    }

    if (mb_strlen($senha) < SENHA_MINIMO) {
        $erros['senha'] = 'A senha precisa ter pelo menos ' . SENHA_MINIMO . ' caracteres.';
    } elseif (strlen($senha) > 72) {
        // O bcrypt, algoritmo do password_hash, só usa os primeiros 72 bytes.
        // Uma senha maior teria um pedaço ignorado sem aviso.
        $erros['senha'] = 'A senha aceita até 72 bytes; letras acentuadas ocupam 2.';
    }

    if ($confirmacao !== $senha) {
        $erros['confirmacao'] = 'A confirmação é diferente da senha.';
    }

    return $erros;
}
