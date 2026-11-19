USE cinema;

ALTER TABLE Screening
    ADD COLUMN price_kr SMALLINT UNSIGNED NOT NULL DEFAULT 150;

CREATE TABLE front_page_showing (
    screening_id INT UNSIGNED NOT NULL PRIMARY KEY,
    showing_date DATE NOT NULL,
    CONSTRAINT fk_front_page_screening
        FOREIGN KEY (screening_id)
        REFERENCES Screening(screening_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;