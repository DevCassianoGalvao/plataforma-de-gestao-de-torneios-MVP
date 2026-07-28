ALTER TABLE documents
    ADD COLUMN expires_at DATE NULL AFTER status;
