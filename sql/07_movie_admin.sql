USE cinema;

ALTER TABLE Movie
    ADD COLUMN age_rating VARCHAR(20) NOT NULL DEFAULT 'Not rated',
    ADD COLUMN release_date DATE NULL;