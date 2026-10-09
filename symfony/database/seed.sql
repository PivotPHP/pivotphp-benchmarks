-- Seed determinístico: 5 candidatos.
-- Idempotente (não duplica em execuções repetidas).

INSERT INTO candidates (id, name, position) VALUES
    (1, 'Candidato Alfa',   'Presidente'),
    (2, 'Candidato Beta',   'Presidente'),
    (3, 'Candidato Gama',   'Presidente'),
    (4, 'Candidato Delta',  'Presidente'),
    (5, 'Candidato Épsilon','Presidente')
ON CONFLICT (id) DO NOTHING;
