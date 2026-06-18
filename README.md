# Pastimes Threads — Clothing Marketplace

**Pastimes Threads** is a PHP 8 / MySQL e-commerce web application built for the **WEDE6021 Part 2 POE** assessment.

---

## About the Application

**Type of eShop:** A second-hand and artisan clothing marketplace where buyers can browse and purchase garments, and sellers can list their own items for sale.

**Goals:**
- Provide a platform for discovering slow-fashion, pre-loved, and handcrafted clothing.
- Enable sellers to list and manage their own garments with photos and descriptions.
- Give buyers a smooth shopping-cart and checkout experience with order history.
- Provide administrators with full oversight of users, products, sellers, and orders.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Back-end | PHP 8.2 (procedural) |
| Database | MySQL / MariaDB 10.4 |
| Front-end | HTML5, CSS3, vanilla JavaScript |
| Icons | Font Awesome 6 |
| Server | Apache (XAMPP recommended) |

---

## Setup Instructions

### Requirements
- XAMPP (Apache + PHP 8+ + MySQL)
- A modern web browser

### Steps

1. Copy the project folder into your web root:
   ```
   C:\xampp\htdocs\Pastimes\
   ```

2. Open **phpMyAdmin**, create a database named `clothingstore`, then import `clothingstore.sql` (project root) to create all tables and seed sample data.

3. Confirm connection settings in `config/DBConn.php`:
   ```
   host: 127.0.0.1 | db: clothingstore | user: root | pass: (blank)
   ```

4. Start Apache and MySQL in the XAMPP Control Panel, then open:
   ```
   http://localhost/Pastimes/
   ```

---

## Test Accounts

| Role | Username | Password | Notes |
|---|---|---|---|
| Customer | john123 | 123456 | Active |
| Customer | jane123 | 123456 | Active |
| Customer | mike123 | 123456 | Active |
| Customer | sara123 | 123456 | Active |
| Customer | thabo123 | 123456 | Active |
| Admin | admin123 | admin123 | Full admin access |

> New registrations require **admin approval** before login is permitted.

---

## Application Structure

```
Pastimes/
├── index.php                   # Startup / landing page
├── clothingstore.sql           # Database schema + seed data
├── README.md                   # This file
├── config/
│   └── DBConn.php              # Database connection
├── data/
│   └── userData.txt            # Sample user records
├── pages/
│   ├── login.php               # Login (ProcessInput + Login functions)
│   ├── register.php            # Registration → pending approval
│   ├── shop.php                # Product grid with AddToCart + ShowCart
│   ├── product.php             # Single product detail page
│   ├── checkout.php            # Shopping cart (ShowCart / RemoveItem / qty update)
│   ├── process_checkout.php    # Checkout form, order creation, confirmation
│   ├── cart_add.php            # AJAX: AddItem (increases qty if duplicate)
│   ├── cart_remove.php         # AJAX: RemoveItem
│   ├── cart_update.php         # AJAX: update quantity
│   ├── my_orders.php           # Order history + grand total of all purchases
│   ├── sellers_hub.php         # Seller dashboard (list/manage products)
│   ├── seller_register.php     # Seller application form
│   ├── admin.php               # Admin: users + products full CRUD
│   ├── admin_orders.php        # Admin: order management
│   ├── messages.php            # User inbox (buyer ↔ admin)
│   ├── admin_messages.php      # Admin messaging dashboard
│   └── logout.php              # EmptyCart session + destroy → login
└── uploads/                    # Product image uploads
```

---

## Key Features

### Shopping Cart Functions

| Function | File | Behaviour |
|---|---|---|
| **Login** | `login.php` | Authenticates user; blocks `pending` accounts |
| **ProcessInput** | `login.php`, `register.php` | Validates and sanitises all form fields |
| **AddItem** | `cart_add.php` | AJAX; ON DUPLICATE KEY UPDATE increases qty |
| **RemoveItem** | `cart_remove.php` | AJAX; removes one product line from cart |
| **Checkout** | `process_checkout.php` | Creates order, writes `tblorderline`, decrements stock |
| **EmptyCart** | `process_checkout.php` | Deletes all cart rows for user after successful order |

Cart is persisted in `tblcart` (database), so items survive page navigation and browser back.

### Checkout & Order Confirmation

After a successful order the confirmation page displays:
- **Order Number** — formatted as `ORD-00000001`
- **Session ID** — the PHP `session_id()` for reference
- Links to **My Orders** and **Return to Login**

### Order History (`My Orders`)

- Lists every past order with date, status badge, and expandable item breakdown.
- Shows the **grand total of all purchases** (sum across all orders) at the bottom.

### Admin Panel

- **Users** — add, edit, delete, verify (activate) pending accounts.
- **Products** — add, edit, delete listings with image upload.
- Protected by session role check (`role = 'admin'`).

### Seller Hub

- Sellers register their brand and list garments with photos.
- View incoming orders and lifetime revenue for their listings.

---

## Database Tables

| Table | Purpose |
|---|---|
| `tbluser` | Registered customers (`status`: pending / active, `role`: user / admin) |
| `tbladmin` | Admin credentials (separate from tbluser) |
| `tblclothes` | Product catalogue (name, price, stock, image, seller) |
| `tblseller` | Seller profiles and approval status |
| `tblcart` | Active cart items per user |
| `tblorder` | Order headers (total, address, status) |
| `tblorderline` | Order line items (product, quantity, unit price) |
| `tblmessage` | User ↔ admin messages |

---

## Security

- Passwords hashed with **bcrypt** (`password_hash` / `password_verify`).
- All queries use **prepared statements** — no raw string interpolation.
- File uploads validate MIME type and extension.
- Session regenerated on login to prevent session fixation.
- **PRG pattern** on checkout prevents duplicate orders on browser refresh.

---

## Contributors

| Name | Student Number |
|---|---|
| Clarity Masuku | 10438928 |
| Sibusiso Mabena | ST10462532 |
| Unathi Mgandela | ST10447100 |

**Module:** Web Development (Intermediate) — WEDE6021/w

> This project is for academic purposes only. All data used is fictitious.
