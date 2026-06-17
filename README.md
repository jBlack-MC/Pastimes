# Pastimes — Local Fashion Marketplace

A PHP/MySQL e-commerce platform for buying, selling, and managing local and second-hand clothing.

**Module:** Web Development Part 2 (WEDE6021/w)

---

## Overview

Pastimes is a multi-role web application with three user types:

- **Buyers** – Browse products, manage a cart, checkout, and track orders.
- **Sellers** – Register, upload product images, and manage inventory from a dedicated hub.
- **Admins** – Verify users, approve sellers, manage the full product catalogue, process orders, and communicate via messages.

---

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
│   ├── createTable.php         Creates tblUser and inserts seed data
│   └── setup_messages.php      Creates tblmessage table
├── uploads/                    Product images (move_uploaded_file target)
├── index.php                   Landing page
└── README.md
```

---

## Video Demonstration

The walkthrough video covers:

**User flow:** Register → Verification pending → Admin approves → Login → Browse shop → Add to cart → Update quantity → Remove item → Checkout → View order number

**Seller flow:** Apply as seller → Admin approves → Login → Add product (with image) → Edit product → Delete product

**Admin flow:** Verify users → Approve sellers → Add/edit/delete products → View and update orders → Read and reply to messages

**Database:** Show phpMyAdmin with all tables; show an order inserted into `tblorder`; show `tblcart` cleared after checkout

---

## Contributors

| Name | Student Number |
| --- | --- |
| Clarity Masuku | 10438928 |
| Sibusiso Mabena | ST10462532 |
| Unathi Mgandela | ST10447100 |

---

## GitHub

[https://github.com/jBlack-MC/Pastimes](https://github.com/jBlack-MC/Pastimes)

---

> This project is for academic purposes. All data is fictitious. Ensure database settings match your local environment.
