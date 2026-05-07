# Pastimes Threads - Clothing Store Web Application

**Pastimes Threads** is a modern, elegant e-commerce web application built for a clothing marketplace. It features a clean, timeless design with a focus on user experience for browsing, shopping, and managing products.

![Pastimes Threads](https://via.placeholder.com/800x400/2a3a3a/fff?text=Pastimes+Threads)

## Features

### User Features
- **User Registration & Login** – Secure password validation (hashed
- **Product Browsing** – Browse clothing items in a beautiful shop interface
- **Product Details** – Detailed product pages
- **Shopping Cart** – Add items to cart and manage selections
- **Checkout System** – Complete purchase flow
- **Responsive Design** – Fully mobile-friendly interface

### Admin & Seller Features
- **Admin Dashboard** (`admin.php`) – Manage the platform
- **Sellers Hub** – Seller-specific tools and management
- **User Role Management** – Support for different user roles (user/seller/admin)

### Technical Features
- **PHP Backend** with MySQL database
- **Session-based Authentication**
- **Clean, modern UI** with custom CSS and Google Fonts
- **Font Awesome icons** for professional look

### Database
Database Name: ClothingStore
Tables:
tblUser
tblAdmin
tblOrder
tblClothes

The database structure is included in:

clothingstore.sql
### Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 8+
- **Database**: MySQL / MariaDB
- **Styling**: Custom CSS with elegant serif + sans-serif typography

Project Structure
Pastimes/
│
├── config/
│   └── DBConn.php
│
├── data/
│   ├── userData.txt
│   └── clothingstore.sql 
│
├── pages/
│   ├── login.php
│   ├── register.php
│   ├── admin.php
│   ├── shop.php
│   ├── checkout.php
│   └── css/
│       └── Style.css
│
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
