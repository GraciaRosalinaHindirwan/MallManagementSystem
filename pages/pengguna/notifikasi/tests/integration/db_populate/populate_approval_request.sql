DELETE FROM 08_approval_requests;

INSERT INTO 08_approval_requests (approval_id, request_number, request_type, title, description, status, current_level, submitted_by, submitted_at, approved_by, approved_at, reject_reason, created_at, updated_at) VALUES

(1, "req-1", "Contract", "", "", "Pending", 1, "", "", "", NOW(), "", NOW(), NOW()),
(2, "req-2", "Contract", "", "", "Pending", 1, "", "", "", NOW(), "", NOW(), NOW()),
(3, "req-3", "Contract", "", "", "Draft", 1, "", "", "", NOW(), "", NOW(), NOW()),
(4, "req-4", "Contract", "", "", "Approved", 1, "", "", "", NOW(), "", NOW(), NOW()),
(5, "req-5", "Contract", "", "", "Rejected", 1, "", "", "", NOW(), "", NOW(), NOW());
