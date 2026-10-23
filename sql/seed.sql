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
-- aparece para quem não entrou, na página inicial e em /recado.php?id=3.
INSERT INTO recados (texto, criado_em, status, arquivado_em, publico, autor_id)
SELECT exemplos.texto, exemplos.criado_em::timestamptz, exemplos.status,
       exemplos.arquivado_em::timestamptz, exemplos.publico, usuarios.id
  FROM (VALUES
        ('Entrega do relatório adiada.',           '2026-03-10 16:45:00-03', 'arquivado', '2026-03-11 08:10:00-03', false),
        ('A chave da sala 3 está na coordenação.', '2026-03-11 09:20:00-03', 'publicado', NULL,                     false),
        ('Reunião de equipe na quinta, às 14h.',   '2026-03-12 14:02:00-03', 'publicado', NULL,                     true)
       ) AS exemplos (texto, criado_em, status, arquivado_em, publico)
 CROSS JOIN usuarios
 WHERE usuarios.nome = 'exemplo';
