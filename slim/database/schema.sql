-- Esquema das APIs de Benchmark (PostgreSQL) — SPEC-106.
-- Suporta Grupo B (CRUD de Produtos/Categorias) e Grupo C (Votação).

-- 1. Grupo C: Votação
CREATE TABLE IF NOT EXISTS candidates (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    position    VARCHAR(255),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS votes (
    id            SERIAL PRIMARY KEY,
    candidate_id  INTEGER NOT NULL REFERENCES candidates(id),
    cpf           VARCHAR(11) NOT NULL UNIQUE,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_votes_candidate_id ON votes (candidate_id);

-- 2. Grupo B: Produtos e Categorias
CREATE TABLE IF NOT EXISTS categories (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS products (
    id          SERIAL PRIMARY KEY,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    price       NUMERIC(10, 2) NOT NULL,
    stock       INTEGER NOT NULL DEFAULT 0,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_products_category_id ON products (category_id);
CREATE INDEX IF NOT EXISTS idx_products_created_at ON products (created_at DESC);
