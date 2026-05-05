-- Add super_admin to ENUM list
ALTER TABLE usuarios MODIFY COLUMN user_type ENUM('admin', 'employee', 'super_admin') DEFAULT 'employee';
