-- Suppress AUTO_INCREMENT zero behavior
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

-- -------------------------------------------------------
-- Database: projectDB
-- -------------------------------------------------------
DROP DATABASE IF EXISTS projectDB;
CREATE DATABASE projectDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE projectDB;

-- Drop tables in reverse FK order
DROP TABLE IF EXISTS FurnitureMaterials;
DROP TABLE IF EXISTS OrderFurnitures;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Staffs;
DROP TABLE IF EXISTS Furnitures;
DROP TABLE IF EXISTS Customers;
DROP TABLE IF EXISTS Materials;

-- -------------------------------------------------------
-- Materials
--   mqty    : physical quantity in warehouse (total stock)
--   mavlqty : available quantity (decreases when orders placed,
--             increases when orders deleted/rejected)
-- -------------------------------------------------------
CREATE TABLE Materials (
    mid     INT          NOT NULL AUTO_INCREMENT,
    mname   VARCHAR(255) NOT NULL,
    mqty    INT          NOT NULL DEFAULT 0,
    mavlqty INT          NOT NULL DEFAULT 0,
    munit   VARCHAR(50)  NOT NULL,
    PRIMARY KEY (mid)
) ENGINE=InnoDB;

INSERT INTO Materials (mname, mqty, mavlqty, munit) VALUES
-- Order 1 uses 2 Oak Planks (chair x1), Order 2 uses 12 Oak Planks (bed x1)
('Oak Wood Plank',   500, 486, 'pcs'),
('Steel Tube',       200, 200, 'meter'),
('Fabric Cloth',     100, 100, 'meter'),
('High Density Foam', 50,  50, 'block');

-- -------------------------------------------------------
-- Customers
-- -------------------------------------------------------
CREATE TABLE Customers (
    cid      INT          NOT NULL AUTO_INCREMENT,
    cname    VARCHAR(255) NOT NULL,
    cpassword VARCHAR(255) NOT NULL,
    ctel     VARCHAR(20)  NOT NULL,
    caddr    VARCHAR(255) NOT NULL,
    company  VARCHAR(255),
    PRIMARY KEY (cid)
) ENGINE=InnoDB;

INSERT INTO Customers (cname, cpassword, ctel, caddr, company) VALUES
('taiman',  'cust123', '23456789', 'Flat A, 12/F, Sunshine Building, Mong Kok, Kowloon',              'ABC Trading Ltd.'),
('siuming', 'cust456', '98765432', 'Room 8, 3/F, Harbour View Court, Tsuen Wan, New Territories',     NULL);

-- -------------------------------------------------------
-- Furnitures  (fimage: filename stored in assets/images/furniture/)
-- -------------------------------------------------------
CREATE TABLE Furnitures (
    fid    INT            NOT NULL AUTO_INCREMENT,
    fname  VARCHAR(255)   NOT NULL,
    fdesc  VARCHAR(500)   NOT NULL,
    fimage VARCHAR(255)   DEFAULT NULL,
    fprice DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (fid)
) ENGINE=InnoDB;

INSERT INTO Furnitures (fname, fdesc, fimage, fprice) VALUES
('Oak Dining Chair',     'Classic style dining chair made of solid oak.',          '1.png', 450.00),
('Large Dining Table',   '6-seater dining table, perfect for families.',           '2.png', 2500.00),
('3-Seater Fabric Sofa', 'Comfortable grey fabric sofa with foam filling.',        '3.png', 3800.00),
('Wooden Wardrobe',      'Double door wardrobe with hanging space.',               '4.png', 1800.00),
('Industrial Bookshelf', 'Modern style bookshelf with steel frame.',               '5.png', 1200.00),
('Queen Size Bed Frame', 'Sturdy bed frame for queen size mattress.',              '6.png', 2200.00);

-- -------------------------------------------------------
-- Staffs
-- -------------------------------------------------------
CREATE TABLE Staffs (
    sid       INT          NOT NULL AUTO_INCREMENT,
    spassword VARCHAR(255) NOT NULL,
    sname     VARCHAR(255) NOT NULL,
    srole     VARCHAR(50)  NOT NULL,
    stel      VARCHAR(20)  NOT NULL,
    PRIMARY KEY (sid)
) ENGINE=InnoDB;

INSERT INTO Staffs (spassword, sname, srole, stel) VALUES
('admin', 'Admin', 'Administrator', '12345678');

-- -------------------------------------------------------
-- Orders
--   ostatus: 1=Open, 2=Approved, 3=Rejected
-- -------------------------------------------------------
CREATE TABLE Orders (
    oid             INT            NOT NULL AUTO_INCREMENT,
    odate           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ototalamount    DECIMAL(10, 2) NOT NULL,
    cid             INT            NOT NULL,
    odeliverydate   DATETIME       NOT NULL,
    odeliveraddress TEXT           NOT NULL,
    ostatus         INT            NOT NULL DEFAULT 1,
    PRIMARY KEY (oid),
    FOREIGN KEY (cid) REFERENCES Customers(cid)
) ENGINE=InnoDB;

INSERT INTO Orders (oid, odate, ototalamount, cid, odeliverydate, odeliveraddress, ostatus) VALUES
(1, '2026-03-01 10:00:00', 450.00,  1, '2026-07-10 14:00:00', 'Flat A, 12/F, Sunshine Building, Mong Kok, Kowloon', 1),
(2, '2026-03-05 09:00:00', 2200.00, 1, '2026-07-12 10:00:00', 'Flat A, 12/F, Sunshine Building, Mong Kok, Kowloon', 2),
(3, '2026-03-10 14:00:00', 3800.00, 2, '2026-07-20 09:00:00', 'Room 8, 3/F, Harbour View Court, Tsuen Wan, NT',     1);

-- -------------------------------------------------------
-- OrderFurnitures  (one furniture item per order in this system)
-- -------------------------------------------------------
CREATE TABLE OrderFurnitures (
    oid  INT NOT NULL,
    fid  INT NOT NULL,
    oqty INT NOT NULL,
    PRIMARY KEY (oid, fid),
    FOREIGN KEY (fid) REFERENCES Furnitures(fid),
    FOREIGN KEY (oid) REFERENCES Orders(oid)
) ENGINE=InnoDB;

INSERT INTO OrderFurnitures (oid, fid, oqty) VALUES
(1, 1, 1),   -- Order 1: Oak Dining Chair x1
(2, 6, 1),   -- Order 2: Queen Size Bed Frame x1
(3, 3, 1);   -- Order 3: 3-Seater Fabric Sofa x1

-- Update mavlqty for order 3 (Sofa uses 5 planks, 10 fabric, 3 foam)
UPDATE Materials SET mavlqty = mavlqty - 5  WHERE mid = 1; -- planks: 486-5=481
UPDATE Materials SET mavlqty = mavlqty - 10 WHERE mid = 3; -- fabric: 100-10=90
UPDATE Materials SET mavlqty = mavlqty - 3  WHERE mid = 4; -- foam:   50-3=47

-- -------------------------------------------------------
-- FurnitureMaterials  (pmqty = units of material per 1 furniture item)
-- -------------------------------------------------------
CREATE TABLE FurnitureMaterials (
    fid   INT NOT NULL,
    mid   INT NOT NULL,
    pmqty INT NOT NULL,
    PRIMARY KEY (fid, mid),
    FOREIGN KEY (fid) REFERENCES Furnitures(fid),
    FOREIGN KEY (mid) REFERENCES Materials(mid)
) ENGINE=InnoDB;

INSERT INTO FurnitureMaterials (fid, mid, pmqty) VALUES
-- 1. Oak Dining Chair: 2 Wood Planks
(1, 1, 2),
-- 2. Large Dining Table: 10 Wood Planks
(2, 1, 10),
-- 3. 3-Seater Fabric Sofa: 5 Wood Planks, 10 Fabric, 3 Foam
(3, 1, 5),
(3, 3, 10),
(3, 4, 3),
-- 4. Wooden Wardrobe: 15 Wood Planks
(4, 1, 15),
-- 5. Industrial Bookshelf: 4 Wood Planks, 6 Steel Tubes
(5, 1, 4),
(5, 2, 6),
-- 6. Queen Size Bed Frame: 12 Wood Planks
(6, 1, 12);

COMMIT;