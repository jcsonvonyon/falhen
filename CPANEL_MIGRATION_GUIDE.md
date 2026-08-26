# Falhen Media - cPanel Deployment & Migration Guide

This step-by-step guide walks you through migrating and deploying the **Falhen Media PHP Application** to a live cPanel web hosting server.

---

## 📋 Prerequisites & System Requirements

- **Web Server:** Apache or LiteSpeed with `mod_rewrite` enabled.
- **PHP Version:** PHP 8.0, 8.1, 8.2, or 8.3 (PHP 8.1+ recommended).
- **PHP Extensions:** `pdo_mysql`, `curl`, `json`, `mbstring`, `fileinfo`.
- **Database Server:** MySQL 5.7+ or MariaDB 10.3+.
- **cPanel Access:** Active cPanel account with File Manager, MySQL Databases, and phpMyAdmin.

---

## 🛠️ Step 1: Prepare Project Files & Database Schema

1. **Clean Project Archive:**
   - Compress the project root directory (`c:\Users\USER\Documents\Mine\Website\PHP\Falhen`) into a `.zip` archive (e.g. `falhen_media_deploy.zip`).
   - *Excluded local items:* `.git`, `.vscode`, `.idea`, `scratch/`, `error_log`.

2. **Locate Database Schema:**
   - The database setup script is located at [`database/schema.sql`](file:///c:/Users/USER/Documents/Mine/Website/PHP/Falhen/database/schema.sql).

---

## 📁 Step 2: Upload Files to cPanel File Manager

1. Log into your **cPanel Dashboard**.
2. Open **File Manager** from the *Files* section.
3. Navigate to your target web directory:
   - For primary domain: `public_html`
   - For subdomain / addon domain: `public_html/subdomain_folder` or domain folder.
4. Click **Upload** in the top menu and upload `falhen_media_deploy.zip`.
5. Once uploaded (progress bar turns green), return to File Manager.
6. Right-click `falhen_media_deploy.zip` and select **Extract**.
7. Ensure all project files (`index.php`, `admin/`, `config/`, `database/`, `.htaccess`, etc.) reside directly inside your web root.

---

## 🗄️ Step 3: Create MySQL Database & User in cPanel

1. In cPanel, navigate to **MySQL® Databases** (or **MySQL® Database Wizard**).
2. **Create New Database:**
   - Database Name: `yourcpaneluser_falhendb` (Click *Create Database*).
3. **Create Database User:**
   - Username: `yourcpaneluser_dbuser`
   - Password: Use a strong generated password (e.g., `SecurePass2026!#`).
   - Click *Create User*.
4. **Add User to Database:**
   - Under *Add User To Database*, select `yourcpaneluser_dbuser` and `yourcpaneluser_falhendb`.
   - Click **Add**.
   - Select **ALL PRIVILEGES** and click **Make Changes**.

---

## 📥 Step 4: Import Database Schema via phpMyAdmin

1. In cPanel, open **phpMyAdmin** from the *Databases* section.
2. Select your newly created database (`yourcpaneluser_falhendb`) from the left sidebar.
3. Click on the **Import** tab at the top.
4. Click **Choose File** and select `database/schema.sql` from your computer.
5. Scroll down and click **Import** (or **Go**).
6. Verify that the 5 tables are created successfully:
   - `admin_users`
   - `inquiries`
   - `portfolio`
   - `services`
   - `testimonials`

---

## ⚙️ Step 5: Configure Database Connection (`config/db.php`)

Open [`config/db.php`](file:///c:/Users/USER/Documents/Mine/Website/PHP/Falhen/config/db.php) in cPanel File Manager Code Editor and update the default constants with your live cPanel credentials:

```php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost'); // Usually 'localhost' in cPanel
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'yourcpaneluser_falhendb');
define('DB_USER', getenv('DB_USER') ?: 'yourcpaneluser_dbuser');
define('DB_PASS', getenv('DB_PASS') ?: 'YourSecureDatabasePassword123!');
```

*Alternatively, create a `.env` file in the root directory based on `.env.example`:*
```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=yourcpaneluser_falhendb
DB_USER=yourcpaneluser_dbuser
DB_PASS=YourSecureDatabasePassword123!
```

---

## 🔒 Step 6: Enable SSL & Configure `.htaccess`

1. **Free SSL (AutoSSL / Let's Encrypt):**
   - In cPanel, go to **SSL/TLS Status** or **AutoSSL** and run AutoSSL to install a free certificate for your domain.
2. **Force HTTPS Redirect in `.htaccess`:**
   - Open [`.htaccess`](file:///c:/Users/USER/Documents/Mine/Website/PHP/Falhen/.htaccess) in cPanel Code Editor.
   - Uncomment the HTTPS redirect lines (lines 7–8):
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

---

## 🔐 Step 7: Verify File Permissions

Ensure standard secure Linux file permissions in cPanel File Manager:
- **Folders:** `755` (`drwxr-xr-x`)
- **Files:** `644` (`-rw-r--r--`)
- **Writable Directories:** Ensure `config/` directory has `755` permissions so `settings.json`, `studio_tasks.json`, etc. can be updated by PHP.

---

## 🚀 Step 8: Production Verification Checklist

1. **Public Site Verification:**
   - Visit `https://yourdomain.com`
   - Test navigation: Home, About, Services, Portfolio, Blog, Contact.
   - Test Client Inquiry Quote Form at `/contact.php` or home page.

2. **Admin Portal Verification:**
   - Visit `https://yourdomain.com/admin/login.php`
   - **Default Admin Account:**
     - **Username:** `admin`
     - **Password:** `Password123#`
   - **Default Staff Account:**
     - **Username:** `staff`
     - **Password:** `Password123#`

3. **Security Post-Deployment Steps:**
   - Immediately log in to `/admin/index.php?section=staff_accounts` or `/admin/index.php?section=profile` and **change the default admin password**.
