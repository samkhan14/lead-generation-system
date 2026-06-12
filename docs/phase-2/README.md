# Phase 2 — Auth + Admin Foundation

## Status

**Implemented.**

## Goal

Make the system usable internally with authentication, role-based access, and a consistent admin shell.

## Already in place from Phase 1

- Laravel Breeze (Inertia + Vue 3)
- Spatie Laravel Permission
- Login / logout / password reset
- Seed users with roles

## Phase 2 Deliverables

### Admin layout

- `resources/js/Layouts/AdminLayout.vue` — sidebar + top bar + responsive mobile menu
- `resources/js/Components/Admin/SidebarLink.vue` — sidebar navigation item
- `resources/js/composables/useAuth.js` — frontend permission checks (respects super admin bypass)

### Dashboard

- `DashboardController` — lead stats and recent leads
- `resources/js/Pages/Dashboard.vue` — stat cards + recent leads list

### Internal-only access

- Public registration disabled (routes removed)
- `/` redirects to login (guest) or dashboard (authenticated)

### Shared auth context

`HandleInertiaRequests` shares:

- `auth.user.id`, `name`, `email`
- `roles`, `permissions`
- `is_super_admin`

Sidebar items respect permissions (e.g. Leads link requires `leads.view`).

## Navigation

| Item | Route | Access |
|------|-------|--------|
| Dashboard | `/dashboard` | Authenticated + verified |
| Leads | `/leads` | `leads.view` or super admin |
| Profile | `/profile` | Authenticated |

## Seed logins

| Email | Password | Role |
|-------|----------|------|
| `superadmin@example.com` | `password` | super_admin |
| `agent@example.com` | `password` | agent |

## Exit criteria

- [x] Breeze auth working (login/logout)
- [x] Spatie roles/permissions integrated
- [x] Admin layout with sidebar
- [x] Basic dashboard with lead stats
- [x] All authenticated admin pages use `AdminLayout`
- [x] Public registration disabled

## Next phase preview

- User management (admin CRUD for users/roles)
- Scraping ingestion pipeline
- Lead score calculation job
