SELECT * FROM projectdb.inv_status;
INSERT INTO inv_status (INV_STAT_ID, STAT_NAME) VALUES
(1, Pending),
(2, Paid),
(3, Overdue),
(4, Canceled);

SELECT * FROM projectdb.memb_status;
INSERT INTO memb_status (MEMB_STATUS_ID, MEMB_STATUS_NAME) VALUES
(1, 'Active'),
(2, 'Expired'),
(3, 'Deactivated'),
(4, 'Suspended');

SELECT * FROM projectdb.packages;
INSERT INTO packages (PACKAGE_ID, PACKAGE_NAME) VALUES
(1,  'Basic — 1 Month'),
(2,  'Basic — 3 Month'),
(3,  'Basic — 6 Month'),
(4,  'Basic — 1 Year'),
(5,  'Intermediate — 1 Month'),
(6,  'Intermediate — 3 Month'),
(7,  'Intermediate — 6 Month'),
(8,  'Intermediate — 1 Year'),
(9,  'Ultimate — 1 Month'),
(10, 'Ultimate — 3 Month'),
(11, 'Ultimate — 6 Month'),
(12, 'Ultimate — 1 Year');