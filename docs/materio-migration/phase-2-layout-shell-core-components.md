# Materio UI Migration — Phase 2: Layout Shell + Core Components

Status: Spec (no code changes). Target app: `d:\herd\testapp`.
Scope: Convert the Inertia/Vue layout shell and the shared low-level components from Tailwind (Breeze default) to the Materio (Bootstrap 5 + Remix Icons) design language, using the Materio HTML references `layout-menu-fixed`, `auth-login-basic`, `ui-modals`, `forms-basic-inputs`, `tables-basic`.

## 0. Assumptions & Prerequisites (must hold before Phase 2 starts)

Phase 2 assumes Phase 1 delivered the asset/build foundation. If any of the below is not yet true, it is a Phase 1 blocker, not Phase 2 work:

1. Materio SCSS/CSS core + theme is compiled and loaded (Bootstrap 5, `@core` styles, Materio theme variables).
2. Remix Icons font (`remixicon.css`) is loaded globally.
3. Materio template JS (`helpers.js`, `menu.js`, and their dependencies e.g. `perfect-scrollbar`, Popper/Bootstrap bundle) is available to import.
4. Global `<html>`/`<body>` classes and the `window.templateCustomizer`/`config` bootstrapping (if used) are wired in the Blade root (`app.blade.php`).

Current state confirmed from repo:
- Build stack: Vite + `@vitejs/plugin-vue`, Tailwind 3 (`tailwind.config.js`), `@tailwindcss/forms`. Entry: `resources/js/app.js` → `resources/css/app.css` (`@tailwind base/components/utilities`).
- No Materio/Bootstrap/Remix assets present yet.
- Auth/permissions via `useAuth()` composable (`user`, `can(permission)`), Ziggy `route()` global.
- `AuthenticatedLayout.vue` exists but is **not imported anywhere** (orphaned).

> Decision needed (carry from Phase 1): Tailwind and Bootstrap coexist during migration, or Tailwind is removed? This spec is written for the **target end-state** (Materio/Bootstrap classes). During transition, keep Tailwind loaded so un-migrated pages don't break; remove Tailwind only in the final phase. Utility-name collisions between Tailwind and Bootstrap (e.g. `.d-flex` vs `.flex`, `.rounded`, `.shadow`) are minimal because Materio uses Bootstrap's `.d-*`/`.text-*` naming; the main risk is Tailwind's `.container`, `.rounded`, `.shadow`, `.text-{color}` — audit these when both are loaded.

---

## 1. `AdminLayout.vue` — Target DOM Structure (Materio `layout-menu-fixed`)

Materio's fixed-menu layout wraps everything in `.layout-wrapper.layout-content-navbar` → `.layout-container` → (`#layout-menu` aside) + `.layout-page` (navbar + `.content-wrapper` + footer). Reproduce that structure in the Vue template, keeping the existing slots (`#header`, default) and behaviors.

### 1.1 Template skeleton (structure only)

```vue
<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue';   // extracted nav (see §3)
import AdminNavbar from '@/Components/Admin/AdminNavbar.vue';     // navbar (see §4)
import AdminFooter from '@/Components/Admin/AdminFooter.vue';     // footer (see §5)

const { user, can } = useAuth();
// menu instance handle (see §7)
</script>

<template>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

      <!-- ===== Sidebar / Menu ===== -->
      <AdminSidebar :can="can" />

      <!-- ===== Page ===== -->
      <div class="layout-page">

        <!-- Navbar: user dropdown, mobile toggle, #header slot -->
        <AdminNavbar :user="user">
          <template #page-header><slot name="header" /></template>
        </AdminNavbar>

        <!-- Content -->
        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            <slot />
          </div>

          <AdminFooter />

          <div class="content-backdrop fade"></div>
        </div>
      </div>
    </div>

    <!-- Menu overlay for mobile (Materio toggles .layout-menu-expanded on <html>) -->
    <div class="layout-overlay layout-menu-toggle"></div>
  </div>
</template>
```

Notes:
- The `.layout-menu-fixed` behavior is enabled by a class on `<html>` (`class="... layout-menu-fixed"`) set in Blade / Materio config, not on this wrapper. Phase 1 owns that; Phase 2 assumes it.
- The `#header` slot previously lived in the sticky `<header>`. In Materio there is no dedicated page-heading bar in the base template; map it to a page-title area rendered inside the navbar (passed through `#page-header`) **or** at the top of `.container-xxl`. Recommended: keep it inside the content container as a `<h4 class="fw-bold py-3 mb-4">` region so per-page headers keep working. Pick one and apply consistently. This spec routes it into the navbar slot to preserve the "sticky header" visual; if pages pass rich content (buttons, breadcrumbs) prefer the content-container option. **Decision: route `#header` into the content container top** (safer for arbitrary slot content) — adjust the skeleton to place `<slot name="header" />` immediately inside `.container-xxl`, wrapped in a `d-flex justify-content-between align-items-center mb-4`.
- Mobile sidebar state is no longer a Vue `ref` (`sidebarOpen`); Materio's `menu.js` + `.layout-menu-toggle` handles open/close by toggling `layout-menu-expanded` on `<html>`. See §7 & §8.

### 1.2 Sub-component extraction (engineering-principles: keep it thin, single owner)

Split the current monolithic `AdminLayout.vue` into three child components so the layout file coordinates only:
- `Components/Admin/AdminSidebar.vue` — the `#layout-menu` aside + all nav items (§3).
- `Components/Admin/AdminNavbar.vue` — top navbar (§4).
- `Components/Admin/AdminFooter.vue` — footer (§5).

Rationale: the current file is ~300 lines dominated by nav markup; extraction isolates the menu (which needs `menu.js` lifecycle wiring) and keeps `AdminLayout.vue` a thin shell. This is optional but recommended; if kept inline, the same class mappings apply.

---

## 2. `GuestLayout.vue` — Target Structure (Materio `auth-login-basic`)

Materio auth pages use a full-height flex container centered card: `.authentication-wrapper.authentication-basic.container-p-y` → `.authentication-inner` → `.card` → `.card-body`. The brand/logo sits inside the card above the slot.

```vue
<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <div class="card px-sm-6 px-0">
          <div class="card-body">

            <!-- Brand -->
            <div class="app-brand justify-content-center mb-6">
              <Link href="/" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <ApplicationLogo class="..." />
                </span>
                <span class="app-brand-text demo text-heading fw-bold">Lead CRM</span>
              </Link>
            </div>

            <!-- Page content (login/register/forgot forms) -->
            <slot />

          </div>
        </div>
      </div>
    </div>
  </div>
</template>
```

Notes:
- `authentication-bg` / illustrations from Materio are optional; `authentication-basic` (centered card, no side image) is the closest to the current single-card design and lowest risk.
- `ApplicationLogo.vue` is an inline SVG; keep it but drop Tailwind `fill-current text-gray-500` and size via Bootstrap/utility or inline style. It can stay as-is visually; only the wrapper classes change.
- The status/session-message block that login pages render inside the slot is unaffected (owned by the page, not the layout).

---

## 3. Sidebar Menu Mapping (Materio `menu-item` / `menu-link` + Remix icons)

Materio menu structure inside `#layout-menu`:

```
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo"> ... logo + brand + toggle ... </div>
  <div class="menu-inner-shadow"></div>
  <ul class="menu-inner py-1">
    <li class="menu-header small text-uppercase"><span class="menu-header-text">SECTION</span></li>
    <li class="menu-item {active}">
      <a href="..." class="menu-link">
        <i class="menu-icon tf-icons ri-xxx"></i>
        <div>Label</div>
      </a>
    </li>
  </ul>
</aside>
```

Each nav entry becomes a `<li class="menu-item">` wrapping the refactored `SidebarLink` (see §6.10). `menu-header` replaces the current `<p>` section labels. Section wrappers keep their `v-if` permission guards.

### 3.1 Item → Materio + Remix icon table

| Section (menu-header) | Current label | Permission guard (`can(...)`) | Route (`route(...)`, `.current(...)`) | Remix icon class |
|---|---|---|---|---|
| _(none)_ | Dashboard | _(always)_ | `dashboard` / `dashboard` | `ri-home-smile-line` |
| _(none)_ | Leads | `leads.view` | `leads.index` / `leads.*` | `ri-user-search-line` |
| _(none)_ | Scraper | `scraper.view` | `scraper.index` / `scraper.*` | `ri-search-eye-line` |
| _(none)_ | Services | `services.view` | `admin.services.index` / `admin.services.*` | `ri-briefcase-line` |
| **AI Platform** | Providers | `ai.providers.view` | `admin.ai.providers.index` / `admin.ai.providers.*` | `ri-plug-line` |
| AI Platform | Models | `ai.models.view` | `admin.ai.models.index` / `admin.ai.models.*` | `ri-cpu-line` |
| AI Platform | Employees | `ai.employees.view` | `admin.ai.employees.index` / `admin.ai.employees.*` | `ri-robot-2-line` |
| AI Platform | Prompts | `ai.prompts.view` | `admin.ai.prompts.index` / `admin.ai.prompts.*` | `ri-file-text-line` |
| AI Platform | Knowledge | `ai.knowledge.view` | `admin.ai.knowledge.index` / `admin.ai.knowledge.*` | `ri-book-2-line` |
| AI Platform | AI Logs | `ai.logs.view` | `admin.ai.logs.index` / `admin.ai.logs.*` | `ri-file-list-3-line` |
| **Voice** | Voice Providers | `voice.providers.view` | `admin.voice.providers.index` / `admin.voice.providers.*` | `ri-phone-line` |
| Voice | Voice Calls | `voice.calls.view` | `admin.voice.calls.index` / `admin.voice.calls.*` | `ri-speak-line` |
| **Email** | Email Providers | `email.providers.view` | `admin.email.providers.index` / `admin.email.providers.*` | `ri-mail-settings-line` |
| Email | Campaigns | `email.campaigns.view` | `admin.email.campaigns.index` / `admin.email.campaigns.*` | `ri-megaphone-line` |
| Email | Email Sends | `email.sends.view` | `admin.email.sends.index` / `admin.email.sends.*` | `ri-send-plane-line` |

Section-header `v-if` conditions are preserved exactly as today (OR of the section's item permissions):
- **AI Platform** header shows if any of `ai.providers.view | ai.models.view | ai.employees.view | ai.prompts.view | ai.knowledge.view | ai.logs.view`.
- **Voice** header shows if `voice.providers.view | voice.calls.view`.
- **Email** header shows if `email.providers.view | email.campaigns.view | email.sends.view`.

Section header markup:
```html
<li class="menu-header small text-uppercase"><span class="menu-header-text">AI Platform</span></li>
```

### 3.2 Brand block (top of sidebar)

Replaces the current `<div class="flex h-16 items-center ...">` logo header:
```html
<div class="app-brand demo">
  <Link :href="route('dashboard')" class="app-brand-link">
    <span class="app-brand-logo demo"><ApplicationLogo/></span>
    <span class="app-brand-text demo menu-text fw-bold">Lead CRM</span>
  </Link>
  <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
    <i class="ri-menu-4-line ... d-block d-xl-none"></i>
    <i class="ri-arrow-left-s-line ... d-none d-xl-block align-middle"></i>
  </a>
</div>
```

### 3.3 Sidebar user block

Current bottom-of-sidebar user card (name/email/roles) is **removed from the sidebar** — Materio surfaces the user via the navbar avatar dropdown (§4). Role badges are not part of the Materio navbar dropdown; if the roles display must be preserved, place it inside the navbar dropdown header (small muted text) rather than the sidebar. **Decision: move name/email into the navbar dropdown header; drop the visible role badges from the shell** (roles remain available via `user.roles` if a page wants them). Confirm with product if role badges must stay visible.

---

## 4. Navbar Content Spec (Materio `layout-menu-fixed` navbar)

```html
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
     id="layout-navbar">

  <!-- Mobile menu toggle (hidden on xl+) -->
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="ri-menu-line ri-22px"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

    <!-- Optional page header slot (breadcrumb/title) -->
    <div class="navbar-nav align-items-center">
      <slot name="page-header" />
    </div>

    <ul class="navbar-nav flex-row align-items-center ms-auto">
      <!-- User dropdown -->
      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
            <span class="avatar-initial rounded-circle bg-label-primary">{{ initials }}</span>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <div class="dropdown-item">
              <div class="d-flex align-items-center">
                <div class="avatar avatar-online"><span class="avatar-initial rounded-circle bg-label-primary">{{ initials }}</span></div>
                <div class="flex-grow-1 ms-2">
                  <span class="fw-medium d-block small">{{ user?.name }}</span>
                  <small class="text-muted">{{ user?.email }}</small>
                </div>
              </div>
            </div>
          </li>
          <li><div class="dropdown-divider my-1"></div></li>
          <li>
            <Link class="dropdown-item" :href="route('profile.edit')">
              <i class="ri-user-3-line ri-22px me-3"></i><span>Profile</span>
            </Link>
          </li>
          <li>
            <Link class="dropdown-item" :href="route('logout')" method="post" as="button">
              <i class="ri-logout-box-r-line ri-22px me-3"></i><span>Log Out</span>
            </Link>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
```

Behavior mapping:
- **Mobile toggle**: the current Vue `@click="sidebarOpen = !sidebarOpen"` hamburger is replaced by Materio's `.layout-menu-toggle` anchor. `menu.js` binds the click to toggle `layout-menu-expanded` on `<html>`. No Vue state.
- **User dropdown**: previously the custom `Dropdown.vue`. Two options:
  - (a) Use Bootstrap's native `dropdown` (`data-bs-toggle="dropdown"`, requires Bootstrap JS bundle) — recommended for visual/behavioral parity with Materio.
  - (b) Keep the Vue `Dropdown.vue` (refactored to Materio classes, §6.9) to avoid depending on Bootstrap JS for this one widget.
  - **Decision: (a) native Bootstrap dropdown** in the navbar (Materio parity, keyboard/aria handled by Bootstrap). Refactor `Dropdown.vue` (§6.9) remains for any non-navbar usages, but the navbar uses native markup. Note: Inertia `<Link>` inside a Bootstrap dropdown works; the logout `as="button"` Link renders a `<button>` styled as `dropdown-item`.
- `initials` = computed from `user.name` (first letters). If an avatar image field exists later, swap `avatar-initial` for `<img class="rounded-circle">`.

---

## 5. Footer with MIT / ThemeSelection Attribution

Materio (MIT free version) requires a visible attribution to ThemeSelection. Add `Components/Admin/AdminFooter.vue`:

```html
<footer class="content-footer footer bg-footer-theme">
  <div class="container-xxl">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="text-body">
        © {{ new Date().getFullYear() }} Lead CRM
      </div>
      <div>
        Template
        <a href="https://themeselection.com/" target="_blank" class="footer-link">Materio</a>
        by
        <a href="https://themeselection.com/" target="_blank" class="footer-link">ThemeSelection</a>
        (MIT License)
      </div>
    </div>
  </div>
</footer>
```

Attribution must remain in the DOM (license compliance). Do not gate it behind `v-if`.

---

## 6. Per-Component Class Mapping (Tailwind → Bootstrap/Materio)

### 6.1 `PrimaryButton.vue`
| Current (Tailwind) | Target (Materio/Bootstrap) |
|---|---|
| `inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white ... hover:bg-gray-700 focus:ring-2 ...` | `btn btn-primary` |
- Keep `<slot/>`. Add `:type` prop passthrough for consistency (currently missing; SecondaryButton has it). Icon usage: `<i class="ri-... me-1"></i>` before slot.
- Disabled state: `disabled` attribute (Bootstrap styles it) instead of `disabled:opacity-25`.

### 6.2 `SecondaryButton.vue`
| `... border-gray-300 bg-white text-gray-700 ... disabled:opacity-25` | `btn btn-outline-secondary` (or `btn btn-label-secondary`) |
- Keep the existing `type` prop.

### 6.3 `DangerButton.vue`
| `... bg-red-600 text-white hover:bg-red-500 ...` | `btn btn-danger` |

> Recommendation (engineering-principles: single source of truth): replace the three near-identical button files with **one `Button.vue`** taking a `variant` prop (`primary|secondary|danger|success|outline-*`) that maps to `btn btn-{variant}`, plus `type`, `size`, `disabled`, optional `icon`. Keep the three named wrappers as thin re-exports for backward compat, or codemod call-sites. This eliminates duplicated markup and matches Materio's single `.btn` system. If minimizing churn in Phase 2, do the direct class swap above and defer consolidation.

### 6.4 `TextInput.vue`
| `rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500` | `form-control` |
- Preserve `defineModel`, `onMounted` autofocus, `defineExpose({ focus })` — unchanged (behavior).
- For `<select>`/`<textarea>` variants elsewhere, use `form-select` / `form-control`.

### 6.5 `InputLabel.vue`
| `block text-sm font-medium text-gray-700` | `form-label` |
- Keep `value` prop + slot fallback.

### 6.6 `InputError.vue`
| `text-sm text-red-600` (in `v-show="message"`) | wrap in `.invalid-feedback d-block` **or** `<div class="text-danger small mt-1">` |
- Bootstrap convention: input gets `.is-invalid` and error uses `.invalid-feedback`. Simplest parity without touching every input: use `<div class="text-danger small mt-1" v-show="message">`. **Decision: `text-danger small` div** (no dependency on sibling `.is-invalid`), keep `v-show`.

### 6.7 `Checkbox.vue`
| `rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500` | `form-check-input` |
- Preserve `checked` prop + `update:checked` proxy `v-model` (behavior). Typically paired with `<label class="form-check-label">` inside a `.form-check` wrapper at call sites (page-level).

### 6.8 `Modal.vue` (Materio `ui-modals`)
Two viable strategies:

- **Strategy A — Keep the current custom `<dialog>`/Vue-transition implementation, restyle only.** Lowest risk; preserves the exact `show`/`@close`/`closeable`/`maxWidth` API and all consuming pages. Map inner classes:

| Current | Target |
|---|---|
| outer `rounded-lg bg-white shadow-xl ... sm:max-w-2xl` | `modal-content` inside `modal-dialog modal-{size}` |
| backdrop `bg-gray-500 opacity-75` | `modal-backdrop fade show` (or keep custom overlay) |
| `maxWidth` map `sm/md/lg/xl/2xl` → `sm:max-w-*` | `modal-sm` / _(default)_ / `modal-lg` / `modal-xl` (Bootstrap has no `2xl`; map `2xl`→`modal-xl`) |

Content slot then uses Materio structure at call sites: `.modal-header` (+ `.btn-close`), `.modal-body`, `.modal-footer`.

- **Strategy B — Switch to native Bootstrap modal** (`data-bs-toggle`, `bootstrap.Modal` instance). Better Materio parity but changes the API (imperative show/hide vs `show` prop) and would require touching every modal call-site and Inertia-driven open/close. Higher risk.

**Decision: Strategy A** for Phase 2 (restyle, preserve API). `maxWidth` prop values map: `sm→modal-sm`, `md→` (none/default), `lg→modal-lg`, `xl→modal-xl`, `2xl→modal-xl`. Keep `show`, `closeable`, `close` emit, Escape handling, body-scroll lock, and transitions. Only class names change.

Target inner skeleton (Strategy A):
```html
<dialog ref="dialog" class="p-0 border-0 bg-transparent">
  <div class="modal-backdrop fade" :class="{ show }" @click="close"></div>
  <div class="modal fade" :class="{ show, 'd-block': show }">
    <div class="modal-dialog modal-dialog-centered" :class="sizeClass">
      <div class="modal-content">
        <slot v-if="showSlot" />
      </div>
    </div>
  </div>
</dialog>
```
(Keep Vue `<Transition>` wrappers around backdrop/dialog if desired; Bootstrap `.fade`/`.show` and Vue transitions should not both drive opacity — pick one. Recommend keeping Vue transitions and dropping `.fade`.)

### 6.9 `Dropdown.vue`
| Current | Target |
|---|---|
| trigger wrapper `div @click` | keep, or use `.dropdown` + `.dropdown-toggle` |
| menu `absolute z-50 mt-2 rounded-md shadow-lg ... w-48` | `dropdown-menu dropdown-menu-{end|start}` |
| `contentClasses: 'py-1 bg-white'` | `dropdown-menu` provides bg/padding; default `contentClasses` → `''` |
| `align` left/right → origin/`start-0`/`end-0` | `dropdown-menu-start` / `dropdown-menu-end` |
| `width` `48`→`w-48` | Bootstrap dropdown auto-widths; drop `width` or map to inline `min-width` |
- Preserve the Vue open/close state, escape handling, overlay click-to-close (behavior). This component is retained for non-navbar dropdowns; navbar uses native Bootstrap (§4).

### 6.10 `Admin/SidebarLink.vue`
| Current | Target |
|---|---|
| active: `flex items-center gap-3 rounded-lg bg-indigo-600 px-3 py-2 text-white` | parent `<li class="menu-item active">`, link `menu-link` |
| inactive: `... text-slate-300 hover:bg-slate-800` | parent `<li class="menu-item">`, link `menu-link` |
| `#icon` slot (inline SVG) | `<i class="menu-icon tf-icons ri-..."></i>` (Remix class via new `icon` prop) |
| label `<span><slot/></span>` | `<div><slot/></div>` |
- Refactor: replace `#icon` SVG slot with an `icon` prop (Remix class string, per §3.1). Keep `href`, `active`. `active` toggles `active` on the `<li>` wrapper (Materio) rather than swapping the whole class set. Emit no behavior change to Inertia `<Link>`.
- The `<li class="menu-item">` wrapper can live either in this component (component renders its own `<li>`) or in `AdminSidebar.vue`. **Decision: component renders `<li class="menu-item" :class="{active}"> <Link class="menu-link"> ... </Link> </li>`** so callers stay declarative.

### 6.11 `Admin/DataTable.vue` (Materio `tables-basic`)
| Current | Target |
|---|---|
| outer `overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm` | `card` |
| header row `flex flex-wrap items-center justify-between ... border-b px-4 py-4` | `card-header d-flex justify-content-between align-items-center` |
| title `text-base font-semibold text-slate-800` | `card-title mb-0` (or `h5 mb-0`) |
| toolbar `border-b ... px-4 py-3` | `card-body border-bottom` or a `.card-header` sub-row |
| empty `px-6 py-10 text-center text-sm text-slate-400` | `text-center text-muted py-6` inside `card-body` |
| scroll `overflow-x-auto` | `table-responsive` |
| `table min-w-full divide-y divide-slate-200` | `table` (Materio adds row borders) |
| `thead bg-slate-50` | `thead` (optionally `table-light` / `border-top`) |
| `tbody divide-y divide-slate-100 bg-white` | `tbody` (default) |
- Preserve all slots: `#header`, `#toolbar`, `#head`, default (rows), `#footer`, and props `title`, `emptyMessage`, `isEmpty`. Consuming pages provide `<tr>/<th>/<td>` — those cell-level Tailwind classes are page-scope (out of Phase 2 shell scope) but should follow `<th>`/`<td>` plain Bootstrap in later phases.

### 6.12 `Admin/Pagination.vue`
| Current | Target |
|---|---|
| container `flex flex-wrap items-center justify-between ... border-t px-4 py-3` | `d-flex flex-wrap align-items-center justify-content-between p-3 border-top` |
| summary text `text-sm text-slate-500`, `font-medium text-slate-900` | `text-muted small`, `fw-medium text-body` |
| per-page `<select> rounded-md border-slate-300 ...` | `form-select form-select-sm w-auto` |
| per-page label `text-sm text-slate-600` | `small text-muted` |
| links container `flex flex-wrap gap-1` | `pagination pagination-sm` on a `<ul>`, items `<li class="page-item">` |
| link `rounded-md px-3 py-1.5 text-sm` | `page-link` |
| active `bg-blue-600 text-white` | `<li class="page-item active">` |
| disabled `cursor-not-allowed bg-slate-50 text-slate-300` | `<li class="page-item disabled">` |
- Preserve props (`paginator`, `itemLabel`, `perPage`, `perPageOptions`, `routeName`, `routeParams`, `query`, `only`), `hasPages` computed, `onPerPageChange` handler, `v-html="link.label"` (Laravel paginator labels), and the `<component :is="link.url ? Link : 'span'">` pattern. To use Bootstrap `.pagination` markup, wrap each link in `<li class="page-item" :class="{active, disabled}">` and give the inner element `page-link`. Behavior (Inertia `Link`, `preserveState/scroll`, per-page router.get) unchanged.

---

## 7. Materio JS to Wire (`helpers.js`, `menu.js`) & How in Vue

Materio's template JS is imperative and DOM-driven; it must run **after** the layout DOM is mounted and **re-run on Inertia navigations** (Inertia swaps the page component without a full reload, so `DOMContentLoaded`-style init won't fire again).

### 7.1 What each provides
- `helpers.js` (`window.Helpers`): core helpers for the menu/layout — `Helpers.init()`, `Helpers.setAutoUpdate(true)`, `Helpers.initSidebarToggle()`-equivalents, collapse/expand of `.layout-menu`, overlay handling, and `layout-menu-expanded` toggling on `<html>`. Also wires the `.layout-menu-toggle` / `.layout-overlay` clicks.
- `menu.js` (`Menu` class + `window.Menu`): instantiates the vertical menu (`new Menu(document.querySelector('#layout-menu'), {...})`), handling submenu open/close, active-trail, scroll-to-active, and `perfect-scrollbar` on `.menu-inner`.

### 7.2 Where to initialize
Because the sidebar/navbar live in the persistent `AdminLayout.vue` shell (it does NOT unmount between admin page navigations when used as a persistent layout), initialize once when the layout mounts and clean up on unmount:

```js
// AdminLayout.vue <script setup>
import { onMounted, onBeforeUnmount, nextTick } from 'vue';

let menuInstance = null;

onMounted(async () => {
  await nextTick();               // ensure #layout-menu is in the DOM
  window.Helpers?.init?.();       // core layout helpers
  window.Helpers?.setAutoUpdate?.(true);
  const el = document.querySelector('#layout-menu');
  if (el && window.Menu) {
    menuInstance = new window.Menu(el, {
      orientation: 'vertical',
      closeChildren: false,
    });
    window.Helpers?.scrollToActive?.(false);
  }
});

onBeforeUnmount(() => {
  menuInstance?.destroy?.();
  menuInstance = null;
});
```

### 7.3 Persistent layout requirement (critical)
Set `AdminLayout` as an Inertia **persistent layout** so it mounts once and menu JS isn't re-initialized on every page change:
```js
// In each admin page component:
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineOptions({ layout: AdminLayout });
```
or the functional form. If pages instead import `<AdminLayout>` in-template (non-persistent), the layout remounts each navigation and the `onMounted` init above still fires per navigation — acceptable but slightly heavier and can cause a scroll flash. **Decision: use persistent layout (`defineOptions({ layout })`)** for correct menu-JS lifecycle. This may require touching page components; if that's out of Phase 2 scope, fall back to per-navigation init and ensure `menuInstance?.destroy?.()` runs in `onBeforeUnmount` to avoid leaks/duplicate scrollbars.

### 7.4 Active menu state
Two sources of "active": (a) Ziggy `route().current('...')` already drives `active` on `SidebarLink` (keep this — it's the source of truth and survives Inertia nav), and (b) Materio `menu.js` active-trail. Prefer (a) for correctness; `menu.js` still handles scroll-to-active and submenu expansion. Do not rely on `menu.js` to set active classes for top-level links — Vue owns that via `:active`.

### 7.5 Loading the JS
Import in `resources/js/app.js` (or a dedicated `materio.js` imported there) so bundling handles it, exposing `window.Helpers`/`window.Menu`. Alternatively load Materio JS via Blade `@vite`/script tags (Phase 1 decision). Ensure Bootstrap bundle (Popper) loads before `helpers.js`/`menu.js` and before any native dropdown/modal use. **This is a Phase 1 asset concern; Phase 2 only consumes `window.Helpers`/`window.Menu`.**

---

## 8. Behavior Preservation Checklist

Verify each after refactor — nothing below should regress:

**Permissions**
- [ ] Every `SidebarLink` keeps its `v-if="can('...')"` guard (exact permission strings from §3.1).
- [ ] Section headers (`menu-header`) keep the OR-of-permissions `v-if` (AI Platform, Voice, Email).
- [ ] Super-admin (`is_super_admin`) sees all items (via `useAuth().can`).
- [ ] Navbar user dropdown links unaffected by permissions (always shown when authenticated).

**Inertia links / navigation**
- [ ] All nav/dropdown/pagination links use `<Link>` (SPA nav), not `<a href>` — no full page reloads.
- [ ] `route()` names + `.current(...)` patterns unchanged → active highlighting still correct.
- [ ] Logout remains `method="post" as="button"` (CSRF-safe Inertia POST).
- [ ] Pagination `Link` vs `span` fallback (`:is="link.url ? Link : 'span'"`) preserved; `preserveState`/`preserveScroll`/`only` on per-page change preserved.
- [ ] Active/section highlighting survives Inertia navigation (Vue-driven, not DOM-init-driven).

**Modal open/close**
- [ ] `show` prop still opens/closes; `@close` emit still fired.
- [ ] `closeable=false` still blocks close (backdrop click + Escape).
- [ ] Escape key + body-scroll lock (`document.body.style.overflow`) preserved.
- [ ] `maxWidth` prop still controls dialog size (mapped to `modal-sm/lg/xl`).
- [ ] Backdrop click closes (when closeable); slot content lazy-render (`showSlot`) preserved.

**Form v-model / inputs**
- [ ] `TextInput` `defineModel`, autofocus-on-mount, `defineExpose({ focus })` intact.
- [ ] `Checkbox` `checked`/`update:checked` proxy `v-model` intact (Array & Boolean modes).
- [ ] `InputLabel` `value` prop + slot fallback intact.
- [ ] `InputError` shows/hides on `message` (`v-show`) intact.
- [ ] Button `type` prop (submit vs button) respected — critical for forms.

**Layout / responsive**
- [ ] Mobile menu toggle opens/closes sidebar (now via `menu.js` + `layout-menu-expanded`, not Vue `sidebarOpen`).
- [ ] Overlay click closes mobile menu.
- [ ] `#header` slot still renders per-page header content.
- [ ] Fixed menu + navbar-detached scroll behavior works (Materio `layout-menu-fixed`).
- [ ] `perfect-scrollbar` on long menu doesn't duplicate across navigations (destroy on unmount).

**Cleanup / no leaks**
- [ ] `menuInstance.destroy()` on unmount; no duplicate scrollbars or event listeners after repeated navigation.
- [ ] No leftover Tailwind-only classes causing layout conflicts once Bootstrap is loaded.

---

## 9. `AuthenticatedLayout.vue` — Delete? (orphaned)

**Yes — delete `resources/js/Layouts/AuthenticatedLayout.vue`.**

Evidence:
- Repo-wide search for `AuthenticatedLayout` returns **only the file itself** — no imports, no `defineOptions({ layout })`, no template usage anywhere.
- It's the stock Breeze top-nav layout (max-w-7xl navbar, `NavLink`/`ResponsiveNavLink`), superseded by `AdminLayout.vue`.

Cascade check before deleting — these are used **only** by `AuthenticatedLayout.vue`; verify no other references, then remove if orphaned too:
- [ ] `resources/js/Components/NavLink.vue`
- [ ] `resources/js/Components/ResponsiveNavLink.vue`

(`DropdownLink.vue`, `Dropdown.vue`, `ApplicationLogo.vue` are used elsewhere — keep.)

Action: delete `AuthenticatedLayout.vue`; grep `NavLink`/`ResponsiveNavLink` — if only referenced by the deleted file, delete those two as well. Do this as a small standalone cleanup commit so it's easy to revert.

---

## 10. Deliverable Summary / Execution Order (for implementers)

1. (Prereq/Phase 1) Materio SCSS/JS/Remix assets loaded; `<html>` layout classes + Bootstrap bundle wired.
2. Refactor leaf components first (no cross-deps): buttons, `TextInput`, `InputLabel`, `InputError`, `Checkbox` (§6.1–6.7).
3. Refactor `SidebarLink` (`icon` prop + `menu-item`/`menu-link`) (§6.10).
4. Refactor `Modal` (Strategy A restyle) (§6.8) and `Dropdown` (§6.9).
5. Refactor `DataTable` + `Pagination` (§6.11–6.12).
6. Build `AdminSidebar` / `AdminNavbar` / `AdminFooter`, then rewrite `AdminLayout.vue` shell + wire menu JS lifecycle (§1, §3–5, §7).
7. Rewrite `GuestLayout.vue` (§2).
8. Set admin pages to persistent layout if adopting `defineOptions({ layout })` (§7.3).
9. Delete `AuthenticatedLayout.vue` (+ orphaned `NavLink`/`ResponsiveNavLink`) (§9).
10. Run behavior checklist (§8); visual QA against Materio references.

## 11. Open Decisions to Confirm Before Implementation
- Tailwind coexistence vs removal timing (§0).
- `#header` slot placement: content-container top (this spec's decision) vs navbar.
- Role badges: drop from shell (this spec's decision) vs relocate into navbar dropdown.
- Navbar dropdown: native Bootstrap (this spec's decision) vs Vue `Dropdown.vue`.
- Button consolidation into single `Button.vue` (recommended) vs in-place class swap.
- Persistent Inertia layout adoption (recommended) vs per-navigation menu init.
