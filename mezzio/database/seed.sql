-- Seed determinístico das APIs de benchmark (SPEC-106).
-- Idempotente (não duplica em execuções repetidas).

-- 1. Candidatos (5 registros)
INSERT INTO candidates (id, name, position) VALUES
    (1, 'Candidato Alfa',   'Presidente'),
    (2, 'Candidato Beta',   'Presidente'),
    (3, 'Candidato Gama',   'Presidente'),
    (4, 'Candidato Delta',  'Presidente'),
    (5, 'Candidato Épsilon','Presidente')
ON CONFLICT (id) DO NOTHING;

-- 2. Categorias (10 registros)
INSERT INTO categories (id, name, slug) VALUES
    (1, 'Eletrônicos', 'eletronicos'),
    (2, 'Livros', 'livros'),
    (3, 'Vestuário', 'vestuario'),
    (4, 'Móveis', 'moveis'),
    (5, 'Alimentos', 'alimentos'),
    (6, 'Informática', 'informatica'),
    (7, 'Games', 'games'),
    (8, 'Brinquedos', 'brinquedos'),
    (9, 'Esportes', 'esportes'),
    (10, 'Automotivo', 'automotivo')
ON CONFLICT (id) DO NOTHING;

-- 3. Produtos (1.000 registros determinísticos)
INSERT INTO products (id, category_id, name, description, price, stock)
SELECT
    i,
    ((i % 10) + 1),
    'Produto ' || i,
    'Descrição detalhada do produto ' || i,
    round((10 + (i % 500) + (i % 99) * 0.01)::numeric, 2),
    (i % 100) + 1
FROM generate_series(1, 1000) AS i
ON CONFLICT (id) DO NOTHING;

SELECT setval('candidates_id_seq', (SELECT COALESCE(MAX(id), 1) FROM candidates));
SELECT setval('categories_id_seq', (SELECT COALESCE(MAX(id), 1) FROM categories));
SELECT setval('products_id_seq', (SELECT COALESCE(MAX(id), 1) FROM products));
