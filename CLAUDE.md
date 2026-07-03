# Plugin: Wbcom Designs - Birthday Widget for BuddyPress

> **READ FIRST:** [`audit/manifest.json`](audit/manifest.json) is the canonical inventory — 0 REST endpoints, 1 AJAX action (priv+nopriv), 1 admin page, 1 shortcode, 2 cron hooks, 5 fired filters, 0 tables (reads BuddyPress xProfile tables read-only). Use this before grepping. See also [`audit/FEATURE_AUDIT.md`](audit/FEATURE_AUDIT.md), [`audit/CODE_FLOWS.md`](audit/CODE_FLOWS.md), and the QA baseline [`audit/wppqa-baseline-2026-06-05/SUMMARY.md`](audit/wppqa-baseline-2026-06-05/SUMMARY.md). Interactive graph: `cd audit && python3 -m http.server 8765` → http://localhost:8765/graph.html. Refresh via `/wp-plugin-onboard --refresh` after non-trivial changes.

## Development skill
All plugin work follows the **`/wp-plugin-development`** skill (backend architecture, REST, hooks, DB, security, escaping, admin UI, release). QA before release: `/wp-plugin-smoke`. Onboarding refresh: `/wp-plugin-onboard`.

## Quick reference
- **Main file**: `buddypress-birthdays.php`
- **Version**: `2.4.1` (dev branch `2.5.0`)
- **Namespace**: none — global `bb_*` functions + `BP_Birthdays_*` / `Widget_Buddypress_Birthdays` classes
- **Text domain**: `buddypress-birthdays`
- **Extends**: null (standalone; requires BuddyPress)
- **Repo**: vapvarun/buddypress-birthdays

## Admin UI wrapper: NEW (card-panel under wbcomplugins) — as of 2.5.0
The admin migrated to the **Wbcom card-panel pattern** under the shared `wbcomplugins` hub (mirrors `buddypress-contact-me`):
- Controller: `BP_Birthdays_Admin_Panel` (`includes/admin/class-bp-birthdays-admin-panel.php`) — menu, enqueue, render router, hub takeover (pri 999).
- Views: `includes/admin/views/{shell,hub,overview,settings-general,settings-email,settings-activity,settings-notifications,settings-display}.php`.
- Assets: `assets/css/admin.css` (`--bbd-admin-*` tokens), `assets/js/admin.js` (`bbdToast`/`bbdConfirm` + dependent-field toggles).

Menu: `add_menu_page('wbcomplugins', …)` (if not present) + `add_submenu_page('wbcomplugins', …, 'bp-birthday-settings', …)`. Slug `bp-birthday-settings` **preserved** for URL continuity. Cap `manage_options` (filter `bp_birthdays_admin_capability`). Page hook suffix: `wbcomplugins_page_bp-birthday-settings`. The old `bp-settings`/`options-general.php` registration was dropped.

`BP_Birthdays_Admin` (`admin/class-bp-birthdays-admin.php`) is now a **legacy service class** retained ONLY for `$defaults`, `get_instance()`, `sanitize_settings()`, `get_settings()` — its UI methods were deleted. The panel's `register_setting` delegates sanitization to it, so the save contract is byte-identical.

Single stored option `bp_birthdays_settings` (group `bp_birthdays_settings_group`) — unchanged. Multi-tab saves are guarded by the legacy sanitizer's merge-on-stored + a `bp_birthdays_tab_rendered_keys` sentinel (Playbook 7.1).

## Key entry points
- Bootstrap: `buddypress-birthdays.php` (constants, BP dependency check, requires)
- Core: `core-init.php` (assets, shortcode, AJAX, cache invalidation, wished-users cron)
- Admin: `admin/class-bp-birthdays-admin.php` (settings page + option)
- Notifications: `includes/class-bp-birthdays-notifications.php` (cron, BP emails, activity, BP notifications)
- Helpers: `includes/class-bp-birthdays-helpers.php` (zodiac, age, date format)
- Widget: `assets/inc/buddypress-birthdays-widget.php` (`Widget_Buddypress_Birthdays`)

## Important patterns
- Caching: WordPress **object cache** group `bp_birthdays` (NOT transients). 30-min TTL. Invalidated on xprofile save / friendship / follow / user register-delete / daily cron and on widget settings save.
- Prefix conventions: functions `bb_*`, classes `BP_Birthdays_*`, option root `bp_birthdays_*`, cron `bb_cleanup_old_wishes` + `bp_birthdays_daily_check`, AJAX `bb_birthdays_action`, nonce `bb_birthdays_nonce`.
- BuddyPress version-aware: branches on `buddypress()->version >= 12.0` for member-URL/slug APIs.
- Owns no DB tables — reads `bp_xprofile_*` read-only; never write BP tables outside BP APIs.

## Known scale risks (see manifest static_analysis.notes)
- BB-2: cron `get_todays_birthdays` uses non-sargable `DATE_FORMAT(value,'%m-%d')` full scan of `bp_xprofile_data`.
- BB-3: widget "all members" path is O(members) with per-user N+1 visibility/date lookups and no SQL `LIMIT` (trim happens after full fetch+sort). Address before claiming 2000+ member readiness.
- BB-4: BP-notification non-friends fan-out silently capped at `number=500`.

## CSS selectors (for testing)
`.bp-birthday-users-list`, `.bp-birthday-item`, `.today-birthday`, `.bp-birthday-name`, `.bp-birthday-age`, `.bp-birthday-date`, `.bp-send-wishes`, `.bp-birthday-pagination`, `.bp-birthday-page-btn`.

## Recent changes
| Date | Type | Description | Files |
|---|---|---|---|
| 2026-07-03 | bug-fix | Widget "all members" SQL: convert PHP date_format meta to MySQL specifiers for STR_TO_DATE (was NULL for every row), prepare-safe `%%m-%%d` masks + bound args (was wpdb::prepare placeholder-count notice + rejected query). New `BP_Birthdays_Helpers::php_to_mysql_date_format()`. | `assets/inc/buddypress-birthdays-widget.php`, `includes/class-bp-birthdays-helpers.php` |
| 2026-07-03 | bug-fix | Cache duration honoured on frontend: read `bp_birthdays_settings` option directly (admin class only loads in `is_admin()`, so TTL always fell back to 30 min). | `assets/inc/buddypress-birthdays-widget.php` |
| 2026-07-03 | bug-fix | Birthday email templates now install: replaced dead `bp_get_email_post()` guard (function never existed in BP) with real post-type/taxonomy checks + idempotent per-type existence lookup. | `includes/class-bp-birthdays-notifications.php` |
| 2026-07-03 | bug-fix | Docs link corrected to singular `/docs/buddypress-birthday/` slug. | `includes/admin/views/shell.php` |
| 2026-06-05 | refactor | Migrated admin to card-panel under `wbcomplugins` hub (v2.5.0). New `BP_Birthdays_Admin_Panel` + views + token CSS/JS; legacy class trimmed to sanitizer/getter only; dropped `bp-settings`/options-general registration. Option key + 13 subkeys preserved. | `includes/admin/*`, `assets/css/admin.css`, `assets/js/admin.js`, `admin/class-bp-birthdays-admin.php`, `buddypress-birthdays.php` |
| 2026-06-05 | bug-fix | Cache TTL now honours saved `cache_duration` (was hardcoded 30 min ghost control). | `assets/inc/buddypress-birthdays-widget.php` |
| 2026-06-05 | onboard | Generated audit/ inventory + reports + graph + wppqa baseline; READ-FIRST CLAUDE.md | audit/*, CLAUDE.md |
