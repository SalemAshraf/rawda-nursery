# Product plan

Name: Baraim (براعم), approved. Target buyers: private nurseries/preschools in Egypt
first, Gulf (Saudi, UAE) as a second phase. Evidence base: docs/market-study.md.

## Positioning

One system that runs the nursery day: register, gate, day log, incidents,
fees. Not a photo-sharing app, not a school ERP. The first version serves a
single-branch nursery with owner, a manager and teachers.

## Phases

### Phase 1 (this repo, v1)

- Tenancy, roles (owner/manager/teacher), staff accounts.
- Children, guardians, classrooms, allergies, restricted health notes.
- Authorized pickup list per child + enforced checkout.
- Attendance on the tenant's calendar day.
- Daily logs, incidents with severity and recorded guardian acknowledgement.
- Tuition plans, invoices, manual payments, overdue tracking.
- Audit log.

### Phase 2 (validated by first buyers)

- Parent-facing portal/app: child's day, invoices, incident acknowledgement
  with guardian login (replaces staff-recorded acknowledgement).
- Paymob/Fawry online collection for Egypt.
- Announcements and parent messaging.
- CSV import of children from Excel (migration is a selling point: Kindaria
  advertises free migration; expect buyers with spreadsheets).

### Phase 3 (Gulf readiness)

- Saudi: ZATCA-compliant e-invoicing (needs proper regulatory verification
  before any compliance claim), SAR, Riyadh timezone already supported.
- UAE: VAT invoice fields.
- Arabic UI end-to-end (v1 is English UI; competitors ship Arabic parent apps).

### Not planned without evidence

Bus live tracking, development milestones engine, QR kiosk hardware, AI
assistant, website builder. Each exists at a competitor; build only after a
buyer asks.

## Safety and privacy principles (non-negotiable, any phase)

- Pickup authorization is per child, explicit, revocable, and never implied by
  a guardian relationship. The system records the authorized person presented
  at checkout; staff remain responsible for verifying identity physically.
  The system does not record or adjudicate custody or legal authority.
- Child health data: allergies visible to staff (safety), other health notes
  restricted to owner/manager, every change audited.
- No child photos or guardian contact data leave the tenant without an
  explicit feature and consent design; no third-party messaging is sent
  automatically in v1.
- Tenant data isolation is enforced server-side and tested.

## Open decisions for the owner

1. ~~Final product name~~ RESOLVED 22 Nov 2026: Baraim (براعم). Rawda collided with rawdahapp.com (Kuwait). Domain check pending (separate workstream).
2. Arabic-first vs English-first UI. Competitors' parent apps are Arabic.
3. Pricing: per-child-per-month (Kindaria's model) vs flat per-nursery. Needs
   buyer conversations, not assumption.
4. First-buyer segment: which neighborhood/type of nursery for the first 5.
