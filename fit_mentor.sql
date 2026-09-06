CREATE DATABASE IF NOT EXISTS fit_mentor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fit_mentor;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  height_cm DECIMAL(5,2) DEFAULT NULL,
  weight_kg DECIMAL(5,2) DEFAULT NULL,
  goal VARCHAR(255) DEFAULT NULL,
  rank ENUM('Beginner','Intermediate','Advanced','Pro','Legendary') DEFAULT 'Beginner',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL DEFAULT NULL
);

-- Admins
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- BMI history
CREATE TABLE IF NOT EXISTS bmi_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  height_cm DECIMAL(5,2) NOT NULL,
  weight_kg DECIMAL(5,2) NOT NULL,
  bmi DECIMAL(5,2) NOT NULL,
  category VARCHAR(50) NOT NULL,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Progress stats
CREATE TABLE IF NOT EXISTS progress_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  minutes INT DEFAULT 0,
  calories INT DEFAULT 0,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id, date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Workout plans
CREATE TABLE IF NOT EXISTS workout_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  level ENUM('Beginner','Intermediate','Advanced','Pro','Legendary') NOT NULL,
  description TEXT,
  exercises JSON,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Diet plans
CREATE TABLE IF NOT EXISTS diet_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  level ENUM('Beginner','Intermediate','Advanced','Pro','Legendary') NOT NULL,
  description TEXT,
  meals JSON,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed an admin (email: admin@fitmentor.local, password: admin123)
INSERT INTO admins (name, email, password_hash)
VALUES ('Ajin', 'ajin.admin@fitmentor.com', '$argon2id$v=19$m=131072,t=4,p=2$aXlFQWJYTkZHeEFTa010ZQ$iSPZI0Rpbn6U+Q+QV6clckzAnlwOlkdkOAznY5s0AD0');

INSERT INTO admins (name, email, password_hash)
VALUES ('Cheenu', 'cheenu.admin@fitmentor.com', '$argon2id$v=19$m=131072,t=4,p=2$LkdYbzhxcTNYNlprMFZUMQ$Y6r7xwb2H6e/9cK+6Vqi9rI1fSIIfoP6hV6V/UZrsqw');

INSERT INTO admins (name, email, password_hash)
VALUES ('Tharshan', 'tharshan.admin@fitmentor.com', '$argon2id$v=19$m=131072,t=4,p=2$b3FPR1d3WjdiNGQ4eFZlTg$WMcdxzI6u6a/pp8zn1Jj/cX9qScjud3Rdaw7vKP6J64');


-- Sample plans
INSERT INTO workout_plans (title, level, description, exercises) VALUES
('Starter Full-Body', 'Beginner', '3 days/week, full-body basics', JSON_ARRAY('Squats 3x10','Push-ups 3x10','Plank 3x30s'));

INSERT INTO diet_plans (title, level, description, meals) VALUES
('Clean Start', 'Beginner', 'Balanced meals for beginners', JSON_ARRAY('Oats + fruit','Grilled chicken + veggies','Paneer salad'));
