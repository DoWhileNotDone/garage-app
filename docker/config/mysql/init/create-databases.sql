-- create databases
CREATE DATABASE IF NOT EXISTS `garage_dev`;
CREATE DATABASE IF NOT EXISTS `garage_test`;

-- create user and grant rights
CREATE USER 'garage_usr'@'%' IDENTIFIED BY 'garage_pw';
GRANT ALL ON *.* TO 'garage_usr'@'%';
