-- Add B-Tree Indexes
CREATE INDEX idx_username ON Users(username);
CREATE INDEX idx_status ON Books(status);
CREATE INDEX idx_borrow_date ON BorrowedBooks(borrow_date);
CREATE INDEX idx_notification_status ON Notifications(status);
CREATE INDEX idx_admin_role ON Admins(role);

-- Add Hash Indexes
CREATE INDEX idx_user_id_hash ON BorrowedBooks(user_id) USING HASH;
CREATE INDEX idx_book_id_hash ON BorrowedBooks(book_id) USING HASH;
CREATE INDEX idx_user_notification_hash ON Notifications(user_id) USING HASH;
