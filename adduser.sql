-- Создаем пользователя с правом подключаться с localhost.
CREATE USER IF NOT EXISTS 'student'@'localhost' IDENTIFIED BY 'password';
-- Полные права на базу данных stroymaterialy.
GRANT ALL PRIVILEGES ON stroymaterialy.* TO 'student'@'localhost';
-- Применяем изменения.
FLUSH PRIVILEGES;
