-- // All db code for otp backend task 2 3

-- CREATE TABLE password_resets (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     email VARCHAR(255) NOT NULL,
--     otp_code VARCHAR(6) NOT NULL,
--     attempts INT NOT NULL DEFAULT 0,
--     is_verified TINYINT(1) NOT NULL DEFAULT 0,
--     reset_token VARCHAR(255) DEFAULT NULL,
--     expires_at DATETIME NOT NULL,
--     verified_at DATETIME DEFAULT NULL,
--     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
-- );

-- CREATE TABLE admin (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     full_name VARCHAR(100) NOT NULL,
--     email VARCHAR(255) NOT NULL UNIQUE,
--     password_hash VARCHAR(255) DEFAULT NULL,
--     role VARCHAR(20) NOT NULL DEFAULT 'admin'
-- );

-- INSERT INTO admin (full_name, email, role)
-- VALUES ('Swornim Sanjel', 'yourgmail@gmail.com', 'admin');

-- //email connection: otp one

-- SELECT * FROM admin;

-- UPDATE admin
-- SET email = 'xettrikenzon@gmail.com'
-- WHERE email = 'yourgmail@gmail.com';
