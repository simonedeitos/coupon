-- Fix password hash for simonedeitos (bcrypt invece di argon2id)
-- Eseguire questo comando nel database u362062795_couponami

UPDATE users
SET password_hash = '$2y$10$xmJXFWBSHqDrt8fokaMXvemzBMF2HA22KKoOs80w4PzBY1nUtgFw2'
WHERE username = 'simonedeitos';

-- Verifica
SELECT id, username, role, status, LEFT(password_hash, 7) AS hash_type FROM users WHERE username = 'simonedeitos';
