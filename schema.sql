

-- -----------------------------------------------
-- Schools
-- -----------------------------------------------
CREATE TABLE schools (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(200)  NOT NULL,
  slug       VARCHAR(200)  NOT NULL UNIQUE,
  city       VARCHAR(100)  DEFAULT NULL,
  country    VARCHAR(100)  DEFAULT NULL,
  created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Subjects
-- -----------------------------------------------
CREATE TABLE subjects (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  slug       VARCHAR(100) NOT NULL UNIQUE,
  color_hex  CHAR(7)      NOT NULL DEFAULT '#2C58F2',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Users
-- -----------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(254)  NOT NULL UNIQUE,
  username      VARCHAR(50)   NOT NULL UNIQUE,
  display_name  VARCHAR(100)  NOT NULL,
  password_hash VARCHAR(255)  NOT NULL,
  school_id     INT UNSIGNED  DEFAULT NULL,
  bio           TEXT          DEFAULT NULL,
  avatar_color  CHAR(7)       NOT NULL DEFAULT '#2C58F2',
  is_verified   TINYINT(1)    NOT NULL DEFAULT 0,
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_users_school
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Materials
-- -----------------------------------------------
CREATE TABLE materials (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uploader_id   INT UNSIGNED  NOT NULL,
  subject_id    INT UNSIGNED  DEFAULT NULL,
  school_id     INT UNSIGNED  DEFAULT NULL,
  title         VARCHAR(200)  NOT NULL,
  description   TEXT          DEFAULT NULL,
  file_path     VARCHAR(500)  NOT NULL,          -- relative to upload root
  original_name VARCHAR(255)  NOT NULL,
  file_size     INT UNSIGNED  NOT NULL,          -- bytes
  mime_type     VARCHAR(100)  NOT NULL,
  file_type     ENUM('pdf','png','jpg') NOT NULL,
  course_code   VARCHAR(30)   DEFAULT NULL,
  semester      VARCHAR(30)   DEFAULT NULL,
  year          YEAR          DEFAULT NULL,
  download_count INT UNSIGNED NOT NULL DEFAULT 0,
  is_approved   TINYINT(1)    NOT NULL DEFAULT 1,
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FULLTEXT INDEX ft_materials (title, description, course_code),

  CONSTRAINT fk_materials_uploader
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_materials_subject
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
  CONSTRAINT fk_materials_school
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Saved Materials (bookmarks)
-- -----------------------------------------------
CREATE TABLE saved_materials (
  user_id     INT UNSIGNED NOT NULL,
  material_id INT UNSIGNED NOT NULL,
  saved_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id, material_id),
  CONSTRAINT fk_saved_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_saved_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Reviews / Ratings
-- -----------------------------------------------
CREATE TABLE reviews (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  material_id INT UNSIGNED NOT NULL,
  reviewer_id INT UNSIGNED NOT NULL,
  rating      TINYINT      NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment     TEXT         DEFAULT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_review (material_id, reviewer_id),
  CONSTRAINT fk_review_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE,
  CONSTRAINT fk_review_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Seed data
-- -----------------------------------------------
INSERT INTO subjects (name, slug, color_hex) VALUES
  ('Mathematics',       'mathematics',       '#2C58F2'),
  ('Biology',           'biology',           '#1FA663'),
  ('Chemistry',         'chemistry',         '#E13A3A'),
  ('Physics',           'physics',           '#1E84D6'),
  ('History',           'history',           '#D9A300'),
  ('Computer Science',  'computer-science',  '#6B3AE1'),
  ('Economics',         'economics',         '#F09000'),
  ('English',           'english',           '#323A4B');

INSERT INTO schools (name, slug, city, country) VALUES
  ('University of the Philippines Diliman', 'up-diliman',   'Quezon City', 'Philippines'),
  ('Ateneo de Manila University',           'admu',         'Quezon City', 'Philippines'),
  ('De La Salle University',                'dlsu',         'Manila',      'Philippines'),
  ('University of Santo Tomas',             'ust',          'Manila',      'Philippines');