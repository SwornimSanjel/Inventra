INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES
(1, 'low_stock', 'Low Stock: ''MacBook Pro 14" M2'' is below threshold (4 remaining).', 0, DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
(1, 'request_approved', 'Request Approved: ''Ergonomic Chair'' request approved by System Admin.', 0, DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
(1, 'new_user', 'New User: ''Swornim'' has been added to the system.', 0, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 'system_update', 'System Update: Maintenance scheduled for midnight.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'security_alert', 'Security: New login detected', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));
