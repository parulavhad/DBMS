# CloudVault — FA-2 MongoDB Extension

## 📁 New Files Added (FA-2)

```
cloud_storage/
├── config/
│   ├── transaction_demo.sql     ← ACID, Commit, Rollback, Deadlock demos
│   └── schema.sql               ← (original relational schema)
├── mongodb/
│   ├── collections_schema.js    ← MongoDB collection definitions with validators
│   ├── sample_data.js           ← Seed data for all 5 collections
│   └── mongodb_queries.js       ← All 12 queries (CRUD, Aggregation, Index, Search)
└── ...
```

---

## ⚙️ Setup — MySQL (Transaction Demo)

```bash
# Run transaction management demo
mysql -u root -p cloud_storage_db < config/transaction_demo.sql
```

This runs:
- `upgrade_user_plan()` — Atomicity demo (payment + upgrade)
- `upload_file_safe()` — Consistency demo (quota check)
- `move_to_trash()` — Soft delete with rollback
- `share_file_safe()` — Deadlock prevention via ordered locking
- Isolation level demos

---

## ⚙️ Setup — MongoDB

### Step 1 — Start MongoDB
```bash
mongod --dbpath /data/db
```

### Step 2 — Create collections with schema validation
```bash
mongosh < mongodb/collections_schema.js
```

### Step 3 — Insert sample data
```bash
mongosh < mongodb/sample_data.js
```

### Step 4 — Run all 12 queries
```bash
mongosh < mongodb/mongodb_queries.js
```

### Or run everything at once:
```bash
mongosh cloud_vault_nosql < mongodb/collections_schema.js && \
mongosh cloud_vault_nosql < mongodb/sample_data.js && \
mongosh cloud_vault_nosql < mongodb/mongodb_queries.js
```

---

## 📋 MongoDB Collections Summary

| Collection      | Relational Equivalent      | Design Pattern         |
|-----------------|----------------------------|------------------------|
| `users`         | User + StoragePlan         | Embedded plan document |
| `files`         | File + FileVersion + SharedAccess | Embedded arrays |
| `folders`       | Folder                     | Self-referencing       |
| `payments`      | Payment                    | Plan snapshot embedded |
| `activity_logs` | ActivityLog                | Reference by user_id   |

---

## 📋 Query Index (mongodb_queries.js)

| # | Query | Category |
|---|-------|----------|
| Q1 | Insert user with embedded plan | CREATE |
| Q2 | Insert file with tags and version | CREATE |
| Q3 | Find all active files by user | READ |
| Q4 | Find users on Pro/Ultra plan | READ |
| Q5 | Update user plan (upgrade) | UPDATE |
| Q6 | Add new file version (push to array) | UPDATE |
| Q7 | Soft delete file (trash) | DELETE |
| Q8 | Storage usage per user (aggregation + $lookup) | AGGREGATION |
| Q9 | Most shared files (embedded array size) | AGGREGATION |
| Q10 | Create indexes (composite, unique, TTL, text) | INDEXING |
| Q11 | Full-text search on file name and tags | SEARCH |
| Q12 | Regex search by partial file name | SEARCH |

---

*CloudVault FA-2 — Transaction Management & NoSQL Extension*
