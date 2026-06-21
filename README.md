# 4523M-phase-2 – Furniture Shop Website

A fully functional PHP + MySQL web application for **ITP4523M Group Project (AY 2526)**, covering both Customer and Staff modules.

---

## Quick Start

### 1. Prerequisites
- **PHP** 8.0+ (or 7.4+ with minor adjustments)
- **MySQL** 5.7+ / MariaDB 10.4+
- A local web server: **XAMPP**, **WAMP**, **MAMP**, or similar

### 2. Import the database
```bash
mysql -u root -p < CreateProjectDB.sql
```
Or use **phpMyAdmin** → Import → select `CreateProjectDB.sql`.

This creates the `projectDB` database with all tables and sample data.

### 3. Place project files
Copy the project folder to your web server root, e.g.:
- XAMPP: `C:\xampp\htdocs\4523M-phase-2\`
- WAMP:  `C:\wamp64\www\4523M-phase-2\`

### 4. Open the site
Navigate to: `http://localhost/4523M-phase-2/`

---

## Sample Credentials

| Role     | Username | Password |
|----------|----------|----------|
| Customer | taiman   | cust123  |
| Customer | siuming  | cust456  |
| Staff    | Admin    | admin    |

---

## Project Structure

```
├── config/
│   ├── db.php          # DB connection + BASE_URL helper
│   └── session.php     # Session helpers & auth guards
├── includes/
│   ├── header.php      # Shared HTML header + navbar
│   ├── footer.php      # Shared HTML footer
│   └── functions.php   # Stock helpers, formatting utilities
├── assets/
│   ├── css/style.css   # Custom stylesheet (Bootstrap 5 base)
│   ├── js/main.js      # UI enhancements
│   └── images/furniture/  # Furniture product images
├── customer/
│   ├── login.php       # Customer login
│   ├── register.php    # New customer registration
│   ├── logout.php
│   ├── index.php       # Browse furniture (sortable)
│   ├── place_order.php # Place an order
│   ├── orders.php      # View & sort own orders
│   ├── profile.php     # Update password / tel / address
│   └── delete_order.php# Delete order (2-day rule + stock restore)
├── staff/
│   ├── login.php       # Staff login
│   ├── logout.php
│   ├── index.php       # Dashboard with stats
│   ├── insert_furniture.php  # Add furniture + materials
│   ├── insert_material.php   # Add material
│   ├── manage_orders.php     # View all orders + material usage
│   ├── update_order.php      # Update order status/qty (POST handler)
│   ├── report.php            # Sales report
│   └── delete_furniture.php  # Delete furniture (no-orders rule)
├── index.php           # Landing page (portal selector)
└── CreateProjectDB.sql # Full DB setup + sample data
```

---

## Database Schema

| Table               | Description |
|---------------------|-------------|
| `Customers`         | Customer accounts |
| `Staffs`            | Staff accounts |
| `Furnitures`        | Furniture products |
| `Materials`         | Raw materials (physical qty + available qty) |
| `FurnitureMaterials`| Materials required per furniture item |
| `Orders`            | Customer orders (status: 1=Open, 2=Approved, 3=Rejected) |
| `OrderFurnitures`   | Furniture items and quantities per order |

---

## Implemented Features

### Customer Module
- ✅ Browse furniture with image, description, price, stock availability (Sold Out if qty=0)
- ✅ Sort by Name, Price, or Availability (ascending / descending)
- ✅ Place order with full validation and material stock deduction (transactional)
- ✅ View order records with all required fields; sortable by 5 columns
- ✅ Update profile: password, contact number, address only
- ✅ Delete order with confirmation; enforces ≥2-day-before-delivery rule and restores stock

### Staff Module
- ✅ Insert furniture item with auto-generated ID; supports multiple materials per item
- ✅ Insert material with auto-generated ID (physical qty → available qty on creation)
- ✅ Manage orders: view order + customer + material usage; update status (Open/Approved/Rejected) and quantity with atomic stock adjustments
- ✅ Sales report: order ID, furniture image/name, total items, total sales amount
- ✅ Delete furniture with confirmation; blocked when related orders exist

### Business Rules
- ✅ Transactions used for all create / update / delete operations affecting orders and stock
- ✅ Available quantity never drops below zero (validated before deduction)
- ✅ Stock deducted on order creation; restored on deletion or rejection
- ✅ Net stock delta applied correctly when staff modifies order quantity or status

---

## Assumptions
1. Each order covers exactly one furniture type (simplification aligned with the order form).
2. Staff login uses plain-text password comparison matching the provided sample data format.
3. "Physical Quantity" is the warehouse total; "Available Quantity" is what can still be used for new orders.
4. The `avl_qty` shown on the Browse page is `min(floor(mavlqty / pmqty))` across all required materials — i.e., how many complete furniture items can still be made.
