-- Patch for a database that was already imported from sql/cinema.sql.
-- Paste this into phpMyAdmin (SQL tab, cinema database selected).
--
-- Why: the ticket confirmation email and the ticket on confirmation.php show the
-- movie poster and the screening format. Both already exist in the Movie and
-- Screening tables, they were just not exposed by the view, so PHP would have
-- needed a second query for them. Adding them to v_booking_overview keeps the
-- whole ticket a single read.
--
-- sql/cinema.sql is the source of truth and already contains this definition;
-- this file only exists so nobody has to re-import and lose their test bookings.

USE cinema;

CREATE OR REPLACE VIEW v_booking_overview AS
SELECT b.booking_id,b.customer_id,CONCAT(c.first_name,' ',c.last_name) AS customer_name,
 c.email,b.screening_id,m.movie_id,m.title AS movie_title,m.cover_image,
 sc.screening_date,sc.screening_time,sc.format,
 h.name AS hall_name,b.status,b.booking_date,b.price,
 COALESCE(active.active_seats,0) AS active_seats,
 i.invoice_id,i.invoice_date,i.total AS invoice_total
FROM Booking b JOIN Customer c ON c.customer_id=b.customer_id
JOIN Screening sc ON sc.screening_id=b.screening_id JOIN Movie m ON m.movie_id=sc.movie_id
JOIN MovieHall h ON h.hall_id=sc.hall_id
LEFT JOIN Invoice i ON i.booking_id=b.booking_id
LEFT JOIN (SELECT booking_id,COUNT(*) AS active_seats FROM BookingSeat GROUP BY booking_id) active
 ON active.booking_id=b.booking_id;

-- Check it worked: these three columns should now exist.
-- SELECT movie_id, cover_image, format FROM v_booking_overview LIMIT 1;
