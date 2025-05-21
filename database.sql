-- Atualiza a tabela messages para incluir subject e parent_id
ALTER TABLE messages
ADD COLUMN subject VARCHAR(255) NULL AFTER receiver_id,
ADD COLUMN parent_id INT NULL AFTER content,
ADD FOREIGN KEY (parent_id) REFERENCES messages(id) ON DELETE SET NULL; 