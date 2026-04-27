USE mensageria;

-- Passa todas as chaves do usuario 2 para o usuario 1
UPDATE api_keys SET user_id = 1 WHERE user_id = 2;

-- Apaga o usuario 2
DELETE FROM users WHERE id = 2;
