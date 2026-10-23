-- Esquema do Mini Mural.
--
-- Execute com o banco já criado, conectado como o usuário da aplicação, para
-- que as tabelas pertençam a ele:
--   psql -h localhost -U mini_mural -d mini_mural -f sql/schema.sql
--
-- ATENÇÃO: o arquivo começa apagando as tabelas. Rodar de novo zera os
-- usuários e os recados. Este projeto não usa migrations: quando o esquema
-- muda, você edita este arquivo e roda outra vez.

-- recados primeiro, porque depende de usuarios.
DROP TABLE IF EXISTS recados;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id         bigint      GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    -- O PHP grava o nome em minúsculas, então "Ana" e "ana" são o mesmo
    -- usuário, e o UNIQUE impede o segundo cadastro.
    nome       text        NOT NULL UNIQUE,
    -- Nunca a senha: só o resultado de password_hash().
    senha_hash text        NOT NULL,
    criado_em  timestamptz NOT NULL DEFAULT now(),

    CONSTRAINT usuario_nome_valido CHECK (nome ~ '^[a-z0-9_.-]{3,30}$')
);

CREATE TABLE recados (
    id        bigint      GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    texto     text        NOT NULL,
    criado_em timestamptz NOT NULL DEFAULT now(),
    status    text        NOT NULL DEFAULT 'publicado',
    -- Quem publicou. Um usuário com recados não pode ser apagado.
    autor_id  bigint      NOT NULL REFERENCES usuarios (id),
    -- Recado público pode ser lido sem login. Todo recado nasce privado.
    publico   boolean     NOT NULL DEFAULT false,
    -- Instante do arquivamento. Vazio enquanto o recado está publicado.
    arquivado_em timestamptz,

    -- As mesmas regras que o PHP confere. Se um defeito no PHP deixar passar
    -- um recado inválido, o banco recusa a gravação.
    CONSTRAINT recado_texto_preenchido CHECK (btrim(texto) <> ''),
    CONSTRAINT recado_texto_ate_280    CHECK (char_length(texto) <= 280),
    CONSTRAINT recado_status_valido    CHECK (status IN ('publicado', 'arquivado')),
    -- Recado arquivado tem data de arquivamento, e recado publicado não tem.
    CONSTRAINT recado_arquivado_com_data CHECK ((status = 'arquivado') = (arquivado_em IS NOT NULL))
);

-- A lista do mural sempre ordena por data de criação.
CREATE INDEX recados_criado_em ON recados (criado_em);
