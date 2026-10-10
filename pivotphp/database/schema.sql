-- Esquema da API de votação (PostgreSQL).
-- Tabela candidatos + votos (voto único por CPF).

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
