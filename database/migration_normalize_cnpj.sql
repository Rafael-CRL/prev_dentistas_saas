-- Normalização dos registros de CNPJ existentes na tabela clinicas
-- Remove todos os caracteres não numéricos (pontos, barras e traços)
UPDATE clinicas 
SET cnpj = REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '')
WHERE cnpj IS NOT NULL AND cnpj != '';
