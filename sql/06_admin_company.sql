USE cinema;

CREATE TABLE IF NOT EXISTS OpeningHours (
    weekday TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    opens_at TIME NULL,
    closes_at TIME NULL,
    is_closed BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_hours_weekday CHECK (weekday BETWEEN 1 AND 7),
    CONSTRAINT chk_hours_times CHECK (
        (is_closed = TRUE AND opens_at IS NULL AND closes_at IS NULL)
        OR
        (is_closed = FALSE AND opens_at IS NOT NULL
         AND closes_at IS NOT NULL AND opens_at < closes_at)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS SpecialHours (
    special_hours_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    special_date DATE NOT NULL,
    label VARCHAR(100) NOT NULL,
    opens_at TIME NULL,
    closes_at TIME NULL,
    is_closed BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uq_special_date (special_date),
    CONSTRAINT chk_special_times CHECK (
        (is_closed = TRUE AND opens_at IS NULL AND closes_at IS NULL)
        OR
        (is_closed = FALSE AND opens_at IS NOT NULL
         AND closes_at IS NOT NULL AND opens_at < closes_at)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ContactInfo (
    contact_info_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind VARCHAR(20) NOT NULL,
    label VARCHAR(80) NOT NULL,
    value VARCHAR(255) NOT NULL,
    CONSTRAINT chk_contact_kind CHECK (kind IN ('address', 'phone', 'email'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ContactInfo (kind, label, value)
SELECT 'address', 'Cinema address', c.address
FROM CompanyInfo AS c
WHERE c.company_info_id = (SELECT MIN(company_info_id) FROM CompanyInfo)
  AND c.address <> ''
  AND NOT EXISTS (
      SELECT 1 FROM ContactInfo
      WHERE kind = 'address' AND value = c.address
  );

INSERT INTO ContactInfo (kind, label, value)
SELECT 'phone', 'Phone', c.phone
FROM CompanyInfo AS c
WHERE c.company_info_id = (SELECT MIN(company_info_id) FROM CompanyInfo)
  AND c.phone <> ''
  AND NOT EXISTS (
      SELECT 1 FROM ContactInfo
      WHERE kind = 'phone' AND value = c.phone
  );

INSERT INTO ContactInfo (kind, label, value)
SELECT 'email', 'Email', c.email
FROM CompanyInfo AS c
WHERE c.company_info_id = (SELECT MIN(company_info_id) FROM CompanyInfo)
  AND c.email <> ''
  AND NOT EXISTS (
      SELECT 1 FROM ContactInfo
      WHERE kind = 'email' AND value = c.email
  );