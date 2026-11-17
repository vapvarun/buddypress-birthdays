# Plugin: Wbcom Designs - Birthday Widget for BuddyPress

> **READ FIRST:** [`audit/manifest.json`](audit/manifest.json) is the canonical inventory — 0 REST endpoints, 0 blocks, 1 widget (`widget_buddypress_birthdays`), 1 AJAX action (priv+nopriv), 1 admin page, 1 shortcode (11 attrs), 2 cron hooks, 12 fired filters / 0 fired actions, 0 tables (reads BuddyPress xProfile tables read-only). Use this before grepping. See also [`audit/FEATURE_AUDIT.md`](audit/FEATURE_AUDIT.md), [`audit/CODE_FLOWS.md`](audit/CODE_FLOWS.md), and the QA baseline [`audit/wppqa-baseline-2026-06-05/SUMMARY.md`](audit/wppqa-baseline-2026-06-05/SUMMARY.md). Interactive graph: `cd audit && python3 -m http.server 8765` → http://localhost:8765/graph.html. Refresh via `/wp-plugin-onboard --refresh` after non-trivial changes.

## Development skill
All plugin work follows the **`/wp-plugin-development`** skill (backend architecture, REST, hooks, DB, security, escaping, admin UI, release). QA before release: `/wp-plugin-smoke`. Onboarding refresh: `/wp-plugin-onboard`.

## Quick reference
- **Main file**: `buddypress-birthdays.php`
- **Version**: see `BIRTHDAY_WIDGET_VERSION` in `buddypress-birthdays.php`. `master` = last release; one dev branch named by version.
- **Requires**: WP 6.5+, PHP 8.1+ (portfolio range 8.1-8.5), BuddyPress (`Requires Plugins: buddypress`). License: bare `GPLv3`.
- **Namespace**: none — global `bb_*` functions + `BP_Birthdays_*` / `Widget_Buddypress_Birthdays` classes
- **Text domain**: `buddypress-birthdays`
- **Extends**: null (standalone; requires BuddyPress)
- **Repo**: vapvarun/buddypress-birthdays

## Admin UI wrapper: NEW (card-panel under wbcomplugins) — as of 2.5.0
The admin migrated to the **Wbcom card-panel pattern** under the shared `wbcomplugins` hub (mirrors `buddypress-contact-me`):
- Controller: `BP_Birthdays_Admin_Panel` (`includes/admin/class-bp-birthdays-admin-panel.php`) — menu, enqueue, render router, hub takeover (pri 999).
- Views: `includes/admin/views/{layout,hub,overview,settings-general,settings-email,settings-activity,settings-notifications,settings-display,discover}.php`.
- Tabs (7): overview, general, email, activity, notifications, display, discover. `discover` is appended **after** the `bp_birthdays_admin_tabs` filter so it can't be filtered away.
- Assets: `assets/css/admin.css` (`--bbd-admin-*` tokens), `assets/js/admin.js` (`bbdToast`/`bbdConfirm` + dependent-field toggles).
- Never name a file `shell.php`: host WAFs block zips that contain one.

Menu: `add_menu_page('wbcomplugins', …)` (if not present) + `add_submenu_page('wbcomplugins', …, 'bp-birthday-settings', …)`. Slug `bp-birthday-settings` **preserved** for URL continuity. Cap `manage_options` (filter `bp_birthdays_admin_capability`). Page hook suffix: `wbcomplugins_page_bp-birthday-settings`. The old `bp-settings`/`options-general.php` registration was dropped.

`BP_Birthdays_Admin` (`admin/class-bp-birthdays-admin.php`) is now a **legacy service class** retained ONLY for `$defaults`, `get_instance()`, `sanitize_settings()`, `get_settings()` — its UI methods were deleted. The panel's `register_setting` delegates sanitization to it, so the save contract is byte-identical.

Single stored option `bp_birthdays_settings` (group `bp_birthdays_settings_group`) — unchanged. Multi-tab saves are guarded by the legacy sanitizer's merge-on-stored + a `bp_birthdays_tab_rendered_keys` sentinel (Playbook 7.1).

## Key entry points
- Bootstrap: `buddypress-birthdays.php` (constants, BP dependency check, requires)
- Core: `core-init.php` (assets, shortcode, AJAX, cache invalidation, wished-users cron, GDPR opt-out UI/save)
- Admin: `includes/admin/class-bp-birthdays-admin-panel.php` (menu, enqueue, render router) + `admin/class-bp-birthdays-admin.php` (legacy service class — sanitizer/defaults/getter ONLY, no UI)
- Notifications: `includes/class-bp-birthdays-notifications.php` (cron, BP emails, activity, BP notifications)
- Helpers: `includes/class-bp-birthdays-helpers.php` (zodiac, age, opt-out, `get_user_url()`, `get_birthday_field_id()`, `visibility_allows()`)
- Widget: `assets/inc/buddypress-birthdays-widget.php` (`Widget_Buddypress_Birthdays`)

## Important patterns
- Caching: WordPress **object cache** group `bp_birthdays` (NOT transients). 30-min TTL. Invalidated on xprofile save / friendship / follow / user register-delete / daily cron and on widget settings save.
- Prefix conventions: functions `bb_*`, classes `BP_Birthdays_*`, option root `bp_birthdays_*`, cron `bb_cleanup_old_wishes` + `bp_birthdays_daily_check`, AJAX `bb_birthdays_action`, nonce `bb_birthdays_nonce`.
- Member URLs: always `BP_Birthdays_Helpers::get_user_url()` (BP 12+ `bp_members_get_user_url()`, older fallback). Never call `bp_core_get_user_domain()` directly.
- Birthday field: always `BP_Birthdays_Helpers::get_birthday_field_id()` (General tab setting if it is a real date field, else first date field). Cron, shortcode and widget fallback all use it.
- Privacy: `BP_Birthdays_Helpers::is_user_opted_out()` gates every surface; BuddyPress field visibility gates per channel via `visibility_allows()` (activity = public only, notification recipients filtered, admin summary skips Only Me, greeting email goes to the member regardless).
- Cron tracking (`bp_birthdays_sent_today`, keyed by day `Y-m-d`, pruned to yesterday and later): per member `{email,activity,notification => time}` plus `admin_summary`. The daily event has no args; `resolve_target_date()` processes yesterday when it runs before today's Send Time (late run). A failed channel stays unrecorded and a `bp_birthdays_daily_check` single event with args `[Y-m-d]` retries that same day +1h, until the end of the next day. Feb 29 birthdays match on Feb 28 in non-leap years via `BP_Birthdays_Helpers::birthday_month_days()` (also used by the widget's Today check). `schedule_cron()` re-times the daily event when Send Time changes. Deactivate/uninstall use `wp_unschedule_hook()`.
- BP emails: the two birthday types are installed by `ensure_email_posts()` on `bp_core_install_emails` and the `admin_init` self-heal, never via `bp_email_get_schema` (BuddyPress would insert duplicates and warn on the unfilterable type schema). The admin summary's BuddyPress salutation is removed with `bp_email_get_salutation`.
- Admin capability: `BP_Birthdays_Admin_Panel::get_capability()` gates the menu and the options.php save (`option_page_capability_bp_birthdays_settings_group`).
- Settings tabs: a switch that can be disabled (dependent row, inactive component) must not be listed in `bp_birthdays_tab_rendered_keys` while disabled, or saving resets it.
- Stored datebox values are always `Y-m-d H:i:s`; the field's `date_format` meta is display only. SQL masks are fixed `%Y-%m-%d`.
- Dark mode (frontend): `bb-core.css` tokens chain `--bx-color-*` -> `--reign-*` -> literal, with a `:root[data-bx-mode="dark"]` block.
- Owns no DB tables — reads `bp_xprofile_*` read-only; never write BP tables outside BP APIs.

## Known scale risks (see manifest static_analysis.notes)
- BB-2: cron `get_todays_birthdays` uses non-sargable `DATE_FORMAT(value,'%m-%d')` full scan of `bp_xprofile_data`.
- BB-3: RESOLVED 2026-07-03 — all widget paths (all members, friends, followers) now trim to a bounded SQL candidate pool (`bb_birthdays_widget_candidate_multiplier`/`_cap` filters, default cap 200) ordered by upcoming-birthday proximity, with batch-primed values (no per-user N+1). The `DATE_FORMAT` predicate is still non-sargable but bounded by `field_id` + `LIMIT`.
- BB-4: BP-notification non-friends fan-out capped at 500 (filter `bb_birthdays_notification_recipient_limit`, truncation logged under WP_DEBUG).

## CSS selectors (for testing)
`.bp-birthday-users-list`, `.bp-birthday-item`, `.today-birthday`, `.bp-birthday-name`, `.bp-birthday-age`, `.bp-birthday-date`, `.bp-send-wishes`, `.bp-birthday-pagination`, `.bp-birthday-page-btn`.
