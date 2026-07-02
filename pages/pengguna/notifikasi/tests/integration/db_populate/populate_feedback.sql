DELETE FROM 05_cs_feedback;

INSERT INTO 05_cs_feedback (nama_pengunjung, rating, komentar, kategori, created_at, updated_at) VALUES 
("pengunjung1", 5, "komentar1", "kategori1", NOW(), NOW()),
("pengunjung2", 4, "komentar2", "kategori2", NOW(), NOW()),
("pengunjung3", 3, "komentar3", "kategori3", NOW(), NOW()),
("pengunjung4", 2, "komentar4", "kategori4", NOW(), NOW()),
("pengunjung5", 1, "komentar5", "kategori5", NOW(), NOW());
