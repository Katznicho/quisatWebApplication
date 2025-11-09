# Laravel Dashboard UI Build Plan

This document captures the follow-up work required to surface the new API data inside the Laravel (web) admin. Each section maps to already-available endpoints so we can prioritise UI screens later.

## 1. Staff Home Dashboard
- Overview page that mirrors `/staff/dashboard`
- Cards for quick stats (assignments due, announcements, student/parent counts)
- Table or timeline for "Today’s Schedule"
- Panels for upcoming events, recent announcements, recent assignments
- Optional export/print buttons for schedule & assignments

## 2. Parent/Attendance Insights
- Admin view that lists parent accounts with linked children (data from `/parent/dashboard`)
- Ability to open a parent profile showing children, upcoming assignments/events, announcements
- Attendance log viewer backed by `/attendance/history` with filters (date range, status, class)
- Action buttons to manually trigger check-in/out or edit remarks (calls `/attendance/check-in`/`check-out`)

## 3. Document Management
- Listing page for assignment attachments (payload from `/documents`)
- Upload flow to attach new files to a class assignment (update `attachments` JSON)
- Link management (preview, replace, delete)
- Optional: bulk download/export of assignment documents

## 4. Student Progress Reports
- Detail page for a student that consumes `/students/{id}/progress`
- Charts/tables showing monthly/quarterly/annual averages with attendance percentage
- Buttons to download/print PDF summaries for parents
- Navigation entry from existing student directory for ease of access

## 5. Admin Notifications & Audit Trail
- Optional enhancement: log check-in/out actions and document updates for auditing
- Surface latest announcements/assignments created in the system with quick links to edit screens

---

When we’re ready to implement, we can break these into separate tickets (UI design, backend glue, testing) and stage deployments accordingly.
