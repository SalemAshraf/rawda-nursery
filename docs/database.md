# Database design

Single database, shared tables, `tenant_id` on every tenant-owned table.
Eloquent global scope filters every query by the authenticated user's tenant;
the tenant id is stamped on create and never taken from request input.
Production and tests run on MySQL 8 (utf8mb4).

## Tables

### tenants
id, name, slug (unique), timezone (default Africa/Cairo), currency (3-letter,
default EGP), timestamps.

### users
Breeze users plus tenant_id FK, role (owner|manager|teacher). Email unique
globally.

### classrooms
id, tenant_id, name, capacity, timestamps. UNIQUE(tenant_id, name).

### children
id, tenant_id, classroom_id (nullable FK), first_name, last_name, dob, gender,
allergies (text, nullable; visible to all staff), health_notes (text,
nullable; owner/manager only), status (active|archived), enrolled_at,
timestamps.

### guardians
id, tenant_id, name, phone, email, relation, timestamps.

### child_guardian
child_id, guardian_id, is_primary. Composite PK. A guardian may be linked to
siblings.

### pickup_persons
id, tenant_id, child_id FK, name, relation, phone, active (bool), timestamps.
The ONLY source of checkout authorization. Deactivated, never deleted, to
preserve attendance history. Listing a person here is a recorded pickup
authorization, not identity verification and not a custody claim.

### attendance
id, tenant_id, child_id FK, date, check_in_at, checked_in_by (users FK),
check_out_at, checked_out_by, pickup_person_id (FK, set at checkout), note,
timestamps. UNIQUE(child_id, date). Checkout requires the pickup person to be
active and to belong to the same child.

### daily_logs
id, tenant_id, child_id FK, date, meals, naps, mood, activities, notes,
created_by, timestamps. UNIQUE(child_id, date).

### incidents
id, tenant_id, child_id FK, occurred_at, severity (minor|moderate|severe),
description, reported_by (users FK), guardian_acknowledged_at (nullable),
guardian_acknowledged_by (nullable, free text), timestamps. v1 has no guardian
login: acknowledgement is recorded by staff for an offline acknowledgement.

### tuition_plans
id, tenant_id, name, amount_minor (unsigned int, minor currency units),
period (monthly|term|yearly), active, timestamps. UNIQUE(tenant_id, name).

### invoices
id, tenant_id, child_id FK, description, issue_date, due_date, amount_minor,
status (draft|sent|paid|void), created_by, timestamps. Status transitions:
draft → sent → paid (auto when payments cover the amount); draft/sent → void
only while no payments exist.

### payments
id, tenant_id, invoice_id FK, paid_at, amount_minor, method, reference,
recorded_by, timestamps. Manually recorded (cash, transfer, wallet); no
online collection in v1.

### audit_logs
id, tenant_id, user_id (nullable), action, entity, entity_id, details,
created_at. Append-only.

## Money and time

- Money is stored in minor units (piasters, halalas, fils) as integers; the
  tenant currency drives display.
- "Today" is the tenant's calendar day in its own timezone, so a nursery in
  Cairo and one in Riyadh each get their own attendance date.
