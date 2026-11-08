

CREATE USER IF NOT EXISTS 'studyvault_user'@'localhost' IDENTIFIED BY 'password';
GRANT SELECT, INSERT, UPDATE, DELETE ON studyvault.* TO 'studyvault_user'@'localhost';
FLUSH PRIVILEGES;
