<?php

declare(strict_types=1);

// Todo o SQL de usuários mora neste arquivo.
//
// Um usuário é um array com as chaves id e nome. O hash da senha só sai deste
// arquivo dentro de conferirLogin(), para ser comparado, e nunca vai para a
// sessão nem para a tela.

// Hash de uma senha aleatória que ninguém conhece. Serve para conferirLogin()
// gastar o mesmo tempo quando o nome não existe.
const HASH_PARA_NOME_INEXISTENTE = '$2y$12$TGWtjT7sexOO7rOHACOuKOZ1pAW6SpCZuOlYtkgT9eJS8tClh.z.e';

/**
 * Confere nome e senha e devolve o usuário, ou null se não conferem.
 *
 * Nome inexistente e senha errada dão o mesmo resultado, e no mesmo tempo:
 * o password_verify roda nos dois casos. Assim a tela de login não revela
 * quais nomes estão cadastrados.
 *
 * #[\SensitiveParameter] tira a senha dos rastros de erro: se uma exceção
 * passar por esta função, o log mostra "Object(SensitiveParameterValue)" no
 * lugar dela.
 */
function conferirLogin(PDO $pdo, string $nome, #[\SensitiveParameter] string $senha): ?array
{
    $consulta = $pdo->prepare(
        'SELECT id, nome, senha_hash FROM usuarios WHERE nome = :nome'
    );
    $consulta->execute(['nome' => $nome]);
    $usuario = $consulta->fetch();

    $hash = $usuario === false ? HASH_PARA_NOME_INEXISTENTE : $usuario['senha_hash'];
    if (!password_verify($senha, $hash) || $usuario === false) {
        return null;
    }

    return ['id' => $usuario['id'], 'nome' => $usuario['nome']];
}

/**
 * Cria um usuário e devolve o usuário criado, ou null se o nome já existe.
 *
 * Quem chama já validou nome e senha com validarCadastro().
 */
function criarUsuario(PDO $pdo, string $nome, #[\SensitiveParameter] string $senha): ?array
{
    $consulta = $pdo->prepare(
        'INSERT INTO usuarios (nome, senha_hash) VALUES (:nome, :senha_hash)
         RETURNING id, nome'
    );

    try {
        $consulta->execute([
            'nome' => $nome,
            // password_hash gera um sal aleatório e o guarda junto do hash.
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        ]);
    } catch (PDOException $erro) {
        // 23505 é o código do PostgreSQL para violação de UNIQUE. Conferir
        // antes com um SELECT não basta: dois cadastros ao mesmo tempo
        // passariam pela conferência, e só o banco decide quem chegou primeiro.
        if ($erro->getCode() === '23505') {
            return null;
        }
        throw $erro;
    }

    return $consulta->fetch();
}
