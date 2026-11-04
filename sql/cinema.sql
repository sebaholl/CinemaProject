-- Import into a SELECTED EMPTY database. This file creates objects and demo data.
-- It never drops, truncates or replaces an existing table. Use a staging database first.
USE cinema;

-- Import into a selected, empty MySQL 8.0.16+ database.
SET NAMES utf8mb4;
SET time_zone = '+00:00';
CREATE TABLE Customer (
 customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 first_name VARCHAR(100) NOT NULL,
 last_name VARCHAR(100) NOT NULL,
 email VARCHAR(254) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 UNIQUE KEY uq_customer_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE AdminUser (
 admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 login VARCHAR(100) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 UNIQUE KEY uq_admin_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE Movie (
 movie_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(200) NOT NULL,
 description TEXT NOT NULL,
 cover_image VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE News (
 news_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(200) NOT NULL,
 description TEXT NOT NULL,
 publication_date DATE NOT NULL,
 image VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE CompanyInfo (
 company_info_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 opening_hours VARCHAR(255) NOT NULL,
 phone VARCHAR(40) NOT NULL,
 address VARCHAR(255) NOT NULL,
 email VARCHAR(254) NOT NULL,
 description TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE MovieHall (
 hall_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 description TEXT NOT NULL,
 UNIQUE KEY uq_hall_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE Seat (
 seat_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 hall_id INT UNSIGNED NOT NULL,
 row_label VARCHAR(5) NOT NULL,
 seat_number SMALLINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_hall_seat (hall_id,row_label,seat_number),
 CONSTRAINT chk_seat_number CHECK(seat_number > 0),
 CONSTRAINT fk_seat_hall FOREIGN KEY(hall_id) REFERENCES MovieHall(hall_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE Screening (
 screening_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 movie_id INT UNSIGNED NOT NULL,
 hall_id INT UNSIGNED NOT NULL,
 screening_date DATE NOT NULL,
 screening_time TIME NOT NULL,
 UNIQUE KEY uq_hall_screening_start (hall_id,screening_date,screening_time),
 CONSTRAINT fk_screening_movie FOREIGN KEY(movie_id) REFERENCES Movie(movie_id) ON DELETE RESTRICT,
 CONSTRAINT fk_screening_hall FOREIGN KEY(hall_id) REFERENCES MovieHall(hall_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE Booking (
 booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NOT NULL,
 screening_id INT UNSIGNED NOT NULL,
 price DECIMAL(10,2) NOT NULL,
 booking_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
 UNIQUE KEY uq_booking_screening (booking_id,screening_id),
 CONSTRAINT chk_booking_price CHECK(price >= 0),
 CONSTRAINT chk_booking_status CHECK(status IN ('confirmed','cancelled')),
 CONSTRAINT fk_booking_customer FOREIGN KEY(customer_id) REFERENCES Customer(customer_id) ON DELETE RESTRICT,
 CONSTRAINT fk_booking_screening FOREIGN KEY(screening_id) REFERENCES Screening(screening_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- screening_id is a constrained copy of Booking.screening_id, required to enforce
-- UNIQUE(screening_id,seat_id) atomically across different bookings.
CREATE TABLE BookingSeat (
 booking_id INT UNSIGNED NOT NULL,
 screening_id INT UNSIGNED NOT NULL,
 seat_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(booking_id,seat_id),
 UNIQUE KEY uq_screening_seat(screening_id,seat_id),
 CONSTRAINT fk_bookingseat_booking FOREIGN KEY(booking_id,screening_id)
  REFERENCES Booking(booking_id,screening_id) ON DELETE RESTRICT,
 CONSTRAINT fk_bookingseat_seat FOREIGN KEY(seat_id) REFERENCES Seat(seat_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE Invoice (
 invoice_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id INT UNSIGNED NOT NULL,
 booking_date DATETIME NOT NULL,
 invoice_date DATE NOT NULL,
 total DECIMAL(10,2) NOT NULL,
 UNIQUE KEY uq_invoice_booking(booking_id),
 CONSTRAINT chk_invoice_total CHECK(total >= 0),
 CONSTRAINT fk_invoice_booking FOREIGN KEY(booking_id) REFERENCES Booking(booking_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_screening_date_time ON Screening(screening_date,screening_time);
CREATE INDEX idx_booking_customer ON Booking(customer_id);
-- uq_screening_seat and uq_hall_seat were defined in 01 as unique indexes.

CREATE VIEW v_screenings_with_seats_left AS
SELECT sc.screening_id,sc.movie_id,m.title AS movie_title,sc.hall_id,h.name AS hall_name,
 sc.screening_date,sc.screening_time,
 COALESCE(cap.total_seats,0) AS total_seats,
 COALESCE(taken.booked_seats,0) AS booked_seats,
 CAST(COALESCE(cap.total_seats,0) AS SIGNED)-CAST(COALESCE(taken.booked_seats,0) AS SIGNED) AS seats_left
FROM Screening sc JOIN Movie m ON m.movie_id=sc.movie_id
JOIN MovieHall h ON h.hall_id=sc.hall_id
LEFT JOIN (SELECT hall_id,COUNT(*) AS total_seats FROM Seat GROUP BY hall_id) cap ON cap.hall_id=sc.hall_id
LEFT JOIN (SELECT screening_id,COUNT(*) AS booked_seats FROM BookingSeat GROUP BY screening_id) taken ON taken.screening_id=sc.screening_id;
CREATE VIEW v_booking_overview AS
SELECT b.booking_id,b.customer_id,CONCAT(c.first_name,' ',c.last_name) AS customer_name,
 c.email,b.screening_id,m.title AS movie_title,sc.screening_date,sc.screening_time,
 h.name AS hall_name,b.status,b.booking_date,b.price,
 COALESCE(active.active_seats,0) AS active_seats,
 i.invoice_id,i.invoice_date,i.total AS invoice_total
FROM Booking b JOIN Customer c ON c.customer_id=b.customer_id
JOIN Screening sc ON sc.screening_id=b.screening_id JOIN Movie m ON m.movie_id=sc.movie_id
JOIN MovieHall h ON h.hall_id=sc.hall_id
LEFT JOIN Invoice i ON i.booking_id=b.booking_id
LEFT JOIN (SELECT booking_id,COUNT(*) AS active_seats FROM BookingSeat GROUP BY booking_id) active
 ON active.booking_id=b.booking_id;

DELIMITER $$
CREATE TRIGGER trg_bookingseat_before_insert BEFORE INSERT ON BookingSeat FOR EACH ROW
BEGIN
 DECLARE v_status VARCHAR(20) DEFAULT NULL;
 DECLARE v_hall INT UNSIGNED DEFAULT NULL;
 DECLARE v_seat_hall INT UNSIGNED DEFAULT NULL;
 DECLARE v_date DATE DEFAULT NULL;
 DECLARE v_time TIME DEFAULT NULL;
 SELECT status INTO v_status FROM Booking WHERE booking_id=NEW.booking_id FOR UPDATE;
 IF v_status IS NULL OR v_status <> 'confirmed' THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Booking must be confirmed';
 END IF;
 SELECT hall_id,screening_date,screening_time INTO v_hall,v_date,v_time
 FROM Screening WHERE screening_id=NEW.screening_id;
 SELECT hall_id INTO v_seat_hall FROM Seat WHERE seat_id=NEW.seat_id;
 IF v_date IS NULL OR v_seat_hall IS NULL THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Screening or seat missing';
 END IF;
 IF TIMESTAMP(v_date,v_time) <= UTC_TIMESTAMP() THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Screening has already started';
 END IF;
 IF v_hall <> v_seat_hall THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Seat is in another hall';
 END IF;
END$$
CREATE TRIGGER trg_booking_after_update AFTER UPDATE ON Booking FOR EACH ROW
BEGIN
 IF OLD.status <> 'cancelled' AND NEW.status='cancelled' THEN
  DELETE FROM BookingSeat WHERE booking_id=NEW.booking_id;
 END IF;
END$$
CREATE TRIGGER trg_bookingseat_before_update BEFORE UPDATE ON BookingSeat FOR EACH ROW
BEGIN
 SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Seat allocation cannot be changed; cancel and rebook';
END$$
CREATE TRIGGER trg_booking_before_update BEFORE UPDATE ON Booking FOR EACH ROW
BEGIN
 IF NEW.customer_id <> OLD.customer_id OR NEW.screening_id <> OLD.screening_id THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Booking customer and screening cannot change';
 END IF;
 IF OLD.status='cancelled' AND NEW.status <> 'cancelled' THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cancelled bookings cannot be reactivated';
 END IF;
END$$
DELIMITER ;

-- Demo records: film titles are real and source-checked; all schedules, halls, users and bookings are fictional. The PHP bcrypt hash is for "password".
INSERT INTO Customer(customer_id,first_name,last_name,email,password_hash) VALUES
(1,'Alice','Demo','alice@example.test','$2y$10$wXtHIGzmhLYMwbfkdxABnev1fQT8tPQgC2kxJ/nzoYSQCXJby54rK'),
(2,'Bob','Demo','bob@example.test','$2y$10$wXtHIGzmhLYMwbfkdxABnev1fQT8tPQgC2kxJ/nzoYSQCXJby54rK'),
(3,'Charlie','Demo','charlie@example.test','$2y$10$wXtHIGzmhLYMwbfkdxABnev1fQT8tPQgC2kxJ/nzoYSQCXJby54rK');
INSERT INTO AdminUser(admin_id,name,login,password_hash) VALUES(1,'Demo Admin','demo-admin','$2y$10$wXtHIGzmhLYMwbfkdxABnev1fQT8tPQgC2kxJ/nzoYSQCXJby54rK');
-- Film titles verified on 2026-11-04 at https://www.nfbio.dk/biografer/esbjerg-broen.
-- Descriptions are original project metadata, not copied synopses. Artwork is intentionally absent.
INSERT INTO Movie(movie_id,title,description,cover_image) VALUES
(1,'Fornuft og følelse','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(2,'Dobbeltfejl','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(3,'Løvehjerte','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(4,'Wildwood','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(5,'Clayface','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(6,'Råplan','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(7,'Den glemte Ø','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(8,'The Influencer Project','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(9,'Street Fighter','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL),
(10,'The Odyssey','Listed in Esbjerg Broen cinema programme on 4 November 2026. Demo project listing; screening times below are not official.',NULL);
INSERT INTO MovieHall(hall_id,name,description) VALUES(1,'Hall One','80 seats'),(2,'Hall Two','80 seats');
INSERT INTO Seat(seat_id,hall_id,row_label,seat_number) VALUES
(1,1,'A',1),
(2,1,'A',2),
(3,1,'A',3),
(4,1,'A',4),
(5,1,'A',5),
(6,1,'A',6),
(7,1,'A',7),
(8,1,'A',8),
(9,1,'A',9),
(10,1,'A',10),
(11,1,'B',1),
(12,1,'B',2),
(13,1,'B',3),
(14,1,'B',4),
(15,1,'B',5),
(16,1,'B',6),
(17,1,'B',7),
(18,1,'B',8),
(19,1,'B',9),
(20,1,'B',10),
(21,1,'C',1),
(22,1,'C',2),
(23,1,'C',3),
(24,1,'C',4),
(25,1,'C',5),
(26,1,'C',6),
(27,1,'C',7),
(28,1,'C',8),
(29,1,'C',9),
(30,1,'C',10),
(31,1,'D',1),
(32,1,'D',2),
(33,1,'D',3),
(34,1,'D',4),
(35,1,'D',5),
(36,1,'D',6),
(37,1,'D',7),
(38,1,'D',8),
(39,1,'D',9),
(40,1,'D',10),
(41,1,'E',1),
(42,1,'E',2),
(43,1,'E',3),
(44,1,'E',4),
(45,1,'E',5),
(46,1,'E',6),
(47,1,'E',7),
(48,1,'E',8),
(49,1,'E',9),
(50,1,'E',10),
(51,1,'F',1),
(52,1,'F',2),
(53,1,'F',3),
(54,1,'F',4),
(55,1,'F',5),
(56,1,'F',6),
(57,1,'F',7),
(58,1,'F',8),
(59,1,'F',9),
(60,1,'F',10),
(61,1,'G',1),
(62,1,'G',2),
(63,1,'G',3),
(64,1,'G',4),
(65,1,'G',5),
(66,1,'G',6),
(67,1,'G',7),
(68,1,'G',8),
(69,1,'G',9),
(70,1,'G',10),
(71,1,'H',1),
(72,1,'H',2),
(73,1,'H',3),
(74,1,'H',4),
(75,1,'H',5),
(76,1,'H',6),
(77,1,'H',7),
(78,1,'H',8),
(79,1,'H',9),
(80,1,'H',10);
INSERT INTO Seat(seat_id,hall_id,row_label,seat_number) VALUES
(81,2,'A',1),
(82,2,'A',2),
(83,2,'A',3),
(84,2,'A',4),
(85,2,'A',5),
(86,2,'A',6),
(87,2,'A',7),
(88,2,'A',8),
(89,2,'A',9),
(90,2,'A',10),
(91,2,'B',1),
(92,2,'B',2),
(93,2,'B',3),
(94,2,'B',4),
(95,2,'B',5),
(96,2,'B',6),
(97,2,'B',7),
(98,2,'B',8),
(99,2,'B',9),
(100,2,'B',10),
(101,2,'C',1),
(102,2,'C',2),
(103,2,'C',3),
(104,2,'C',4),
(105,2,'C',5),
(106,2,'C',6),
(107,2,'C',7),
(108,2,'C',8),
(109,2,'C',9),
(110,2,'C',10),
(111,2,'D',1),
(112,2,'D',2),
(113,2,'D',3),
(114,2,'D',4),
(115,2,'D',5),
(116,2,'D',6),
(117,2,'D',7),
(118,2,'D',8),
(119,2,'D',9),
(120,2,'D',10),
(121,2,'E',1),
(122,2,'E',2),
(123,2,'E',3),
(124,2,'E',4),
(125,2,'E',5),
(126,2,'E',6),
(127,2,'E',7),
(128,2,'E',8),
(129,2,'E',9),
(130,2,'E',10),
(131,2,'F',1),
(132,2,'F',2),
(133,2,'F',3),
(134,2,'F',4),
(135,2,'F',5),
(136,2,'F',6),
(137,2,'F',7),
(138,2,'F',8),
(139,2,'F',9),
(140,2,'F',10),
(141,2,'G',1),
(142,2,'G',2),
(143,2,'G',3),
(144,2,'G',4),
(145,2,'G',5),
(146,2,'G',6),
(147,2,'G',7),
(148,2,'G',8),
(149,2,'G',9),
(150,2,'G',10),
(151,2,'H',1),
(152,2,'H',2),
(153,2,'H',3),
(154,2,'H',4),
(155,2,'H',5),
(156,2,'H',6),
(157,2,'H',7),
(158,2,'H',8),
(159,2,'H',9),
(160,2,'H',10);
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(1,1,1,DATE_ADD(UTC_DATE(),INTERVAL 1 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(2,2,2,DATE_ADD(UTC_DATE(),INTERVAL 1 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(3,3,1,DATE_ADD(UTC_DATE(),INTERVAL 2 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(4,4,2,DATE_ADD(UTC_DATE(),INTERVAL 2 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(5,5,1,DATE_ADD(UTC_DATE(),INTERVAL 3 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(6,6,2,DATE_ADD(UTC_DATE(),INTERVAL 3 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(7,7,1,DATE_ADD(UTC_DATE(),INTERVAL 4 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(8,8,2,DATE_ADD(UTC_DATE(),INTERVAL 4 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(9,9,1,DATE_ADD(UTC_DATE(),INTERVAL 5 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(10,10,2,DATE_ADD(UTC_DATE(),INTERVAL 5 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(11,1,1,DATE_ADD(UTC_DATE(),INTERVAL 6 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(12,2,2,DATE_ADD(UTC_DATE(),INTERVAL 6 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(13,3,1,DATE_ADD(UTC_DATE(),INTERVAL 7 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(14,4,2,DATE_ADD(UTC_DATE(),INTERVAL 7 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(15,5,1,DATE_ADD(UTC_DATE(),INTERVAL 8 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(16,6,2,DATE_ADD(UTC_DATE(),INTERVAL 8 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(17,7,1,DATE_ADD(UTC_DATE(),INTERVAL 9 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(18,8,2,DATE_ADD(UTC_DATE(),INTERVAL 9 DAY),'20:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(19,9,1,DATE_ADD(UTC_DATE(),INTERVAL 10 DAY),'18:00:00');
INSERT INTO Screening(screening_id,movie_id,hall_id,screening_date,screening_time) VALUES(20,10,2,DATE_ADD(UTC_DATE(),INTERVAL 10 DAY),'20:00:00');
INSERT INTO News(title,description,publication_date,image) VALUES('Opening week','Fictional announcement',UTC_DATE(),NULL),('New films','Fictional update',UTC_DATE(),NULL),('Seats','Fictional update',UTC_DATE(),NULL),('Welcome','Fictional update',UTC_DATE(),NULL);
INSERT INTO CompanyInfo(opening_hours,phone,address,email,description) VALUES('Daily 10:00–23:00','+45 00000000','Example Street 1, 6700 Esbjerg','cinema@example.test','Fictional academic cinema');
INSERT INTO Booking(booking_id,customer_id,screening_id,price) VALUES(1,1,1,200);
INSERT INTO BookingSeat(booking_id,screening_id,seat_id) VALUES(1,1,1);
INSERT INTO BookingSeat(booking_id,screening_id,seat_id) VALUES(1,1,2);
INSERT INTO Invoice(booking_id,booking_date,invoice_date,total) SELECT booking_id,booking_date,UTC_DATE(),price FROM Booking WHERE booking_id=1;
INSERT INTO Booking(booking_id,customer_id,screening_id,price) VALUES(2,2,2,200);
INSERT INTO BookingSeat(booking_id,screening_id,seat_id) VALUES(2,2,81);
INSERT INTO BookingSeat(booking_id,screening_id,seat_id) VALUES(2,2,82);
INSERT INTO Invoice(booking_id,booking_date,invoice_date,total) SELECT booking_id,booking_date,UTC_DATE(),price FROM Booking WHERE booking_id=2;
INSERT INTO Booking(booking_id,customer_id,screening_id,price) VALUES(3,3,3,100);
INSERT INTO BookingSeat(booking_id,screening_id,seat_id) VALUES(3,3,3);
INSERT INTO Invoice(booking_id,booking_date,invoice_date,total) SELECT booking_id,booking_date,UTC_DATE(),price FROM Booking WHERE booking_id=3;
