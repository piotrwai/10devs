1. Lista tabel z ich kolumnami, typami danych i ograniczeniami

### 1.1 users
- usr_id: SERIAL PRIMARY KEY
- usr_login: VARCHAR(50) NOT NULL UNIQUE
- usr_password: VARCHAR(255) NOT NULL
- usr_citybase: VARCHAR(150) NOT NULL
- usr_admin: BOOLEAN NOT NULL DEFAULT FALSE  -- Flaga oznaczająca czy użytkownik jest administratorem (ustawiana ręcznie w bazie danych)
- usr_date_registration: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP

### 1.2 cities
- cit_id: SERIAL PRIMARY KEY
- cit_usr_id: INTEGER NOT NULL REFERENCES users(usr_id) ON DELETE CASCADE
- cit_name: VARCHAR(150) NOT NULL
- cit_desc: VARCHAR(200) NOT NULL
- cit_visited TINYINT(1) NOT NULL DEFAULT 0
- cit_date_created: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- CONSTRAINT cities_unique UNIQUE (cit_usr_id, cit_name)

### 1.3 recom
- rec_id: SERIAL PRIMARY KEY
- rec_usr_id: INTEGER NOT NULL REFERENCES users(usr_id) ON DELETE CASCADE
- rec_cit_id: INTEGER NOT NULL REFERENCES cities(cit_id) ON DELETE CASCADE
- rec_title: VARCHAR(200) NOT NULL
- rec_desc: TEXT NOT NULL
- rec_model: VARCHAR(50) NOT NULL  -- Wartość odpowiadająca modelowi lub 'manual'
- rec_date_created: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- rec_date_modified: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- rec_status: VARCHAR(50) NOT NULL
- rec_done: BOOLEAN NOT NULL DEFAULT FALSE  -- Czy rekomendacja została już odwiedzona przez użytkownika
- CONSTRAINT recom_unique UNIQUE (rec_usr_id, rec_cit_id, rec_title)

### 1.4 ai_logs
- ail_id: SERIAL PRIMARY KEY
- ail_usr_id: INTEGER NOT NULL REFERENCES users(usr_id) ON DELETE CASCADE
- ail_rec_id: INTEGER NOT NULL REFERENCES recom(rec_id) ON DELETE CASCADE
- ail_date: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- ail_status: VARCHAR(50) NOT NULL

### 1.5 ai_inputs
- ain_id: SERIAL PRIMARY KEY
- ain_usr_id: INTEGER NOT NULL REFERENCES users(usr_id) ON DELETE CASCADE
- ain_date: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- ain_content: TEXT NOT NULL
- ain_source: VARCHAR(150)

### 1.6 error_logs
- err_id: SERIAL PRIMARY KEY
- err_type: VARCHAR(100) NOT NULL  -- typ błędu (np. login_error, validation_error, ai_fetch_error, ai_call_error)
- err_message: TEXT NOT NULL       -- opis błędu
- err_timestamp: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP  -- czas wystąpienia błędu
- err_usr_id: INTEGER NULL REFERENCES users(usr_id) ON DELETE SET NULL  -- identyfikator użytkownika, jeśli dotyczy
- err_url: VARCHAR(255) NULL        -- URL wywołania, przy którym wystąpił błąd
- err_payload: TEXT NULL            -- dodatkowe dane, np. payload lub stack trace

2. Relacje między tabelami
- users (1) : cities (N) – relacja przez cit_usr_id
- users (1) : recom (N) – relacja przez rec_usr_id
- cities (1) : recom (N) – relacja przez rec_cit_id
- users (1) : ai_logs (N) – relacja przez ail_usr_id
- recom (1) : ai_logs (N) – relacja przez ail_rec_id
- users (1) : ai_inputs (N) – relacja przez ain_usr_id

3. Indeksy
- Domyślne indeksy tworzone przez PRIMARY KEY i UNIQUE constraints.
- Dodatkowe indeksy:
  - CREATE INDEX idx_cities_cit_usr_id ON cities (cit_usr_id);
  - CREATE INDEX idx_recom_rec_usr_id ON recom (rec_usr_id);
  - CREATE INDEX idx_recom_rec_cit_id ON recom (rec_cit_id);
  - CREATE INDEX idx_ai_logs_ail_usr_id ON ai_logs (ail_usr_id);
  - CREATE INDEX idx_ai_logs_ail_rec_id ON ai_logs (ail_rec_id);
  - CREATE INDEX idx_ai_inputs_ain_usr_id ON ai_inputs (ain_usr_id);

4. Zasady RLS (Row-Level Security)
- Główne tabele (users, cities, recom) będą chronione poprzez widoki filtrujące dane na podstawie usr_id, tak aby użytkownik widział tylko swoje rekordy.
- Alternatywnie, można wykorzystać natywne mechanizmy RLS PostgreSQL, np.:

```sql
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
CREATE POLICY users_policy ON users USING (usr_id = current_setting('app.current_user_id')::integer);

ALTER TABLE cities ENABLE ROW LEVEL SECURITY;
CREATE POLICY cities_policy ON cities USING (cit_usr_id = current_setting('app.current_user_id')::integer);

ALTER TABLE recom ENABLE ROW LEVEL SECURITY;
CREATE POLICY recom_policy ON recom USING (rec_usr_id = current_setting('app.current_user_id')::integer);
```

5. Dodatkowe uwagi
- Wszystkie nazwy tabel i kolumn są w małych literach z prefiksami: usr_, cit_, rec_, ail_, ain_.
- Pola daty mają ustawione domyślnie CURRENT_TIMESTAMP.
- Klucze obce są zdefiniowane z opcją ON DELETE CASCADE w celu zachowania integralności danych. 