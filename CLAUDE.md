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

## Admin UI wrapper: NONE
This plugin uses a **self-contained WordPress Settings API tabbed page** (`BP_Birthdays_Admin`, `admin/class-bp-birthdays-admin.php`). It does NOT use any Wbcom shared admin wrapper:
- OLD (`admin/wbcom/wbcom-admin-settings.php`) — absent
- INTERMEDIATE (`includes/shared-admin/class-wbcom-shared-dashboard.php`) — absent
- NEW (`includes/admin/views/shell.php`) — absent

Menu: `add_submenu_page` parent `bp-settings` (BuddyPress) with fallback to `options-general.php`, slug `bp-birthday-settings`, cap `manage_options`, on `admin_menu` @20. Page hook suffix: `buddypress_page_bp-birthday-settings` or `settings_page_bp-birthday-settings`. Single stored option `bp_birthdays_settings` (group `bp_birthdays_settings_group`).

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
| 2026-06-05 | onboard | Generated audit/ inventory + reports + graph + wppqa baseline; READ-FIRST CLAUDE.md | audit/*, CLAUDE.md |
