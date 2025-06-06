CREATE DATABASE IF NOT EXISTS sports_analytics;
CREATE USER IF NOT EXISTS 'symfony_user'@'%' IDENTIFIED BY 'symfony_password';
GRANT ALL PRIVILEGES ON sports_analytics.* TO 'symfony_user'@'%';
FLUSH PRIVILEGES;
