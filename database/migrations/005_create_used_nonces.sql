-- 005_create_used_nonces.sql
CREATE TABLE IF NOT EXISTS used_nonces (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  nonce TEXT NOT NULL,
  used_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_used_nonces_user_nonce ON used_nonces(user_id, nonce);
CREATE INDEX IF NOT EXISTS idx_used_nonces_time ON used_nonces(used_at DESC);