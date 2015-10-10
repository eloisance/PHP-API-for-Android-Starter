CREATE TABLE users (
	id INT(6) AUTO_INCREMENT PRIMARY KEY,
	id_google VARCHAR(255),
	firstname VARCHAR(30),
	lastname VARCHAR(30),
	email VARCHAR(50),
	password VARCHAR(255),
	phone VARCHAR(10),
	provider VARCHAR(10) NOT NULL,
	registration_date DATE NOT NULL
)ENGINE=InnoDB;