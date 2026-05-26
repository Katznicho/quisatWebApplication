# Kids Clinics — end-to-end test checklist

Run these steps in order to verify the full flow (school → parent app → clinic web).

## One-time setup (Laravel)

```bash
cd /path/to/quisat
php artisan migrate
php artisan db:seed --class=KidsClinicsFeatureSeeder
php artisan clinic:backfill-student-access-codes
```

Enable **Kids Clinics** on:

1. A **school** business (children + parents live here).
2. A **clinic** business (pediatric clinic category with Kids Clinics feature).

In admin: Business → edit → ensure **Kids Clinics** is in enabled features (or pick a category that includes it).

## Flow A — Parent links child (mobile app)

1. Log in as a **parent** linked to at least one student at the school.
2. On **Parent dashboard**, note the **Clinic code** (`CHD-…`) under the selected child.
3. Open **Kids Clinics** from the module grid.
4. Under **My linked clinics** (empty first time), browse and open a clinic.
5. Tap the child row to fill the code, or type it manually → **Link child to this clinic**.
6. Confirm **Your children at this clinic** shows the child and patient number.
7. Return to **Kids Clinics** — the clinic appears under **My linked clinics**.

## Flow B — Clinic staff imports same child (web)

1. Log in as clinic staff (business with Kids Clinics enabled).
2. Sidebar → **Kids Clinics** → **Import by access code**.
3. Enter the same `CHD-…` code → preview student from school → **Import child**.
4. Patient list shows the child; **school access code** column matches.
5. Open patient → **Schedule appointment** (date/time, doctor, type) → Save.
6. Parent app: pull to refresh clinic detail → **Upcoming appointments** appears.

## Flow C — Duplicate protection

1. Parent links again with same code → message: already linked (no duplicate patient).
2. Staff imports same code again → redirected to existing patient (no second record).

## API smoke (optional)

With parent Bearer token:

- `GET /api/v1/parent/clinics/my-links`
- `GET /api/v1/parent/clinics/{clinicId}/overview`
- `POST /api/v1/parent/clinics/{clinicId}/attach` body: `{ "child_access_code": "CHD-XXXXXXXX" }`

Public (no auth):

- `GET /api/v1/clinics`
- `GET /api/v1/clinics/{id}`

## Troubleshooting

| Symptom | Fix |
|--------|-----|
| Kids Clinics missing in web sidebar | Enable feature on business; check `enabled_feature_ids` |
| No `CHD-` code on dashboard | Run `php artisan clinic:backfill-student-access-codes` |
| Linked children empty in app | Use parent login; overview requires auth |
| No clinics in app list | At least one active business (not id 1) with Kids Clinics feature |
