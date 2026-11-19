USE cinema;

ALTER TABLE News
    ADD COLUMN admin_id INT UNSIGNED NULL,
    ADD INDEX idx_news_admin (admin_id),
    ADD CONSTRAINT fk_news_author
        FOREIGN KEY (admin_id)
        REFERENCES AdminUser(admin_id)
        ON DELETE SET NULL;

CREATE INDEX idx_news_publication
    ON News (publication_date, news_id);