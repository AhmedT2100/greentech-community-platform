-- Remove statut column from evenements table
-- WARNING: This will permanently delete the column and all its data
-- Make sure you have a backup before running this command!

-- Drop the statut column
ALTER TABLE evenements DROP COLUMN statut;

-- Verification query (run this after to confirm column is removed)
-- DESCRIBE evenements;
