-- Usuário e recados de exemplo.
--
-- Opcional. Execute depois do schema.sql:
--   psql -h localhost -U mini_mural -d mini_mural -f sql/seed.sql
--
-- Usuário: exemplo
-- Senha:   senha-de-exemplo
-- O hash abaixo foi gerado com password_hash('senha-de-exemplo', PASSWORD_DEFAULT).
-- Use este usuário só na sua máquina.

INSERT INTO usuarios (nome, senha_hash) VALUES
    ('exemplo', '$2y$12$D741kWagYO4/48rf/V7COeDMG03UuCMQEivioAKt5rDfdIsAz9zue');

-- As datas são diferentes de propósito: com todas iguais, trocar a ordem do
-- mural em "Sobre" não mudaria nada na tela. O terceiro recado é público e
-- pode ser lido sem login em /recado.php?id=3.
INSERT INTO recados (texto, criado_em, status, publico, autor_id)
SELECT exemplos.texto, exemplos.criado_em::timestamptz, exemplos.status,
       exemplos.publico, usuarios.id
  FROM (VALUES
        ('Entrega do relatório adiada.',           '2026-03-10 16:45:00-03', 'arquivado', false),
        ('A chave da sala 3 está na coordenação.', '2026-03-11 09:20:00-03', 'publicado', false),
        ('Reunião de equipe na quinta, às 14h.',   '2026-03-12 14:02:00-03', 'publicado', true)
       ) AS exemplos (texto, criado_em, status, publico)
 CROSS JOIN usuarios
 WHERE usuarios.nome = 'exemplo';
