# Wrapper Audit: buddypress-birthdays (branch 2.5.0)

**Date:** 2026-06-05
**Auditor:** AutoVAP (READ-ONLY — no code was changed)
**Classification:** NONE — self-contained WP Settings API tabbed page

---

## 1. NONE Classification — Still Accurate?

**YES. Classification confirmed accurate.**

Evidence (grepped entire plugin tree):
- `admin/wbcom/wbcom-admin-settings.php` — absent
- `includes/shared-admin/class-wbcom-shared-dashboard.php` — absent
- `includes/admin/views/shell.php` — absent
- Zero occurrences of `wbcomplugins`, `wbcom_admin`, `wbcom-designs`, `wbcom_admin_setting_header` anywhere in PHP/JS/CSS source files.

Menu: `add_submenu_page` with parent `bp-settings` (BuddyPress menu), slug `bp-birthday-settings`, capability `manage_options`, fallback to `options-general.php` when BuddyPress menu is absent. No `wbcomplugins` parent, no leftover separate top-level menu.

---

## 2. Settings Wiring — End-to-End

Single option: `bp_birthdays_settings` (array), group `bp_birthdays_settings_group`, sanitizer `BP_Birthdays_Admin::sanitize_settings`.

Constants used for asset paths:
- `BIRTHDAY_WIDGET_PLUGIN_URL` defined at `buddypress-birthdays.php:26` via `plugin_dir_url(__FILE__)` — resolves to plugin ROOT. PASS.
- `BIRTHDAY_WIDGET_PLUGIN_PATH` defined at `buddypress-birthdays.php:27` via `plugin_dir_path(__FILE__)` — resolves to plugin ROOT. PASS.
- Both constants are used correctly in `admin/class-bp-birthdays-admin.php:212,219` for asset enqueues. No 404-risk.

### Option Wiring Table

| Subkey | Registered? | Rendered? (field + name attr) | Saved + Sanitized? | Read-back (value in field on reload)? | Consumed by feature? | Verdict |
|---|---|---|---|---|---|---|
| `default_field_id` | YES (group `bp_birthdays_settings_group`) | YES — select `bp_birthdays_settings[default_field_id]`, general tab line 299 | YES — `absint()` in sanitize_settings:134 | YES — `selected()` on line 302 | YES — notifications.php:438 via `get_setting('default_field_id')` | PASS |
| `cache_duration` | YES | YES — number input `bp_birthdays_settings[cache_duration]`, general tab line 319 | YES — `absint()` at sanitize_settings:138 | YES — `esc_attr($settings['cache_duration'])` on line 320 | PARTIAL — localized to `bbBirthdays.settings.cache_duration` (core-init.php:110) but actual widget object cache TTL is hardcoded `30 * MINUTE_IN_SECONDS` at widget.php:72; the saved value is never read for actual cache expiry | **MEDIUM: UI control is cosmetic — saving a different value has no effect on real cache TTL** |
| `email_enabled` | YES | YES — checkbox `bp_birthdays_settings[email_enabled]`, email tab line 349 | YES — bool from `'1' === $input['email_enabled']` at sanitize_settings:142 | YES — `checked($settings['email_enabled'])` on line 351 | YES — notifications.php:404 via `get_setting('email_enabled')` | PASS |
| `email_send_time` | YES | YES — time input `bp_birthdays_settings[email_send_time]`, email tab line 385 | YES — `sanitize_text_field()` at sanitize_settings:145 | YES — `esc_attr($settings['email_send_time'])` on line 386 | YES — notifications.php:358 via `get_setting('email_send_time')` | PASS |
| `admin_email_enabled` | YES | YES — checkbox `bp_birthdays_settings[admin_email_enabled]`, email tab line 405 | YES — bool at sanitize_settings:148 | YES — `checked($settings['admin_email_enabled'])` on line 407 | YES — notifications.php:425 via `get_setting('admin_email_enabled')` | PASS |
| `admin_email` | YES | YES — email input `bp_birthdays_settings[admin_email]`, email tab line 412 | YES — `sanitize_email()` at sanitize_settings:151 | YES — `esc_attr($settings['admin_email'])` on line 413 | YES — notifications.php:721 via `get_setting('admin_email')` | PASS |
| `activity_enabled` | YES | YES — checkbox `bp_birthdays_settings[activity_enabled]`, activity tab line 445 | YES — bool at sanitize_settings:155 | YES — `checked($settings['activity_enabled'])` on line 448 | YES — notifications.php:409 via `get_setting('activity_enabled')` | PASS |
| `activity_message` | YES | YES — text input `bp_birthdays_settings[activity_message]`, activity tab line 460 | YES — `sanitize_text_field()` at sanitize_settings:159 | YES — `esc_attr($settings['activity_message'])` on line 462 | YES — notifications.php (activity post path) | PASS |
| `notification_enabled` | YES | YES — checkbox `bp_birthdays_settings[notification_enabled]`, notifications tab line 493 | YES — bool at sanitize_settings:163 | YES — `checked($settings['notification_enabled'])` on line 496 | YES — notifications.php:414 via `get_setting('notification_enabled')` | PASS |
| `notification_friends_only` | YES | YES — checkbox `bp_birthdays_settings[notification_friends_only]`, notifications tab line 507 | YES — bool at sanitize_settings:167 | YES — `checked($settings['notification_friends_only'])` on line 509 | YES — notifications.php:623 via `get_setting('notification_friends_only')` | PASS |
| `notification_text` | YES | YES — text input `bp_birthdays_settings[notification_text]`, notifications tab line 523 | YES — `sanitize_text_field()` at sanitize_settings:170 | YES — `esc_attr($settings['notification_text'])` on line 525 | YES — notifications.php (BP notification path) | PASS |
| `confetti_enabled` | YES | YES — checkbox `bp_birthdays_settings[confetti_enabled]`, display tab line 550 | YES — bool at sanitize_settings:174 | YES — `checked($settings['confetti_enabled'])` on line 553 | YES — core-init.php:83-84 reads `get_option('bp_birthdays_settings')` directly, localizes to `bbBirthdays.settings.confetti_enabled` | PASS |
| `zodiac_enabled` | YES | YES — checkbox `bp_birthdays_settings[zodiac_enabled]`, display tab line 566 | YES — bool at sanitize_settings:178 | YES — `checked($settings['zodiac_enabled'])` on line 569 | YES — widget.php:255-256 reads via `get_option('bp_birthdays_settings')` directly | PASS |

**Runtime-only options (not in UI — correct):**
- `bp_birthdays_emails_installed` — one-time flag, written by notifications class, not a settings UI field. Correct.
- `bp_birthdays_last_check_date` — internal daily rollover, not a settings UI field. Correct.
- `bp_birthdays_sent_today` — per-day dedupe map, not a settings UI field. Correct.

### Multi-tab Data-Loss Guard

All five tabs (general, email, activity, notifications, display) write into ONE option array `bp_birthdays_settings`. The sanitizer at `sanitize_settings()` lines 128-130 implements a merge:

```php
$existing_settings = get_option( self::OPTION_NAME, array() );
$sanitized = $existing_settings;
```

Each subkey is then conditionally overwritten only when `isset($input[$key])`. This is the correct sentinel-guarded merge (Playbook 7.1 equivalent). A save on one tab does NOT blank keys owned by another tab — the merge preserves all existing values. **PASS.**

---

## 3. Scale Findings (Confirm Still Present — Out of Scope to Fix)

All three onboarding findings remain present in the source:

- **BB-2 (medium) — confirmed present:** `includes/class-bp-birthdays-notifications.php:461` — `DATE_FORMAT(d.value, '%%m-%%d')` in the cron `get_todays_birthdays` query. Non-sargable function wrap on an unindexed `value` column forces a full `bp_xprofile_data` table scan on every daily cron run.

- **BB-3 (medium/high) — confirmed present:** `assets/inc/buddypress-birthdays-widget.php:424-429` — "all members, no range" path runs `SELECT DISTINCT user_id` with no `LIMIT`, returning every member with the field set. Post-fetch sort and trim at widget.php:93 (`min($total_birthdays, $birthdays_to_display)`) only limits the *display count* after the full PHP array is built. Per-user xprofile + visibility lookups inside the subsequent `foreach` (widget.php:438+) create an O(members) N+1 on large sites.

- **BB-4 (low) — confirmed present:** `includes/class-bp-birthdays-notifications.php:631` — `'number' => 500` hard cap in the non-friends `get_users()` call. Silent truncation on communities larger than 500 members.

---

## 4. Hygiene Checks

**alert() / confirm() in admin JS:** None found. `admin/js/admin-settings.js` uses only jQuery DOM manipulation for field toggling. PASS.

**Admin CSS — raw hex values:** Present. `admin/css/admin-settings.css` uses the following raw hex values (all are WordPress core palette values, but not CSS custom properties):
- Line 48: `background: #f0f0f1` (WP neutral gray)
- Line 51: `color: #50575e` (WP text secondary)
- Line 56: `color: #646970` (WP description gray)
- Line 63: `background: #f0f0f1`
- Line 83: `border-top: 1px solid #c3c4c7` (WP border gray)

This plugin has NO design-system token layer (`--bb-*` or `--bp-*` custom properties). Since this is a NONE-classified plugin with no Wbcom wrapper, there is no token contract to enforce — these hex values mirror standard WordPress admin palette. Low severity.

**Tap targets:** `submit_button()` uses WP core `.button-primary` which is ≥ 32px. Checkbox labels are inline; no touch-target below 24px on the settings rows. No severe violation for an admin-only settings page.

**Screen-scoped enqueue:** `enqueue_admin_assets()` (line 207-208) guards on `'buddypress_page_bp-birthday-settings' !== $hook && 'settings_page_bp-birthday-settings' !== $hook` before enqueuing. CSS and JS only load on the settings page itself. PASS.

---

## 5. Findings — Severity Ranked

| # | Severity | Finding | File:line | Suggested journey to fix |
|---|---|---|---|---|
| 1 | MEDIUM | `cache_duration` setting is a ghost control: the admin field saves the value but the widget object cache TTL is hardcoded `30 * MINUTE_IN_SECONDS` and never reads the stored value. Admins who configure a shorter or longer cache duration get no effect. | `assets/inc/buddypress-birthdays-widget.php:72`, `admin/class-bp-birthdays-admin.php:317-328` | `bug-fix`: read `BP_Birthdays_Admin::get_settings('cache_duration')` before `wp_cache_set`, multiply by `MINUTE_IN_SECONDS` |
| 2 | MEDIUM | BB-2 (scale): cron `DATE_FORMAT(d.value,'%%m-%%d')` query is non-sargable — full `bp_xprofile_data` scan on every daily run. No index on `(field_id, value)`. | `includes/class-bp-birthdays-notifications.php:454-466` | `wp-performance`: add a generated/virtual column or rewrite to a range-based date filter with index support |
| 3 | MEDIUM | BB-3 (scale): widget "all members" path fetches ALL user IDs matching the field with no SQL `LIMIT`; per-user lookups inside foreach create O(members) N+1. | `assets/inc/buddypress-birthdays-widget.php:424-429, 438+` | `wp-performance`: apply `LIMIT` in the SQL (fetch only `$birthdays_to_display + safety margin`), eliminate N+1 by batching meta/xprofile reads |
| 4 | LOW | BB-4 (scale): non-friends notification fan-out silently capped at 500 via `'number' => 500` in `get_users()`. No notice to admin when community exceeds cap. | `includes/class-bp-birthdays-notifications.php:631` | `bug-fix`: either paginate the notification dispatch or surface a warning in the admin |
| 5 | LOW | Raw hex colors in admin CSS (`#f0f0f1`, `#50575e`, `#646970`, `#c3c4c7`) — no CSS token layer. Not a functional bug for a NONE-wrapper plugin; violates design-system hygiene if the plugin is ever brought into the Wbcom card-panel suite. | `admin/css/admin-settings.css:48,51,56,63,83` | `css-styling` if/when migrating to wbcomplugins wrapper |

---

## 6. Migration Recommendation

**Should this plugin adopt the card-panel wrapper under `wbcomplugins` for portfolio consistency?**

Yes, eventually — but it is low urgency. The plugin sits under the BuddyPress menu by design (it is a BuddyPress addon, so co-location with other BP settings is a reasonable UX choice). Migration effort is moderate: create `includes/admin/` with `shell.php`, `hub.php`, and per-tab view files; refactor `BP_Birthdays_Admin` to implement the full controller contract; add `wbcomplugins` as parent slug; replace raw hex with design tokens. Payoff is primarily visual portfolio consistency, not functional — the existing Settings API page works correctly. If the `wbcomplugins` hub is intended as the single discovery point for all Wbcom plugins, migration is worthwhile; otherwise the current placement under BuddyPress is defensible and should stay.

---

## Overall Verdict

**FIX-NEEDED**

Must-fix before claiming the settings UI is fully functional:

1. **Finding #1 (MEDIUM) — `cache_duration` ghost control.** The setting exists in the UI and saves correctly but has zero runtime effect. This is a silent lie to the admin. Fix: wire the saved value into `wp_cache_set` at widget.php:72.

The two scale findings (BB-2, BB-3) are pre-documented in the onboarding manifest as known gaps, not regressions introduced in 2.5.0. They are out of scope for this wrapper audit pass but must be resolved before any "2000+ member site" readiness claim.

Settings wiring for all 12 active subkeys is otherwise complete and correct. Multi-tab data-loss guard is properly implemented. No Wbcom wrapper contamination. NONE classification stands.
