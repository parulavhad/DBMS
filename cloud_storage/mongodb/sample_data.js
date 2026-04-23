/**
 * CloudVault — MongoDB Sample Data (Seed)
 * FA-2: NoSQL Implementation
 *
 * Run: mongosh cloud_vault_nosql < sample_data.js
 */

use('cloud_vault_nosql');

// ─────────────────────────────────────────────
// INSERT USERS
// ─────────────────────────────────────────────
db.users.deleteMany({});

const u1 = db.users.insertOne({
  username: "admin",
  email: "admin@cloudstorage.com",
  password_hash: "$2b$10$adminhashedpassword",
  full_name: "Admin User",
  phone: "9000000001",
  account_age: 400,
  storage_used_mb: 5120.0,
  created_at: new Date("2025-01-01"),
  plan: {
    plan_id: 3,
    plan_name: "Pro",
    storage_limit_gb: 200,
    price: 499.00,
    duration_days: 30,
    expiry_date: new Date("2026-05-30")
  }
});

const u2 = db.users.insertOne({
  username: "john_doe",
  email: "john@example.com",
  password_hash: "$2b$10$johnhashedpassword",
  full_name: "John Doe",
  phone: "9000000002",
  account_age: 120,
  storage_used_mb: 12450.5,
  created_at: new Date("2025-03-01"),
  plan: {
    plan_id: 2,
    plan_name: "Basic",
    storage_limit_gb: 50,
    price: 199.00,
    duration_days: 30,
    expiry_date: new Date("2026-05-01")
  }
});

const u3 = db.users.insertOne({
  username: "jane_doe",
  email: "jane@example.com",
  password_hash: "$2b$10$janehashedpassword",
  full_name: "Jane Doe",
  phone: "9000000003",
  account_age: 90,
  storage_used_mb: 850.0,
  created_at: new Date("2025-04-01"),
  plan: {
    plan_id: 1,
    plan_name: "Free",
    storage_limit_gb: 5,
    price: 0.00,
    duration_days: 365,
    expiry_date: new Date("2026-12-31")
  }
});

print("✅ Users inserted:", u1.insertedId, u2.insertedId, u3.insertedId);

const adminId = u1.insertedId;
const johnId  = u2.insertedId;
const janeId  = u3.insertedId;

// ─────────────────────────────────────────────
// INSERT FOLDERS
// ─────────────────────────────────────────────
db.folders.deleteMany({});

const f1 = db.folders.insertOne({
  folder_name: "Projects",
  folder_path: "/Projects",
  parent_folder_id: null,
  user_id: johnId,
  file_count: 3,
  created_at: new Date("2025-03-05")
});

const f2 = db.folders.insertOne({
  folder_name: "DBMS Assignment",
  folder_path: "/Projects/DBMS Assignment",
  parent_folder_id: f1.insertedId,
  user_id: johnId,
  file_count: 2,
  created_at: new Date("2025-03-10")
});

const f3 = db.folders.insertOne({
  folder_name: "Photos",
  folder_path: "/Photos",
  parent_folder_id: null,
  user_id: janeId,
  file_count: 1,
  created_at: new Date("2025-04-05")
});

print("✅ Folders inserted");

// ─────────────────────────────────────────────
// INSERT FILES
// ─────────────────────────────────────────────
db.files.deleteMany({});

db.files.insertMany([
  {
    file_name: "project_report.pdf",
    file_type: "pdf",
    file_size_mb: 15.75,
    user_id: johnId,
    folder_id: f2.insertedId,
    tags: ["report", "academic", "dbms", "2026"],
    uploaded_at: new Date("2026-03-25"),
    is_deleted: false,
    deleted_at: null,
    permanent_delete_at: null,
    versions: [
      { version_no: 1, version_size_kb: 14850.0, saved_at: new Date("2026-03-25") },
      { version_no: 2, version_size_kb: 16128.0, saved_at: new Date("2026-04-01") }
    ],
    shared_access: [
      { shared_with: janeId, permission: "View", shared_date: new Date("2026-04-02"), is_expired: false }
    ]
  },
  {
    file_name: "machine_learning_notes.pdf",
    file_type: "pdf",
    file_size_mb: 8.25,
    user_id: johnId,
    folder_id: f1.insertedId,
    tags: ["ml", "notes", "academic", "2026"],
    uploaded_at: new Date("2026-03-20"),
    is_deleted: false,
    deleted_at: null,
    permanent_delete_at: null,
    versions: [
      { version_no: 1, version_size_kb: 8448.0, saved_at: new Date("2026-03-20") }
    ],
    shared_access: [
      { shared_with: null, permission: "Download", shared_date: new Date("2026-03-21"), is_expired: false }
    ]
  },
  {
    file_name: "profile_photo.jpg",
    file_type: "jpg",
    file_size_mb: 2.10,
    user_id: janeId,
    folder_id: f3.insertedId,
    tags: ["photo", "personal"],
    uploaded_at: new Date("2026-04-10"),
    is_deleted: false,
    deleted_at: null,
    permanent_delete_at: null,
    versions: [
      { version_no: 1, version_size_kb: 2150.0, saved_at: new Date("2026-04-10") }
    ],
    shared_access: []
  },
  {
    file_name: "old_backup.zip",
    file_type: "zip",
    file_size_mb: 512.00,
    user_id: adminId,
    folder_id: null,
    tags: ["backup", "system"],
    uploaded_at: new Date("2025-12-01"),
    is_deleted: true,
    deleted_at: new Date("2026-04-01"),
    permanent_delete_at: new Date("2026-05-01"),
    versions: [
      { version_no: 1, version_size_kb: 524288.0, saved_at: new Date("2025-12-01") }
    ],
    shared_access: []
  }
]);

print("✅ Files inserted");

// ─────────────────────────────────────────────
// INSERT PAYMENTS
// ─────────────────────────────────────────────
db.payments.deleteMany({});

db.payments.insertMany([
  {
    user_id: johnId,
    amount: 199.00,
    payment_date: new Date("2026-03-01"),
    payment_mode: "UPI",
    status: "Completed",
    plan_snapshot: { plan_name: "Basic", storage_limit_gb: 50, price: 199.00 }
  },
  {
    user_id: janeId,
    amount: 0.00,
    payment_date: new Date("2026-03-01"),
    payment_mode: "None",
    status: "Completed",
    plan_snapshot: { plan_name: "Free", storage_limit_gb: 5, price: 0.00 }
  },
  {
    user_id: adminId,
    amount: 499.00,
    payment_date: new Date("2026-04-01"),
    payment_mode: "Card",
    status: "Completed",
    plan_snapshot: { plan_name: "Pro", storage_limit_gb: 200, price: 499.00 }
  }
]);

print("✅ Payments inserted");

// ─────────────────────────────────────────────
// INSERT ACTIVITY LOGS
// ─────────────────────────────────────────────
db.activity_logs.deleteMany({});

db.activity_logs.insertMany([
  { user_id: johnId,  file_id: null, action_type: "Login",        action_date: new Date("2026-04-20T08:00:00Z"), ip_address: "192.168.1.10" },
  { user_id: johnId,  file_id: null, action_type: "Upload",       action_date: new Date("2026-04-20T08:05:00Z"), ip_address: "192.168.1.10" },
  { user_id: johnId,  file_id: null, action_type: "CreateFolder", action_date: new Date("2026-04-20T08:10:00Z"), ip_address: "192.168.1.10" },
  { user_id: janeId,  file_id: null, action_type: "Login",        action_date: new Date("2026-04-20T09:00:00Z"), ip_address: "192.168.1.20" },
  { user_id: janeId,  file_id: null, action_type: "Download",     action_date: new Date("2026-04-20T09:15:00Z"), ip_address: "192.168.1.20" },
  { user_id: adminId, file_id: null, action_type: "Login",        action_date: new Date("2026-04-20T10:00:00Z"), ip_address: "10.0.0.1" },
  { user_id: adminId, file_id: null, action_type: "Delete",       action_date: new Date("2026-04-20T10:05:00Z"), ip_address: "10.0.0.1" },
  { user_id: johnId,  file_id: null, action_type: "Share",        action_date: new Date("2026-04-20T11:00:00Z"), ip_address: "192.168.1.10" },
  { user_id: johnId,  file_id: null, action_type: "Logout",       action_date: new Date("2026-04-20T12:00:00Z"), ip_address: "192.168.1.10" },
  { user_id: janeId,  file_id: null, action_type: "Logout",       action_date: new Date("2026-04-20T12:30:00Z"), ip_address: "192.168.1.20" },
]);

print("✅ Activity logs inserted");
print("🎉 CloudVault MongoDB seed data complete!");
