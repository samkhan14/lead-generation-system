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
- [x] Phase 3A: Leads, Dashboard, Scraper
- [x] Phase 3B: Admin modules (Services, AI, Voice, Email) + Profile + Auth
- [ ] Manual: Remix icons render in browser (`ri-home-smile-line`)
- [ ] Manual: Admin CRUD flows (Services, AI providers, Email campaigns)
- [ ] Manual: Login → Dashboard → Leads flow works

### Phase 3A complete (Leads / Dashboard / Scraper)

- Badges, score cards, Dashboard
- Leads Create, Index, Show + BulkVoiceCallModal, LeadWorkforcePanel
- WebsiteAnalysisPanel, ScraperNotification
- Scraper Index, Show

### Phase 3B complete (Admin modules)

**26 pages + 8 form modals + `EmailHtmlEditor`**

| Module | Files |
|--------|-------|
| Services | Index, Show + `ServiceFormModal` |
| Voice | Providers + Calls (Index/Show) + `VoiceProviderFormModal` |
| AI | Providers, Models, Prompts, Knowledge, Employees, Logs (Index/Show each) + 5 form modals |
| Email | Providers, Campaigns, Sends (Index/Show/Form/Compose) + `EmailProviderFormModal`, `EmailHtmlEditor` |

Also migrated in this pass (originally Phase 3C–3D):

- Profile: `Edit.vue` + 3 partials
- Auth: Login, Register, Forgot/Reset password, Verify email, Confirm password

**Patterns:** `card` + `DataTable` toolbar, `badge bg-label-*`, `alert`, `modal-header/body/footer`, shared `Pagination`.

### Next: Phase 4

- Migrate `Welcome.vue` (Laravel default landing — still Tailwind)
- Remove Tailwind dependency when no pages use utilities
- Final visual QA pass (`docs/manual-qa-checklist.md`)

### Materio source reference

Clone for HTML markup reference:

```powershell
git clone --depth 1 https://github.com/themeselection/materio-bootstrap-html-admin-template-free.git ..\materio-bootstrap-html-admin-template-free
```

License: MIT — keep ThemeSelection footer link in admin layout (Phase 2).
