
CREATE DATABASE IF NOT EXISTS `10devs` DEFAULT CHARACTER SET UTF8MB4 COLLATE utf8_general_ci;  -- utworzenie bazy danych 10devs
USE `10devs`;  -- wybranie bazy danych 10devs

CREATE TABLE users (
    usr_id INT(11) NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator uzytkownika',
    usr_login VARCHAR(50) NOT NULL UNIQUE COMMENT 'login uzytkownika',
    usr_password VARCHAR(255) NOT NULL COMMENT 'haslo uzytkownika',
    usr_city VARCHAR(150) NOT NULL COMMENT 'miasto bazowe uzytkownika',
    usr_date_registration TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data rejestracji uzytkownika',
    PRIMARY KEY (usr_id)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='tabela uzytkownikow';

CREATE TABLE cities (
    cit_id INT(11) NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator miasta',
    cit_usr_id INT(11) NOT NULL COMMENT 'identyfikator uzytkownika odpowiadajacy miastu',
    cit_name VARCHAR(150) NOT NULL COMMENT 'nazwa miasta',
    PRIMARY KEY (cit_id),
    UNIQUE KEY cities_unique (cit_usr_id, cit_name),
    KEY idx_cit_usr_id (cit_usr_id),
    CONSTRAINT fk_cit_usr_id FOREIGN KEY (cit_usr_id) REFERENCES users(usr_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='tabela miast';

CREATE TABLE recomm (
    rec_id INT(11) NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator rekomendacji',
    rec_usr_id INT(11) NOT NULL COMMENT 'identyfikator uzytkownika, ktorego dotyczy rekomendacja',
    rec_cit_id INT(11) NOT NULL COMMENT 'identyfikator miasta, dla ktorego jest rekomendacja',
    rec_title VARCHAR(200) NOT NULL COMMENT 'tytul rekomendacji',
    rec_desc TEXT NOT NULL COMMENT 'opis rekomendacji',
    rec_model VARCHAR(50) NOT NULL COMMENT 'model generujacy rekomendacje lub manual',
    rec_date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data utworzenia rekomendacji',
    rec_date_modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'data modyfikacji rekomendacji',
    rec_status VARCHAR(50) NOT NULL COMMENT 'status rekomendacji',
    PRIMARY KEY (rec_id),
    UNIQUE KEY recomm_unique (rec_usr_id, rec_cit_id, rec_title),
    KEY idx_rec_usr_id (rec_usr_id),
    KEY idx_rec_cit_id (rec_cit_id),
    CONSTRAINT fk_rec_usr_id FOREIGN KEY (rec_usr_id) REFERENCES users(usr_id) ON DELETE CASCADE,
    CONSTRAINT fk_rec_cit_id FOREIGN KEY (rec_cit_id) REFERENCES cities(cit_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='tabela rekomendacji';

CREATE TABLE ai_logs (
    ail_id INT(11) NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator loga AI',
    ail_usr_id INT(11) NOT NULL COMMENT 'identyfikator uzytkownika powiazany z logiem',
    ail_rec_id INT(11) NOT NULL COMMENT 'identyfikator rekomendacji powiazany z logiem',
    ail_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data logowania dzialania AI',
    ail_status VARCHAR(50) NOT NULL COMMENT 'status loga AI',
    PRIMARY KEY (ail_id),
    KEY idx_ail_usr_id (ail_usr_id),
    KEY idx_ail_rec_id (ail_rec_id)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='tabela logow AI';

CREATE TABLE ai_inputs (
    ain_id INT(11) NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator danych wejscia AI',
    ain_usr_id INT(11) NOT NULL COMMENT 'identyfikator uzytkownika, ktorego dane sa przetwarzane',
    ain_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data pobrania danych wejscia',
    ain_content TEXT NOT NULL COMMENT 'tresc danych wejscia dla AI',
    ain_source VARCHAR(150) COMMENT 'zrodlo, z ktorego pobrano dane',
    PRIMARY KEY (ain_id),
    KEY idx_ain_usr_id (ain_usr_id)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 COMMENT='tabela danych wejscia dla AI';
