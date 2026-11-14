-- Add profit column to evenements table
-- Run this SQL command in your MySQL database (phpMyAdmin or MySQL Workbench)

ALTER TABLE evenements 
ADD COLUMN profit DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Total profit from confirmed reservations';

-- Optional: Update existing events with calculated profit
-- This query calculates profit for all existing events
UPDATE evenements e
SET e.profit = (
    SELECT COALESCE(SUM(r.quantite), 0) * e.prix
    FROM reservations r
    WHERE r.id_event = e.id_event 
    AND r.statut = 'confirmee'
);
