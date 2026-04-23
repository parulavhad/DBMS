/**
 * CloudVault — MongoDB Collection Schema & Validators
 * FA-2: NoSQL Implementation
 * 
 * Run this file in MongoDB Shell:
 *   mongosh cloud_vault_nosql < collections_schema.js
 */

use('cloud_vault_nosql');

// ─────────────────────────────────────────────
// 1. users collection
// ─────────────────────────────────────────────
db.createCollection("users", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["username", "email", "password_hash", "plan", "created_at"],
      properties: {
        username:       { bsonType: "string", description: "Unique username" },
        email:          { bsonType: "string", description: "Unique email address" },
        password_hash:  { bsonType: "string", description: "bcrypt hashed password" },
        full_name:      { bsonType: "string" },
        phone:          { bsonType: "string" },
        account_age:    { bsonType: "int", minimum: 0 },
        storage_used_mb:{ bsonType: "double", minimum: 0 },
        created_at:     { bsonType: "date" },
        plan: {
          bsonType: "object",
          required: ["plan_name", "storage_limit_gb", "price"],
          properties: {
            plan_id:          { bsonType: "int" },
            plan_name:        { bsonType: "string", enum: ["Free", "Basic", "Pro", "Ultra"] },
            storage_limit_gb: { bsonType: "double" },
            price:            { bsonType: "double" },
            duration_days:    { bsonType: "int" },
            expiry_date:      { bsonType: "date" }
          }
        }
      }
    }
  }
});

// ─────────────────────────────────────────────
// 2. folders collection
// ─────────────────────────────────────────────
db.createCollection("folders", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["folder_name", "user_id", "created_at"],
      properties: {
        folder_name:      { bsonType: "string" },
        folder_path:      { bsonType: "string" },
        parent_folder_id: { bsonType: ["objectId", "null"] },
        user_id:          { bsonType: "objectId" },
        file_count:       { bsonType: "int", minimum: 0 },
        created_at:       { bsonType: "date" }
      }
    }
  }
});

// ─────────────────────────────────────────────
// 3. files collection (embeds versions + shared_access)
// ─────────────────────────────────────────────
db.createCollection("files", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["file_name", "file_size_mb", "user_id", "uploaded_at", "is_deleted"],
      properties: {
        file_name:    { bsonType: "string" },
        file_type:    { bsonType: "string" },
        file_size_mb: { bsonType: "double", minimum: 0 },
        user_id:      { bsonType: "objectId" },
        folder_id:    { bsonType: ["objectId", "null"] },
        tags:         { bsonType: "array", items: { bsonType: "string" } },
        uploaded_at:  { bsonType: "date" },
        is_deleted:   { bsonType: "bool" },
        deleted_at:           { bsonType: ["date", "null"] },
        permanent_delete_at:  { bsonType: ["date", "null"] },
        versions: {
          bsonType: "array",
          items: {
            bsonType: "object",
            required: ["version_no", "saved_at"],
            properties: {
              version_no:       { bsonType: "int" },
              version_size_kb:  { bsonType: "double" },
              saved_at:         { bsonType: "date" }
            }
          }
        },
        shared_access: {
          bsonType: "array",
          items: {
            bsonType: "object",
            properties: {
              shared_with:  { bsonType: ["objectId", "null"] },
              permission:   { bsonType: "string", enum: ["View", "Edit", "Download"] },
              shared_date:  { bsonType: "date" },
              is_expired:   { bsonType: "bool" }
            }
          }
        }
      }
    }
  }
});

// ─────────────────────────────────────────────
// 4. payments collection
// ─────────────────────────────────────────────
db.createCollection("payments", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["user_id", "amount", "payment_date", "payment_mode", "status"],
      properties: {
        user_id:      { bsonType: "objectId" },
        amount:       { bsonType: "double", minimum: 0 },
        payment_date: { bsonType: "date" },
        payment_mode: { bsonType: "string", enum: ["UPI", "Card", "NetBanking", "None"] },
        status:       { bsonType: "string", enum: ["Pending", "Completed", "Failed", "Refunded"] },
        plan_snapshot: {
          bsonType: "object",
          properties: {
            plan_name:        { bsonType: "string" },
            storage_limit_gb: { bsonType: "double" },
            price:            { bsonType: "double" }
          }
        }
      }
    }
  }
});

// ─────────────────────────────────────────────
// 5. activity_logs collection
// ─────────────────────────────────────────────
db.createCollection("activity_logs", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["user_id", "action_type", "action_date"],
      properties: {
        user_id:     { bsonType: "objectId" },
        file_id:     { bsonType: ["objectId", "null"] },
        action_type: {
          bsonType: "string",
          enum: ["Upload","Download","Delete","Restore","Share","Login","Logout","CreateFolder","RenameFile"]
        },
        action_date: { bsonType: "date" },
        ip_address:  { bsonType: "string" }
      }
    }
  }
});

print("✅ All 5 CloudVault collections created with validators.");
