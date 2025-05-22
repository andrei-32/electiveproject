-- Add winstreak column to users table if it doesn't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS winstreak INT DEFAULT 0;

-- Update existing users to have 0 winstreak if they don't have one
UPDATE users 
SET winstreak = 0 
WHERE winstreak IS NULL; 