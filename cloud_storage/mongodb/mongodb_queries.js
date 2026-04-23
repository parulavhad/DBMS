/**
 * CloudVault — MongoDB Queries (FA-2)
 * 12 Queries: CRUD, Aggregation, Indexing, Search
 *
 * Run: mongosh cloud_vault_nosql < mongodb_queries.js
 */

use('cloud_vault_nosql');

print("\n========================================");
print("  CloudVault — MongoDB Queries (FA-2)");
print("========================================\n");

// ══════════════════════════════════════════════
// SECTION A: CREATE OPERATIONS
// ══════════════════════════════════════════════

print("── Q1: INSERT a new user with embedded plan ──");
// SQL Equivalent:
//   INSERT INTO User (username, email, password, full_name, phone, plan_id)
//   VALUES ('alice_k', 'alice@example.com', MD5('alice123'), 'Alice Kumar', '9876543210', 1);

db.users.insertOne({
  username: "alice_k",
  email: "alice@example.com",
  password_hash: "$2b$10$alicehashedpassword",
  full_name: "Alice Kumar",
  phone: "9876543210",
  account_age: 0,
  storage_used_mb: 0,
  created_at: new Date(),
  plan: {
    plan_id: 1,
    plan_name: "Free",
    storage_limit_gb: 5,
    price: 0.00,
    duration_days: 365,
    expiry_date: new Date("2027-04-20")
  }
});
print("Q1 ✅ User 'alice_k' inserted\n");


print("── Q2: INSERT a new file with tags and first version ──");
// SQL Equivalent:
//   INSERT INTO File (file_name, file_type, file_size_mb, user_id, folder_id, tags)
//   VALUES ('cloud_notes.docx', 'docx', 3.50, 2, 1, 'cloud,notes,2026');
//   INSERT INTO FileVersion (file_id, version_no, version_size_kb)
//   VALUES (LAST_INSERT_ID(), 1, 3584.0);

const johnUser = db.users.findOne({ username: "john_doe" });
const projectFolder = db.folders.findOne({ folder_name: "Projects" });

if (johnUser && projectFolder) {
  db.files.insertOne({
    file_name: "cloud_notes.docx",
    file_type: "docx",
    file_size_mb: 3.50,
    user_id: johnUser._id,
    folder_id: projectFolder._id,
    tags: ["cloud", "notes", "2026"],
    uploaded_at: new Date(),
    is_deleted: false,
    deleted_at: null,
    permanent_delete_at: null,
    versions: [
      { version_no: 1, version_size_kb: 3584.0, saved_at: new Date() }
    ],
    shared_access: []
  });
  print("Q2 ✅ File 'cloud_notes.docx' inserted with version 1\n");
} else {
  print("Q2 ⚠️  User or folder not found — run sample_data.js first\n");
}


// ══════════════════════════════════════════════
// SECTION B: READ OPERATIONS
// ══════════════════════════════════════════════

print("── Q3: FIND all active files by a user (not deleted) ──");
// SQL Equivalent:
//   SELECT file_name, file_type, file_size_mb, uploaded_at, tags
//   FROM File WHERE user_id = 2 AND is_deleted = 0
//   ORDER BY uploaded_at DESC;

if (johnUser) {
  const userFiles = db.files.find(
    { user_id: johnUser._id, is_deleted: false },
    { projection: { file_name: 1, file_type: 1, file_size_mb: 1, uploaded_at: 1, tags: 1 } }
  ).sort({ uploaded_at: -1 }).toArray();

  print("Q3 ✅ Files for john_doe:");
  userFiles.forEach(f => print("   →", f.file_name, "|", f.file_type, "|", f.file_size_mb + "MB | Tags:", f.tags?.join(", ")));
  print();
}


print("── Q4: FIND users on Pro or Ultra plan ──");
// SQL Equivalent:
//   SELECT u.username, u.email, sp.plan_name, sp.storage_limit_gb
//   FROM User u JOIN StoragePlan sp ON u.plan_id = sp.plan_id
//   WHERE sp.plan_name IN ('Pro', 'Ultra');

const premiumUsers = db.users.find(
  { "plan.plan_name": { $in: ["Pro", "Ultra"] } },
  { projection: { username: 1, email: 1, "plan.plan_name": 1, "plan.storage_limit_gb": 1 } }
).toArray();

print("Q4 ✅ Pro/Ultra users:");
premiumUsers.forEach(u => print("   →", u.username, "|", u.plan.plan_name, "|", u.plan.storage_limit_gb + "GB"));
print();


// ══════════════════════════════════════════════
// SECTION C: UPDATE OPERATIONS
// ══════════════════════════════════════════════

print("── Q5: UPDATE user plan (plan upgrade) ──");
// SQL Equivalent:
//   UPDATE User SET plan_id = 3 WHERE username = 'alice_k';

db.users.updateOne(
  { username: "alice_k" },
  {
    $set: {
      "plan.plan_id": 2,
      "plan.plan_name": "Basic",
      "plan.storage_limit_gb": 50,
      "plan.price": 199.00,
      "plan.duration_days": 30,
      "plan.expiry_date": new Date("2026-05-20")
    }
  }
);
print("Q5 ✅ alice_k upgraded to Basic plan\n");


print("── Q6: ADD a new version to a file (push to embedded array) ──");
// SQL Equivalent:
//   INSERT INTO FileVersion (file_id, version_no, version_size_kb, saved_at)
//   VALUES (1, 3, 17000.0, NOW());

const targetFile = db.files.findOne({ file_name: "project_report.pdf" });
if (targetFile) {
  db.files.updateOne(
    { _id: targetFile._id },
    {
      $push: {
        versions: {
          version_no: 3,
          version_size_kb: 17000.0,
          saved_at: new Date()
        }
      }
    }
  );
  print("Q6 ✅ Version 3 added to project_report.pdf\n");
}


// ══════════════════════════════════════════════
// SECTION D: DELETE / SOFT-DELETE OPERATIONS
// ══════════════════════════════════════════════

print("── Q7: SOFT DELETE a file (move to trash) ──");
// SQL Equivalent:
//   UPDATE File SET is_deleted = 1 WHERE file_id = X;
//   INSERT INTO Trash (file_id, user_id, permanent_delete_at)
//   VALUES (X, Y, DATE_ADD(NOW(), INTERVAL 30 DAY));

const fileToTrash = db.files.findOne({ file_name: "cloud_notes.docx" });
if (fileToTrash) {
  db.files.updateOne(
    { _id: fileToTrash._id },
    {
      $set: {
        is_deleted: true,
        deleted_at: new Date(),
        permanent_delete_at: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
      }
    }
  );
  print("Q7 ✅ cloud_notes.docx moved to trash (auto-delete in 30 days)\n");
}


// ══════════════════════════════════════════════
// SECTION E: AGGREGATION PIPELINE
// ══════════════════════════════════════════════

print("── Q8: AGGREGATION — Storage usage per user with quota percentage ──");
// SQL Equivalent:
//   SELECT u.username, COUNT(f.file_id) AS total_files,
//          COALESCE(SUM(f.file_size_mb), 0) AS total_size_mb,
//          ROUND((COALESCE(SUM(f.file_size_mb),0) / (sp.storage_limit_gb * 1024)) * 100, 2) AS usage_pct
//   FROM User u
//   LEFT JOIN File f ON f.user_id = u.user_id AND f.is_deleted = 0
//   JOIN StoragePlan sp ON u.plan_id = sp.plan_id
//   GROUP BY u.user_id ORDER BY total_size_mb DESC;

const storageStats = db.files.aggregate([
  { $match: { is_deleted: false } },
  {
    $group: {
      _id: "$user_id",
      total_files: { $sum: 1 },
      total_size_mb: { $sum: "$file_size_mb" },
      avg_file_size_mb: { $avg: "$file_size_mb" }
    }
  },
  { $sort: { total_size_mb: -1 } },
  {
    $lookup: {
      from: "users",
      localField: "_id",
      foreignField: "_id",
      as: "user_info"
    }
  },
  { $unwind: "$user_info" },
  {
    $project: {
      username: "$user_info.username",
      plan_name: "$user_info.plan.plan_name",
      storage_limit_gb: "$user_info.plan.storage_limit_gb",
      total_files: 1,
      total_size_mb: { $round: ["$total_size_mb", 2] },
      avg_file_size_mb: { $round: ["$avg_file_size_mb", 2] },
      usage_pct: {
        $round: [
          { $multiply: [
            { $divide: ["$total_size_mb", { $multiply: ["$user_info.plan.storage_limit_gb", 1024] }] },
            100
          ]},
          2
        ]
      }
    }
  }
]).toArray();

print("Q8 ✅ Storage usage per user:");
storageStats.forEach(s => {
  print(`   → ${s.username} | ${s.plan_name} | Files: ${s.total_files} | Used: ${s.total_size_mb}MB | ${s.usage_pct}% of quota`);
});
print();


print("── Q9: AGGREGATION — Top shared files (by share count) ──");
// SQL Equivalent:
//   SELECT f.file_name, f.file_type, COUNT(sa.share_id) AS share_count
//   FROM File f JOIN SharedAccess sa ON f.file_id = sa.file_id
//   WHERE f.is_deleted = 0
//   GROUP BY f.file_id ORDER BY share_count DESC LIMIT 10;

const topShared = db.files.aggregate([
  { $match: { is_deleted: false } },
  {
    $project: {
      file_name: 1,
      file_type: 1,
      share_count: { $size: "$shared_access" }
    }
  },
  { $match: { share_count: { $gt: 0 } } },
  { $sort: { share_count: -1 } },
  { $limit: 10 }
]).toArray();

print("Q9 ✅ Most shared files:");
topShared.forEach(f => print(`   → ${f.file_name} (${f.file_type}) — ${f.share_count} share(s)`));
print();


// ══════════════════════════════════════════════
// SECTION F: INDEXING
// ══════════════════════════════════════════════

print("── Q10: CREATE indexes for performance ──");
// SQL Equivalent:
//   CREATE INDEX idx_file_user ON File(user_id, is_deleted, uploaded_at DESC);
//   CREATE INDEX idx_file_tags ON File(tags);
//   CREATE UNIQUE INDEX idx_user_email ON User(email);
//   CREATE INDEX idx_log_user ON ActivityLog(user_id, action_date DESC);

// Index 1: User files lookup (most common query)
db.files.createIndex(
  { user_id: 1, is_deleted: 1, uploaded_at: -1 },
  { name: "idx_file_user_active" }
);

// Index 2: Tag-based search
db.files.createIndex(
  { tags: 1 },
  { name: "idx_file_tags" }
);

// Index 3: Unique email for login
db.users.createIndex(
  { email: 1 },
  { unique: true, name: "idx_user_email_unique" }
);

// Index 4: Unique username
db.users.createIndex(
  { username: 1 },
  { unique: true, name: "idx_user_username_unique" }
);

// Index 5: Activity log by user + date (for dashboard feed)
db.activity_logs.createIndex(
  { user_id: 1, action_date: -1 },
  { name: "idx_log_user_date" }
);

// Index 6: TTL index — auto-delete trashed files after 30 days
db.files.createIndex(
  { permanent_delete_at: 1 },
  { expireAfterSeconds: 0, name: "idx_ttl_trash_auto_delete" }
);

// Index 7: Plan name index for filtering
db.users.createIndex(
  { "plan.plan_name": 1 },
  { name: "idx_user_plan_name" }
);

print("Q10 ✅ 7 indexes created (including TTL auto-delete index)\n");

// Verify indexes
print("   Indexes on 'files' collection:");
db.files.getIndexes().forEach(i => print("   →", i.name));
print();


// ══════════════════════════════════════════════
// SECTION G: SEARCH FEATURES
// ══════════════════════════════════════════════

print("── Q11: FULL-TEXT SEARCH on file name and tags ──");
// SQL Equivalent:
//   SELECT file_name, tags, file_size_mb
//   FROM File
//   WHERE MATCH(file_name, tags) AGAINST ('machine learning academic' IN NATURAL LANGUAGE MODE)
//   ORDER BY relevance DESC;

// Create text index (required for $text search)
try {
  db.files.createIndex(
    { file_name: "text", tags: "text" },
    { name: "idx_text_file_name_tags" }
  );
} catch(e) {
  // Index may already exist
}

const textResults = db.files.find(
  { $text: { $search: "machine learning academic" } },
  { projection: { score: { $meta: "textScore" }, file_name: 1, tags: 1, file_size_mb: 1 } }
).sort({ score: { $meta: "textScore" } }).toArray();

print("Q11 ✅ Full-text search results for 'machine learning academic':");
textResults.forEach(f => print(`   → ${f.file_name} | Tags: ${f.tags?.join(", ")} | Score: ${f.score?.toFixed(2)}`));
print();


print("── Q12: REGEX SEARCH — find files by partial name (case-insensitive) ──");
// SQL Equivalent:
//   SELECT * FROM File
//   WHERE file_name LIKE 'machine%' AND is_deleted = 0 AND user_id = 2;

if (johnUser) {
  const regexResults = db.files.find(
    {
      user_id: johnUser._id,
      file_name: { $regex: /^machine/i },
      is_deleted: false
    },
    { projection: { file_name: 1, file_type: 1, file_size_mb: 1, tags: 1 } }
  ).toArray();

  print("Q12 ✅ Regex search results (starts with 'machine'):");
  regexResults.forEach(f => print(`   → ${f.file_name} | ${f.file_type} | ${f.file_size_mb}MB`));
  print();
}

print("══════════════════════════════════════════════");
print("  All 12 MongoDB Queries Executed Successfully");
print("══════════════════════════════════════════════");
