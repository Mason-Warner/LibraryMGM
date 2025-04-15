alter table notifications
partition by range columns(notification_id) (
	partition p0 values less than (15),
    partition p1 values less than (30), 
    partition p2 values less than (45),
    partition p3 values less than (MAXVALUE));