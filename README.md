# Pastimes Threads - Clothing Store Web Application

**Pastimes Threads** is a modern, elegant e-commerce web application built for a clothing marketplace. It features a clean, timeless design with a focus on user experience for browsing, shopping, and managing products.

![Pastimes Threads](https://via.placeholder.com/800x400/2a3a3a/fff?text=Pastimes+Threads)

## Features

| Feature | Description |
| --- | --- |
| User registration & login | Separate flows for buyers and sellers; admin via `tbladmin` |
| Email-based verification | Admin approves new user accounts before first login |
| Shopping cart | Add / update / remove items via AJAX; persisted in `tblcart` |
| Checkout & orders | Creates `tblorder` + `tblorderline`, decrements stock |
| Image upload | Sellers and admin upload product photos stored in `uploads/` |
| Seller dashboard | Add, edit, delete products; view lifetime revenue and order stats |
| Admin – Users | Verify or filter pending/active accounts |
| Admin – Products | Full CRUD over the product catalogue with image support |
| Admin – Sellers | Approve or reject seller applications |
| Admin – Orders | View all orders; update status (Pending → Shipped → Delivered) |
| Messaging | Buyers contact admin; admin replies in a conversation view |
| Order history | Users view past orders with line-item breakdown |
| Responsive UI | Works on desktop and mobile |

---

## Database

**Database name:** `ClothingStore`

| Table | Purpose |
| --- | --- |
| `tblUser` | Registered buyers/sellers; `status` = `pending` / `active` |
| `tbladmin` | Admin credentials |
| `tblclothes` | Product catalogue (name, price, stock, image, seller) |
| `tblseller` | Seller profiles and `approval_status` |
| `tblcart` | Active shopping cart items per user |
| `tblorder` | Order headers (total, address, status) |
| `tblorderline` | Order line items (product, quantity, unit price) |
| `tblmessage` | User ↔ admin messages (auto-created on first visit) |

---

## Installation

### Requirements

- PHP 8.0+
- MySQL / MariaDB
- Apache with `mod_rewrite` (XAMPP or WAMP recommended)

### Steps

1. **Copy the project** into your web root:

   ```text
   C:\xampp\htdocs\Pastimes\
   ```

2. **Create the database** in phpMyAdmin:

   ```sql
   CREATE DATABASE ClothingStore;
   ```

3. **Import the schema and seed data** by visiting:

   ```text
   http://localhost/Pastimes/scripts/createTable.php
   http://localhost/Pastimes/scripts/setup_messages.php
   ```

4. **Verify connection settings** in `config/DBConn.php` (or set environment variables):

   ```text
   DB_HOST=localhost
   DB_USERNAME=root
   DB_PASSWORD=
   DB_DATABASE=ClothingStore
   ```

5. **Open the app:**

   ```text
   http://localhost/Pastimes/
   ```

### Default Admin Login

| Username | Password |
| -------- | -------- |
| `admin123` | `admin123` |

---

## Folder Structure

```text
Pastimes/
├── config/
│   └── DBConn.php              Database connection
├── data/
│   └── userData.txt            Seed user data
├── pages/
│   ├── login.php               User & admin login
│   ├── register.php            Buyer registration
│   ├── shop.php                Product listing (database-driven)
│   ├── product.php             Single product detail page
│   ├── checkout.php            Shopping cart
│   ├── process_checkout.php    Order creation and confirmation
│   ├── my_orders.php           User order history
│   ├── sellers_hub.php         Seller product management + image upload
│   ├── seller_register.php     Seller application form
│   ├── admin.php               Admin: users / products / sellers
│   ├── admin_orders.php        Admin: order management
│   ├── messages.php            User inbox (buyer ↔ admin)
│   ├── admin_messages.php      Admin messaging dashboard
│   ├── cart_add.php            AJAX: add item to cart
│   ├── cart_remove.php         AJAX: remove item from cart
│   ├── cart_update.php         AJAX: update cart quantity
│   └── logout.php              Session destroy
├── scripts/
│   └── createTable.php
│
├── index.php
├── readmefile.txt
└── clothingstore.sql

### Setup Instructions

1. Install Requirements
Install XAMPP or WAMP
Start Apache and MySQL

3. Move Project

Copy the project folder to:

C:\xampp\htdocs\

3. Create Database

Open phpMyAdmin
Create database:
ClothingStore
4. Import Database

Click Import
Upload:
clothingstore.sql

5. Run Application

Open browser:

http://localhost/Pastimes/pages/login.php
 
 ### Login System
Passwords are stored using hashing
Users must be verified by admin before login
Invalid login shows error message
Sticky form retains input values
### Data Files
userData.txt → contains sample user records
Used to preload data into database

### Demonstration

A demonstration video is included showing:

User registration
Login attempt (before verification)
Admin verification
Successful login
Database interaction

### Contributors
Clarity Masuku

Student Number: [10438928]

Sibusiso Mabena

Student Number: [ST10462532]

Unathi Mgandela

Student Number: [ST10447100]

### Module Information
Module Name: Web Development part 2 (Intermediate)
Module Code: WEDE6021/w
### Notes
This project is for academic purposes only
All data used is fictitious
Ensure database connection settings match your local environment
### Version Control

This project is managed using Git and hosted on GitHub:
👉 https://github.com/jBlack-MC/Pastimes
