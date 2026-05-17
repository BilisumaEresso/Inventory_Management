# 📦 Smart Inventory & Operations Management System

A high-performance, responsive administrative inventory catalog and live executive analytics dashboard for modern warehouse operations. Designed with a premium dark- obsidian color palette and custom-styled metrics tracking.

---

## ✨ Features Overview

### 💎 Executive Dashboard & Operations Room (`public/live-inventory.php`)
- **Obsidian Dark Color Theme**: Implemented a state-of-the-art slate black and deep indigo theme featuring translucent glowing borders, glassmorphic layouts, and smooth animations.
- **Glowing KPI statistic cards**: Displays real-time calculations of Total Valuation, Active Stock Units, and Stock Health indicators (Healthy, Low Stock, and Out of Stock pills).
- **Interactive Visual Analytics**:
  - **Stock Category Capacity Distribution**: Rendered inside a premium donut chart to gauge warehouse capacity.
  - **Daily Activity Trend**: Mapped inside an emerald green line trend chart tracking stock movements over the last 7 days.
- **SaaS Operations Feed**: Compact borderless ledger table presenting transaction history with dynamic green/red indicators.
- **Top Moving Products Leaderboard**: A brand-new widget parsing throughput dynamically to rank items with dedicated ordered badges, metrics sold, and relative progress bars representing throughput proportion.

### 🛡️ Core Administrative Experience
- **Fluid Grid Toolbar**: Centralizes search fields, category filters, and stock alerts inside a single high-density grid control.
- **High-Density Data Table**: Narrowed standard action columns into a single elegant **"Options" pill dropdown list**, saving display space and resolving horizontal viewport overflows.
- **Product Profile Layout (`products/view.php`)**: Features radial-gradient initials badges, hero pricing callouts, a three-tile IN/OUT/NET stock tracker, and custom historical charts.
- **Route Protection Middleware**: Authenticated sessions and hash-verified sign-in screens.

---

## 🛠️ Technology Stack
- **Backend & Logic**: PHP (Modern procedural & relational structure)
- **Database Layer**: PDO prepared SQL transactions (Preventing SQL injections)
- **Frontend Components**: Bootstrap 5, Bootstrap Icons, Google Fonts (Inter)
- **Visualization Library**: Chart.js (Loaded via high-speed CDNs)
- **Live Sync Controller**: Native AJAX JSON fetch cycles

---

## 📁 Repository Structure

```text
├── api/                    # Central admin metrics APIs
├── assets/                 # Custom global styling and script files
│   ├── css/
│   └── js/
├── auth/                   # Secure user login and logout pages
├── categories/             # Dynamic category classification manager
├── config/                 # Relational DB connections and stock helpers
├── includes/               # Reusable header, footer, navigation layers
├── inventory/              # Manual stock adjustments and audit history
├── middleware/             # Route protection and session checkers
├── products/               # Product catalog, registration, and details views
├── public/                 # Public Operational Dashboard and Live APIs
│   ├── api/
│   └── live-inventory.php  # Dark Executive Operations Room
├── DATABASE_SETUP.sql      # Database initialization schema
├── SETUP_GUIDE.md          # Comprehensive deployment steps
├── .gitignore              # Dependency and binary ignore configurations
└── README.md               # Main repository documentation
```

---

## 🚀 Getting Started

### 1. Database Installation
Import `DATABASE_SETUP.sql` into your local database instance:
- **phpMyAdmin**: Create a database named `inventory_db`, select the database, click the **Import** tab, choose `DATABASE_SETUP.sql`, and hit import.
- **MySQL CLI**: Run the following in your terminal:
  ```bash
  mysql -u root -p -e "CREATE DATABASE inventory_db"
  mysql -u root -p inventory_db < DATABASE_SETUP.sql
  ```

### 2. Configure Settings
Configure database connection settings inside `config/db.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'inventory_db');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### 3. Run Local Server
Ensure you have PHP 8.x installed locally. Launch the built-in development server from the repository root:
```bash
php -S localhost:3000
```
Open your browser and navigate to:
- Admin Dashboard: `http://localhost:3000/dashboard.php`
- Live Executive Room: `http://localhost:3000/public/live-inventory.php`

---

## 🛡️ Security & Performance Features
- **Centralized Session Checks**: Prevents unauthorized url routes from loading metadata.
- **Automated Sync Hash Verification**: Client scripts compare polling JSON hashes before redrawing canvases or feeds, minimizing unnecessary DOM reflows and browser memory usage.
- **Clean Git footprint**: All dependencies (`vendor/`), binary objects, local workspace parameters (`.vscode/`), and developer scratch scripts are ignored via `.gitignore` to preserve lightweight, zero-bloat repository assets.
