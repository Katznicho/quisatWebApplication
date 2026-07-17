# Guest Parent Account + Universal Registration Link/Code

**Status:** Spec (not implemented)  
**Product name:** “On the Guest”  
**Related code today:** `ParentGuardian`, `AuthController::parentLogin`, school parent CRUD, RN guest browse stack  
**Related child codes (unchanged):** student `CHD-…`, clinic family `KCL-…`

---

## 1. Problem

Parents who are not linked to any business in Quisat currently cannot create a parent account. That leads to:

- Inability to register for school/clinic services as themselves
- Duplicate parent profiles when they later join a new service (staff create another row, or they use a different email)
- Slower onboarding for both services and parents

Today’s “Browse as guest” in the mobile app is **anonymous browse only** — it does not create a `ParentGuardian` row, does not issue a durable identity, and does not support joining businesses.

---

## 2. Goals

1. **Guest account creation** — Parents can open a standalone Quisat parent account without belonging to any business yet.
2. **Universal link + code** — System generates one unique reusable code and deep link per parent account.
3. **Re-use across businesses** — The same code/link can be used to join any Quisat business (school, clinic, etc.) without creating a new account.
4. **No duplicates** — If a parent already exists by email or phone, the system merges / reuses that row instead of creating another.
5. **Upgrade path** — Guest account becomes **Linked** once the parent joins their first business.

---

## 3. Non-goals (Phase 1)

- Replacing student access codes (`CHD-`) or clinic family codes (`KCL-`). Those remain for **child ↔ clinic/school** linking.
- Auto-converting historical anonymous kids-event / Parent Corner / order registrations into `ParentGuardian` accounts.
- Full multi-business “active context” UX for staff web dashboards (staff still operate in their logged-in business).
- Cross-business student records merging (students stay owned by their school `business_id`).

---

## 4. Current state

| Area | Behavior today |
|------|----------------|
| `parent_guardians` | `business_id` required FK; `email` unique globally |
| Parent signup | Staff-created only (web CRUD / bulk CSV) |
| Parent login | `POST /api/v1/auth/parent-login` — fails if no business association |
| Guest mode (app) | Anonymous stack: browse events/clinics/mart; register with PII but `user_id` null |
| Universal parent code | Does not exist |

Key files:

- `app/Models/ParentGuardian.php`
- `app/Http/Controllers/API/AuthController.php` (`parentLogin`)
- `app/Http/Controllers/SchoolManagement/ParentGuardianController.php`
- `src/navigation/AppNavigator.tsx` (RN guest stack)
- `docs/KIDS_CLINICS_E2E_TEST.md` (child/clinic codes only)

---

## 5. Definitions

| Term | Meaning |
|------|---------|
| **Guest parent** | `ParentGuardian` with `account_type = guest`, zero memberships (or none active), `business_id` null |
| **Linked parent** | `ParentGuardian` with `account_type = linked` and ≥1 active business membership |
| **Universal code** | Stable parent-scoped code, e.g. `QSP-A7K2M9XQ`, reusable across businesses |
| **Universal link** | URL that embeds the code, e.g. `https://app.quisat.com/join/QSP-A7K2M9XQ` (+ app deep link) |
| **Membership** | Row in `parent_guardian_business` tying a parent to a business |

---

## 6. Data model

### 6.1 Changes to `parent_guardians`

| Column | Type | Notes |
|--------|------|--------|
| `business_id` | nullable FK | Legacy “primary” / first school; **no longer required** for create/login |
| `account_type` | enum `guest` \| `linked` | Default `guest` for self-signup; `linked` after first join (and for backfilled school parents) |
| `universal_code` | string, unique, indexed | Generated on create; format `QSP-` + 8 alphanumeric (uppercase, no ambiguous chars) |
| `phone` | keep unique soft rule | See merge rules — normalize before match |
| `password` | required for guest self-signup | Existing school parents may still set via forgot-password |

`status` (`active` / `inactive`) remains account-level enablement.

### 6.2 New table: `parent_guardian_business`

| Column | Type | Notes |
|--------|------|--------|
| `id` | bigint PK | |
| `parent_guardian_id` | FK | |
| `business_id` | FK | |
| `relationship` | string/enum nullable | Optional override at membership level |
| `joined_via` | enum | `universal_code` \| `staff_invite` \| `staff_create` \| `clinic_attach` \| `self_join` |
| `status` | enum | `active` \| `inactive` |
| `joined_at` | timestamp | |
| `timestamps` | | |

**Unique:** `(parent_guardian_id, business_id)`.

### 6.3 Relationships

```
ParentGuardian 1──* ParentGuardianBusiness *──1 Business
ParentGuardian.business()          // optional legacy primary
ParentGuardian.businesses()        // belongsToMany via pivot
ParentGuardian.memberships()       // hasMany ParentGuardianBusiness
```

### 6.4 Code + link format

- **Code:** `QSP-XXXXXXXX` (prefix `QSP` = Quisat Parent)
- **Web link:** `https://app.quisat.com/join/{code}`
- **App deep link:** `quisat://join/{code}` (or existing Expo scheme if already defined)
- **Regeneration:** Allowed from profile; old code invalidated immediately; memberships unchanged
- **Expiry:** None in Phase 1 (code is identity-scoped, not invite-scoped)

Child codes remain separate:

- `CHD-…` — student access for clinic attach
- `KCL-…` — clinic family code

---

## 7. Core logic rules

1. **One parent row per person** — never create a second `ParentGuardian` when email or normalized phone already matches an existing row (including soft-deleted: prefer restore + merge over new insert when safe).
2. **Code is reusable** — same `universal_code` can create many memberships across businesses; joining an already-linked business is **idempotent** (return success + existing membership).
3. **Guest → Linked** — on first successful membership insert, set `account_type = linked`. Optionally set `business_id` to that first business for backward compatibility with code that still reads `parent.business_id`.
4. **Login without business** — guest parents can log in; API returns `account_type`, `universal_code`, `businesses: []`, and must **not** return 403 for missing business.
5. **Staff create with existing email/phone** — attach membership instead of failing unique email / creating duplicate.

---

## 8. Flows

### 8.1 Guest signup

```
App → POST /api/v1/auth/parent-register
  { first_name, last_name, email, phone, password, relationship? }
```

**Happy path**

1. Validate fields; normalize email (lowercase) and phone (E.164 or local digits rule — document one canonicalizer).
2. If email **or** phone matches existing parent → **do not create**; return `409` with `code: ACCOUNT_EXISTS` and guidance to log in / reset password / claim.
3. Else create `ParentGuardian` with `account_type=guest`, `business_id=null`, generated `universal_code`, hashed password, `status=active`.
4. Issue Sanctum token.
5. Return parent profile + `universal_code` + `universal_link`.

### 8.2 Login (updated)

```
App → POST /api/v1/auth/parent-login
```

- Remove hard requirement that `business` must exist.
- Response includes:
  - `account_type`
  - `universal_code`, `universal_link`
  - `businesses[]` (from pivot; empty for pure guest)
  - `business` / `business_id` — first active membership or legacy `business_id` (nullable)

### 8.3 Parent views / shares code

```
GET /api/v1/parent/universal-code
```

Returns `{ code, link, account_type, businesses_count }`. Profile screen: copy code, share link.

### 8.4 Join a business (parent-initiated)

```
POST /api/v1/parent/join-business
  { business_id }  // or business uuid/slug
  // optional: invite_token if business requires staff approval later
```

**Phase 1 join policy (locked for this spec):**

| Business type | Join behavior |
|---------------|---------------|
| Clinic | Auto-create active membership when parent requests join (or when staff accepts universal code — both supported) |
| School | Staff-mediated: parent presents `QSP-` code; staff accepts via web/API; **or** parent join creates `pending` membership if we add pending later |

**Phase 1 simplification:** Support **staff accept by universal code** for all business types, plus **parent self-join** for clinics. Schools use staff accept so student records stay controlled.

**Staff accept**

```
POST /api/v1/businesses/{business}/parents/accept-universal-code
  { universal_code, relationship? }
```

Auth: business staff. Creates/activates membership; upgrades guest → linked; sets legacy `business_id` if null.

### 8.5 Merge / claim existing account

| Situation | Behavior |
|-----------|----------|
| Signup email exists | `409 ACCOUNT_EXISTS` — login or forgot-password |
| Signup phone exists (different email) | `409 PHONE_IN_USE` — support/contact or login with that account |
| Staff creates parent with existing email | Attach membership to existing parent; do not overwrite password unless empty / force-reset flag |
| Soft-deleted parent same email | Restore if policy allows; else admin tool |

**No silent password overwrite** when merging staff create onto an existing guest with a password.

### 8.6 Upgrade guest → linked

Triggered when first `parent_guardian_business` row with `status=active` is created:

1. `account_type = linked`
2. If `business_id` is null → set to that `business_id`
3. Emit optional analytics event `parent.linked`

Leaving all businesses inactive may leave `account_type=linked` (historical) or revert to `guest` — **Phase 1 keeps `linked` once set**.

### 8.7 Re-use across businesses

Parent already linked to School A presents same `QSP-` to Clinic B:

1. Lookup by code → existing parent
2. Insert membership for Clinic B
3. No new parent row
4. Login response `businesses` now includes A and B

---

## 9. API contracts (draft)

Base prefix: `/api/v1`

### Auth

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| POST | `/auth/parent-register` | Public | Guest signup |
| POST | `/auth/parent-login` | Public | Login (guest or linked) |
| POST | `/auth/parent-forgot-password` | Public | Existing |
| POST | `/auth/parent-reset-password` | Public | Existing |

### Parent (Sanctum parent token)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/parent/me` | Profile + account_type + businesses + code |
| GET | `/parent/universal-code` | Code + link |
| POST | `/parent/universal-code/regenerate` | Rotate code |
| POST | `/parent/join-business` | Self-join (clinics Phase 1) |
| GET | `/parent/businesses` | List memberships |

### Staff (business user)

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/businesses/{id}/parents/accept-universal-code` | Link parent by `QSP-` |
| GET | `/businesses/{id}/parents` | Include membership source |

### Error codes (stable)

| HTTP | `code` | When |
|------|--------|------|
| 409 | `ACCOUNT_EXISTS` | Email already registered |
| 409 | `PHONE_IN_USE` | Phone already on another parent |
| 404 | `CODE_NOT_FOUND` | Invalid universal code |
| 409 | `ALREADY_LINKED` | Idempotent success preferred; or soft conflict with membership payload |
| 403 | `BUSINESS_JOIN_FORBIDDEN` | Self-join not allowed for this business type |

---

## 10. App / Web UX

### Mobile (React Native)

1. Guest home: CTA **Create Quisat parent account** (in addition to Browse).
2. Signup screen → on success, show **Your Quisat code** with copy/share; explain it works for any school/clinic on Quisat.
3. Login: allow guests with empty businesses; home shows **Not linked yet** + how to share code with a school/clinic.
4. Profile: always show universal code + link; list linked businesses.
5. Deep link `join/{code}`: if logged out → signup/login then store pending join; if logged in as different user → show conflict; if staff flow is primary, deep link may open “show this code to staff” instructional screen.

### Web (school / clinic staff)

1. Parents list: action **Link by Quisat code** → enter `QSP-…` → preview name/email/phone → Confirm.
2. Create parent form: on email/phone conflict, offer **Link existing Quisat parent** instead of validation error only.
3. Show badge: Guest vs Linked; show `joined_via`.

---

## 11. Security

- Universal code is **bearer of identity linkage** — treat like a semi-public identifier (shareable), not a password. Joining still requires staff accept for schools; clinic self-join should rate-limit and optionally require business to be discoverable/public.
- Rate-limit: register, login, code accept, regenerate.
- Do not expose other parents’ PII on public code lookup; staff accept returns preview only to authenticated staff of that business.
- Password rules same as existing parent reset (min length, etc.).
- Sanctum tokens: revoke on password reset; regenerate code does not revoke tokens.

---

## 12. Migration / backfill

1. Make `business_id` nullable.
2. Add `account_type`, `universal_code`.
3. Create `parent_guardian_business`.
4. Backfill:
   - For each existing parent with `business_id`: insert pivot row (`joined_via=staff_create`, `status=active`); set `account_type=linked`; generate `universal_code` if missing.
5. Update `parentLogin` and any `where('business_id', …)` assumptions in parent APIs to use memberships where needed.
6. Keep reading legacy `business_id` as fallback primary until all callers use `businesses[]`.

Artisan command suggestion: `php artisan parents:backfill-universal-codes`.

---

## 13. Edge cases

| Case | Expected |
|------|----------|
| Parent joins same business twice | Idempotent success |
| Soft-deleted parent, signup same email | Prefer restore + set password if none; else `ACCOUNT_EXISTS` with support path |
| Inactive parent login | 403 account inactive |
| Staff accepts code for parent already in business | Idempotent |
| Guest with no password (legacy) | Force forgot-password before login |
| Phone shared across family intentionally | Phase 1: one account per phone; document limitation |
| Code regenerated while staff is typing old code | Old code fails `CODE_NOT_FOUND` |

---

## 14. Test plan

### API

- [ ] Guest register creates parent, null `business_id`, `account_type=guest`, unique `QSP-` code
- [ ] Register duplicate email → 409 `ACCOUNT_EXISTS`
- [ ] Register duplicate phone → 409 `PHONE_IN_USE`
- [ ] Guest login succeeds without business; response `businesses: []`
- [ ] Staff accept code → membership + `account_type=linked` + legacy `business_id` set if null
- [ ] Same code accepted by second business → second membership, still one parent row
- [ ] Second accept same business → idempotent
- [ ] Staff create parent with existing guest email → membership attach, no duplicate row
- [ ] Regenerate code invalidates old code

### App

- [ ] Signup → shows code/link
- [ ] Guest home after login shows empty linked services
- [ ] Profile copy/share works
- [ ] After staff link, pull-to-refresh shows business

### Regression

- [ ] Existing school parent login still works
- [ ] Clinic attach via `CHD-` unchanged
- [ ] Parent forgot/reset password unchanged

---

## 15. Implementation notes for engineers

Suggested order:

1. Migrations + model relations + code generator service (`ParentUniversalCodeService`)
2. Backfill command
3. `parent-register` + login response changes
4. Staff accept endpoint + web UI
5. Parent self-join for clinics
6. RN signup / profile / guest CTA
7. Deep link handling

Touch points likely:

- `app/Models/ParentGuardian.php`
- `app/Http/Controllers/API/AuthController.php`
- `app/Http/Controllers/SchoolManagement/ParentGuardianController.php`
- New API controller e.g. `ParentUniversalAccountController`
- RN: `AuthContext`, new `ParentRegisterScreen`, `GuestHomeScreen`, profile

---

## 16. Open questions (non-blocking for Phase 1)

1. Should school self-join be allowed later with admin approval (`pending` membership)?
2. Should universal link open a public landing page for parents without the app installed?
3. Multi-business active context in the app: single “selected business” vs merged feeds?

Phase 1 does not require answers to ship the above design.
