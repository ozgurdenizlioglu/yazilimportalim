-- 004_create_attendance.sql
CREATE TABLE IF NOT EXISTS attendance (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  scanned_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  type TEXT NOT NULL CHECK (type IN ('in','out')),
  source_device TEXT,
  notes TEXT
);

CREATE INDEX IF NOT EXISTS idx_attendance_user_time ON attendance(user_id, scanned_at DESC);

-- Kullanıcıya özel QR secret yoksa ekleyelim
ALTER TABLE users ADD COLUMN IF NOT EXISTS qr_secret TEXT;

-- Tetiklemek için boş secret’ları doldurmayı uygulama tarafında yapacağız.