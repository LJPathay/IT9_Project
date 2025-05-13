# Library Information System

## About the Project

This Library Information System is designed to help libraries efficiently manage their books, members, loans, reservations, and fees. Built with Laravel 12.x, it provides a modern, responsive interface for both administrators and users.

---

## Features

- User Authentication (Login, Register)
- Role-based access (Admin, Member)
- Book Management (CRUD, categories, copies)
- Member Management
- Book Loans and Returns
- Reservations
- Fees & Fines Management
- Payment Tracking
- Reporting (Books Borrowed, Fees Collected)
- Responsive UI with Tailwind CSS

---

## User Roles

- **Admin:** Full access to manage books, members, reservations, fees, and reports.
- **Member/User:** Can browse books, make reservations, borrow and return books, and view their own transactions.

---

## Setup Instructions

### Requirements

- PHP >= 8.2
- Composer
- Node.js and NPM
- MySQL or SQLite
- Laravel 12.x

### Installation

```bash
# Clone the repository
git clone https://github.com/LJPathay/IT9_Project.git
cd it9project

# Install PHP dependencies
composer install

# Install JS dependencies and build assets
npm install && npm run dev

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Set up your database credentials in .env
# DB_DATABASE=your_db
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed the database with initial data
php artisan db:seed

# Start the development server
php artisan serve
```

---

## Usage

Once the server is running, visit [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

---

## Database Schema

### Users
- `id`
- `name`
- `email`
- `password`
- `email_verified_at`
- `remember_token`
- `created_at`
- `updated_at`

### Members
- `member_id`
- `user_id`
- `first_name`
- `last_name`
- `middle_name`
- `email`
- `password`
- `contact_number`
- `join_date`

### Books
- `book_id`
- `book_title`
- `isbn`
- `publication_date`
- `publisher`
- `category_id`
- `description`
- `cover`
- `created_at`
- `updated_at`

### Book Copies
- `copy_id`
- `book_id`
- `acquisition_date`
- `status` (available, on_loan, reserved, damaged, lost)
- `created_at`
- `updated_at`

### Categories
- `category_id`
- `name`
- `description`
- `created_at`
- `updated_at`

### Loans
- `loan_id`
- `copy_id`
- `member_id`
- `loan_date`
- `due_date`
- `return_date`
- `created_at`
- `updated_at`

### Reservations
- `reservation_id`
- `book_id`
- `member_id`
- `reservation_date`
- `status` (active, fulfilled, cancelled)
- `created_at`
- `updated_at`

### Fee Types
- `fee_type_id`
- `name`
- `rate`
- `description`
- `created_at`
- `updated_at`

### Transactions
- `transaction_id`
- `member_id`
- `loan_id`
- `fee_type_id`
- `amount`
- `transaction_date`
- `status` (unpaid, partially_paid, paid)
- `created_at`
- `updated_at`

### Payments
- `payment_id`
- `transaction_id`
- `payment_amount`
- `payment_date`
- `payment_method`
- `created_at`
- `updated_at`

---

## Who are the Developers?

**Main Developer:**
- Lebron James Pathay

**Partners:**
- Albert Melendres
- Prince Zchary L Ducog
