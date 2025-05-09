
-- Dumping structure for table 10devs.ai_inputs
CREATE TABLE IF NOT EXISTS `ai_inputs` (
  `ain_id` int NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator danych wejscia AI',
  `ain_usr_id` int NOT NULL COMMENT 'identyfikator uzytkownika, ktorego dane sa przetwarzane',
  `ain_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data pobrania danych wejscia',
  `ain_content` text NOT NULL COMMENT 'tresc danych wejscia dla AI',
  `ain_source` varchar(150) DEFAULT NULL COMMENT 'zrodlo, z ktorego pobrano dane',
  PRIMARY KEY (`ain_id`),
  KEY `idx_ain_usr_id` (`ain_usr_id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='tabela danych wejscia dla AI';

-- Data exporting was unselected.

-- Dumping structure for table 10devs.ai_logs
CREATE TABLE IF NOT EXISTS `ai_logs` (
  `ail_id` int NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator loga AI',
  `ail_usr_id` int NOT NULL COMMENT 'identyfikator uzytkownika powiazany z logiem',
  `ail_rec_id` int NOT NULL COMMENT 'identyfikator rekomendacji powiazany z logiem',
  `ail_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data logowania dzialania AI',
  `ail_status` varchar(50) NOT NULL COMMENT 'status loga AI',
  PRIMARY KEY (`ail_id`),
  KEY `idx_ail_usr_id` (`ail_usr_id`),
  KEY `idx_ail_rec_id` (`ail_rec_id`)
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='tabela logow AI';

-- Data exporting was unselected.

-- Dumping structure for table 10devs.cities
CREATE TABLE IF NOT EXISTS `cities` (
  `cit_id` int NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator miasta',
  `cit_usr_id` int NOT NULL COMMENT 'identyfikator uzytkownika odpowiadajacy miastu',
  `cit_name` varchar(150) NOT NULL COMMENT 'nazwa miasta',
  `cit_desc` varchar(200) NOT NULL COMMENT 'krotki opis miasta',
  `cit_visited` tinyint(1) NOT NULL DEFAULT (0) COMMENT 'czy miasto zostalo odwiedzone przez uzytkownika (1 - tak, 0 - nie)',
  `cit_date_created` timestamp NOT NULL DEFAULT (now()) COMMENT 'data utworzenia miasta',
  PRIMARY KEY (`cit_id`),
  UNIQUE KEY `cities_unique` (`cit_usr_id`,`cit_name`),
  KEY `idx_cit_usr_id` (`cit_usr_id`),
  CONSTRAINT `fk_cit_usr_id` FOREIGN KEY (`cit_usr_id`) REFERENCES `users` (`usr_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3 COMMENT='tabela miast';

-- Data exporting was unselected.

-- Dumping structure for table 10devs.error_logs
CREATE TABLE IF NOT EXISTS `error_logs` (
  `err_id` int NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator logu błędu',
  `err_type` varchar(100) NOT NULL COMMENT 'typ błędu (np. login_error, validation_error, ai_fetch_error, ai_call_error)',
  `err_message` text NOT NULL COMMENT 'opis błędu',
  `err_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'czas wystąpienia błędu',
  `err_usr_id` int DEFAULT NULL COMMENT 'identyfikator użytkownika, jeśli dotyczy',
  `err_url` varchar(255) DEFAULT NULL COMMENT 'URL wywołania, przy którym wystąpił błąd',
  `err_payload` text COMMENT 'dodatkowe dane, np. payload lub stack trace',
  PRIMARY KEY (`err_id`),
  KEY `idx_err_usr_id` (`err_usr_id`),
  CONSTRAINT `fk_err_usr_id` FOREIGN KEY (`err_usr_id`) REFERENCES `users` (`usr_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='tabela logów błędów';

-- Data exporting was unselected.

-- Dumping structure for table 10devs.recom
CREATE TABLE IF NOT EXISTS `recom` (
  `rec_id` int NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator rekomendacji',
  `rec_usr_id` int NOT NULL COMMENT 'identyfikator uzytkownika, ktorego dotyczy rekomendacja',
  `rec_cit_id` int NOT NULL COMMENT 'identyfikator miasta, dla ktorego jest rekomendacja',
  `rec_title` varchar(200) NOT NULL COMMENT 'tytul rekomendacji',
  `rec_desc` text NOT NULL COMMENT 'opis rekomendacji',
  `rec_model` varchar(50) NOT NULL COMMENT 'model generujacy rekomendacje lub manual',
  `rec_date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data utworzenia rekomendacji',
  `rec_date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'data modyfikacji rekomendacji',
  `rec_status` varchar(50) NOT NULL COMMENT 'status rekomendacji',
  `rec_done` tinyint(1) NOT NULL DEFAULT (0) COMMENT 'czy rekomendacja zostala juz odwiedzona przez uzytkownika (1 - tak, 0 - nie)',
  PRIMARY KEY (`rec_id`),
  UNIQUE KEY `recom_unique` (`rec_usr_id`,`rec_cit_id`,`rec_title`) USING BTREE,
  KEY `idx_rec_usr_id` (`rec_usr_id`),
  KEY `idx_rec_cit_id` (`rec_cit_id`),
  CONSTRAINT `fk_rec_cit_id` FOREIGN KEY (`rec_cit_id`) REFERENCES `cities` (`cit_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rec_usr_id` FOREIGN KEY (`rec_usr_id`) REFERENCES `users` (`usr_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='tabela rekomendacji';

-- Data exporting was unselected.

-- Dumping structure for table 10devs.users
CREATE TABLE IF NOT EXISTS `users` (
  `usr_id` int NOT NULL AUTO_INCREMENT COMMENT 'unikalny identyfikator uzytkownika',
  `usr_login` varchar(50) NOT NULL COMMENT 'login uzytkownika',
  `usr_password` varchar(255) NOT NULL COMMENT 'haslo uzytkownika',
  `usr_city` varchar(150) NOT NULL COMMENT 'miasto bazowe uzytkownika',
  `usr_admin` tinyint(1) NOT NULL DEFAULT (0) COMMENT 'flaga oznaczajaca czy uzytkownik jest administratorem',
  `usr_date_registration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'data rejestracji uzytkownika',
  PRIMARY KEY (`usr_id`),
  UNIQUE KEY `usr_login` (`usr_login`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3 COMMENT='tabela uzytkownikow';


