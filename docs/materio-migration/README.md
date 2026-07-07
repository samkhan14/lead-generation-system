# Materio UI Migration

Branch: `feat/materio-ui`

## Phase 0–1 Foundation (complete)

### Installed packages

- `bootstrap@~5.3.5`, `@popperjs/core`, `sass@1.78.0`
- `perfect-scrollbar`, `node-waves`

### Asset layout

| Source | Destination |
|--------|-------------|
| Materio `scss/` | `resources/scss/materio/` |
| `helpers.js`, `menu.js` | `resources/js/materio/vendor/` |
| `config.js` | `resources/js/materio/` |
| `iconify-icons.css` | `public/vendor/materio/fonts/` |
| favicon | `public/vendor/materio/img/favicon/` |

### Entry points

- **CSS:** `resources/scss/materio/app.scss` → imported in `resources/js/app.js`
- **JS:** `resources/js/materio/index.js` → sets `window.Helpers`, `window.Menu`, `window.bootstrap`, `window.Waves`
- **Composable:** `useMaterio()` for Phase 2 layout shells

### Coexistence

Tailwind remains enabled with **`preflight: false`** so Bootstrap reboot owns base styles. Unmigrated pages keep Tailwind utilities.

### Acceptance checklist

- [x] `npm run build` succeeds
- [x] Materio CSS bundle ~601 KB (Bootstrap + Materio theme)
- [x] `php artisan test` — 246 tests pass
- [x] Phase 2: AdminLayout, GuestLayout, core components restyled
- [ ] Manual: Remix icons render in browser (`ri-home-smile-line`)
- [ ] Manual: Login → Dashboard → Leads flow works

### Next: Phase 3

Migrate page modules (Leads, Dashboard, Admin CRUD) from Tailwind to Materio classes.

See `phase-2-layout-shell-core-components.md` (complete) and Phase 3 specs in the migration plan.

### Materio source reference

Clone for HTML markup reference:

```powershell
git clone --depth 1 https://github.com/themeselection/materio-bootstrap-html-admin-template-free.git ..\materio-bootstrap-html-admin-template-free
```

License: MIT — keep ThemeSelection footer link in admin layout (Phase 2).
