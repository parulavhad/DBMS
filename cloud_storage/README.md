# ☁️ CloudVault — Cloud Storage Management System
### Built with PHP + MySQL | DBMS Project

---

## 📁 Project Structure

```
cloud_storage/
├── index.php                   ← Login page
├── config/
│   ├── db.php                  ← DB connection (edit credentials here)
│   └── schema.sql              ← Full DB schema + sample data
├── includes/
│   ├── auth.php                ← Session, login helpers, activity logger
│   ├── header.php              ← Sidebar + page shell (top)
│   └── footer.php              ← Page shell (bottom)
├── pages/
│   ├── register.php            ← User registration
│   ├── dashboard.php           ← Overview stats + recent files
│   ├── files.php               ← Upload, list, filter, trash files
│   ├── folders.php             ← Create & manage folders (nested)
│   ├── shared.php              ← Share files, manage access
│   ├── trash.php               ← Restore or permanently delete
│   ├── versions.php            ← View & manage file versions
│   ├── activity.php            ← Full activity log with filters
│   ├── payments.php            ← Make payments, upgrade plans
│   ├── plans.php               ← View all storage plans
│   └── logout.php              ← Session logout
├── assets/
│   ├── css/style.css           ← Dark industrial stylesheet
│   └── js/main.js              ← UI helpers (icons, confirm, alerts)
└── uploads/                    ← Uploaded files stored here
```

---

## ⚙️ Setup Instructions (Step by Step)

### Step 1 — Requirements
- PHP 8.0+ with `mysqli` extension
- MySQL 8.0+ (or MariaDB 10.6+)
- Apache / Nginx (XAMPP, WAMP, MAMP, or Laragon)

---

### Step 2 — Import the Database

1. Open **phpMyAdmin** (or MySQL CLI)
2. Create a new database: `cloud_storage_db`
3. Import the file: `config/schema.sql`

**OR via MySQL CLI:**
```bash
mysql -u root -p < config/schema.sql
```

---

### Step 3 — Configure Database Credentials

Open `config/db.php` and update:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'cloud_storage_db');
```

---

### Step 4 — Place Project in Web Root

- **XAMPP**: Copy `cloud_storage/` to `C:/xampp/htdocs/`
- **WAMP**: Copy to `C:/wamp64/www/`
- **Linux**: Copy to `/var/www/html/`

---

### Step 5 — Run the Project

Open your browser:
```
http://localhost/cloud_storage/
```

**Demo Login Credentials:**
| Role  | Email                     | Password   |
|-------|---------------------------|------------|
| Admin | admin@cloudstorage.com    | admin123   |
| User  | john@example.com          | john123    |
| User  | jane@example.com          | jane123    |

---

## 🗃️ Database Schema (9 Entities)

| Table         | Description                              | Key Columns                        |
|---------------|------------------------------------------|------------------------------------|
| `User`        | Registered users                         | user_id, email, plan_id            |
| `StoragePlan` | Available storage plans                  | plan_id, storage_limit_gb, price   |
| `Payment`     | Payment transactions                     | payment_id, user_id, plan_id       |
| `Folder`      | Nested folder structure                  | folder_id, parent_folder_id        |
| `File`        | Files uploaded by users                  | file_id, folder_id, file_size_mb   |
| `SharedAccess`| File sharing between users               | share_id, shared_by, shared_with   |
| `Trash`       | Soft-deleted files (30-day auto-purge)   | trash_id, permanent_delete_at      |
| `FileVersion` | Version history per file                 | version_id, version_no             |
| `ActivityLog` | All user actions logged                  | log_id, action_type, ip_address    |

---

## ✨ Features by Page

### 🔐 Login / Register (`index.php`, `register.php`)
- Secure MD5-hashed password login
- Auto-assigns Free plan on registration
- Session-based authentication

### 📊 Dashboard (`dashboard.php`)
- Live stats: total files, folders, shares, trash count
- Storage meter with usage percentage
- Recent uploaded files (grid view)
- Recent activity feed (last 6 actions)

### 📂 Files (`files.php`)
- Upload files with folder + tag assignment
- Storage quota check before upload
- Filter by file type or search by name
- Per-file actions: version history, share, trash
- Auto-creates FileVersion v1 on upload

### 📁 Folders (`folders.php`)
- Create nested folders (parent-child)
- Shows file count per folder
- Delete folders with confirmation

### 🔗 Shared Access (`shared.php`)
- Share any owned file with another user (by email)
- Public share option (no email = public link)
- Set permissions: View / Download / Edit
- Expire or revoke shares instantly
- View files shared with you

### 🗑️ Trash (`trash.php`)
- Files moved to trash with 30-day expiry timer
- Color-coded days remaining (red < 5, orange < 10)
- Restore to original location
- Permanent delete (individual or empty all)

### ⎇ File Versions (`versions.php`)
- Full version history per file
- Manual version save with size tracking
- Days since saved (computed column)
- Delete old versions

### 📋 Activity Log (`activity.php`)
- All actions: Upload, Download, Delete, Restore, Share, Login, Logout, CreateFolder, RenameFile
- IP address tracking
- Filter by action type
- Last 200 entries shown

### 💳 Payments (`payments.php`)
- Browse plans & make payment
- Multiple payment modes (UPI, Card, NetBanking)
- Auto-upgrades user's plan on successful payment
- Full payment history with status badges

### 📦 Plans (`plans.php`)
- Visual plan comparison cards
- Highlights current active plan
- Direct upgrade link

---

## 🔒 Security Notes

> For a production deployment, replace MD5 with `password_hash()` / `password_verify()`.

```php
// Registration (replace MD5):
password_hash($password, PASSWORD_BCRYPT)

// Login verify:
password_verify($inputPassword, $storedHash)
```

---

## 🧩 ER Diagram Mapping

```
User ──subscribes──► StoragePlan
User ──purchased via──► Payment (→ StoragePlan)
User ──creates──► Folder (self-referencing: parent_folder_id)
Folder ──contains──► File
File ──shared via──► SharedAccess (shared_by / shared_with → User)
File ──moved to──► Trash
File ──has versions──► FileVersion
FileVersion ──logged in──► ActivityLog
User ──logged in──► ActivityLog
```

---

*Built for DBMS project — CloudVault*
