-- Run after schema.sql (or the optional-deadline migration on an existing database).
-- Re-running this file preserves existing programs and avoids duplicate names.
INSERT INTO scholarships (name, description, deadline, status)
SELECT programs.name, programs.description, NULL, 'active'
FROM (
    SELECT 'CARRI (Cultural, Athletics, Rescue and ROTC Incentives)' AS name,
           'Cultural, Athletics, Rescue and ROTC Incentives.' AS description
    UNION ALL
    SELECT 'BOARD Program',
           'Board review assistance for graduating/outstanding academic results.'
    UNION ALL
    SELECT 'CREATE (Comprehensive Research and Excellence Award for Transformative Education)',
           'Comprehensive Research and Excellence Award for Transformative Education.'
    UNION ALL
    SELECT 'SEAL (Scholar''s Empowerment Assistance for Leadership)',
           'Scholar''s Empowerment Assistance for Leadership.'
) AS programs
WHERE NOT EXISTS (
    SELECT 1 FROM scholarships AS existing WHERE existing.name = programs.name
);
