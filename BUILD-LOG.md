# BUILD-LOG — buddypress-birthdays wrapper migration (branch 2.5.0)

**Date:** 2026-06-05
**Scope:** Migrate the standalone WP Settings-API tabbed admin page to the
card-panel pattern under the shared `wbcomplugins` hub + fix the
`cache_duration` ghost-control bug (WRAPPER-AUDIT finding #1, MED).
**Reference pattern:** `buddypress-contact-me` (wbcom-pro), Playbook
`references/wbcom-wrapper-migration.md`.

NOT committed / pushed. No DB writes. No activation toggles.

---

## Classification

Was **NONE** (self-contained Settings-API page under `bp-settings` with an
`options-general.php` fallback, class `BP_Birthdays_Admin`). There was NO
`admin/wbcom/` OLD wrapper and NO `includes/shared-admin/` INTERMEDIATE
glue to delete — the migration is a *build the new card panel + drop the
old standalone registration* job, not a wrapper teardown.

---

## Files DELETED (4)

| File | Why |
|---|---|
| `admin/css/admin-settings.css` | Old standalone settings-page CSS; superseded by `assets/css/admin.css`. No longer enqueued. |
| `admin/css/admin-settings.min.css` | Minified twin of the above. |
| `admin/js/admin-settings.js` | Old dependent-field toggle JS; logic ported into `assets/js/admin.js`. |
| `admin/js/admin-settings.min.js` | Minified twin. |

Empty `admin/css/` and `admin/js/` directories removed. Only
`admin/class-bp-birthdays-admin.php` remains under `admin/`.

## Files CREATED (10)

| File | Role |
|---|---|
| `includes/admin/class-bp-birthdays-admin-panel.php` | Card-panel controller: `get_tabs()`, `register()`, `add_menu()` (→ `wbcomplugins`), `takeover_hub_landing()` (pri 999), `register_settings()`, `sanitize_settings()` (delegates to legacy), `enqueue_assets()` (screen-scoped), `suppress_foreign_notices()`, `is_our_screen()`, `render_hub()`, `render_page()`, `get_settings()`, `get_date_fields()`, `get_bp_emails_url()`. |
| `includes/admin/views/shell.php` | Page header + sidebar nav + body slot; `wp-header-end` marker; settings form wraps the active settings tab. |
| `includes/admin/views/hub.php` | WB Plugins hub landing (card grid, wrapper-helper-slug filter). |
| `includes/admin/views/overview.php` | Dashboard: birthday-field stat, channels-on, cache duration, config snapshot, quick actions. |
| `includes/admin/views/settings-general.php` | Default birthday field + cache duration. |
| `includes/admin/views/settings-email.php` | Birthday emails + send time + admin summary. |
| `includes/admin/views/settings-activity.php` | Activity feed posts + message. |
| `includes/admin/views/settings-notifications.php` | BP notifications + friends-only + text. |
| `includes/admin/views/settings-display.php` | Confetti + zodiac. |
| `assets/css/admin.css` | Token-driven card CSS (`--bbd-admin-*`), ported from contact-me with prefix swap + birthdays-specific utilities. |
| `assets/js/admin.js` | `bbdToast()` + `bbdConfirm()` helpers + dependent-field toggles (email/admin-email/activity/notification). |

## Files MODIFIED (3)

| File | Change |
|---|---|
| `buddypress-birthdays.php` | Version 2.4.1 → **2.5.0**; added `BIRTHDAY_WIDGET_VERSION` constant; require + register the new `BP_Birthdays_Admin_Panel` on `plugins_loaded`; kept the legacy class require (sanitizer/getter only). |
| `admin/class-bp-birthdays-admin.php` | Re-scoped to a **legacy service class**: constructor is now a no-op (UI hooks removed), and `add_admin_menu`, `register_settings`, `enqueue_admin_assets`, `render_settings_page`, all per-tab renderers, `get_date_fields`, `get_bp_emails_url` were **deleted as dead code** (Playbook 3.1). Retained: `$defaults`, `get_instance()`, `sanitize_settings()`, `get_settings()`. Sanitizer hardened with an explicit `bp_birthdays_tab_rendered_keys` sentinel (Playbook 7.1). Class docblock rewritten to reflect post-migration scope. ~420 dead lines removed. |
| `assets/inc/buddypress-birthdays-widget.php` | **Cache TTL bug fix** (see below). |

---

## Option-wiring preservation proof (old → new)

- **Option key:** `bp_birthdays_settings` — unchanged.
- **Option group:** `bp_birthdays_settings_group` — unchanged.
- **Sanitizer:** `BP_Birthdays_Admin::sanitize_settings` — unchanged callback
  (the new panel's `register_setting` delegates to it via
  `BP_Birthdays_Admin::get_instance()->sanitize_settings()`).
- **All 13 subkeys** rendered in exactly one new view file AND coerced by the
  retained legacy sanitizer (verified by grep — each subkey: `view-files=1`,
  legacy sanitizer refs 3–4):
  `default_field_id, cache_duration, email_enabled, email_send_time,
  admin_email_enabled, admin_email, activity_enabled, activity_message,
  notification_enabled, notification_friends_only, notification_text,
  confetti_enabled, zodiac_enabled`.
- **Multi-tab data-loss guard verified by round-trip** (WP-CLI eval): seeded
  the option with keys owned by general/notifications/display tabs, then
  saved ONLY the email tab. Result: `email_enabled=1`, `admin_email_enabled=0`
  (sentinel cleared the unchecked box), and `zodiac_enabled`,
  `notification_text`, `cache_duration=45` all preserved. No blanking.
- Runtime-only options (`bp_birthdays_emails_installed`,
  `bp_birthdays_last_check_date`, `bp_birthdays_sent_today`) untouched.

Two complementary guards make this safe: (1) the legacy sanitizer merges on
top of `get_option()` and only overwrites keys present in `$input`; (2) each
settings tab posts a hidden `value="0"` companion before each checkbox AND a
`bp_birthdays_tab_rendered_keys[]` sentinel, so an unchecked box is an
explicit "off" while keys from other tabs (absent from `$input`) are kept.

---

## Cache-fix diff (`assets/inc/buddypress-birthdays-widget.php`, ~line 72)

Before — TTL hardcoded, `cache_duration` setting was a ghost control:

```php
$birthdays = $this->bbirthdays_get_array( $instance );
// Cache for 30 minutes using object cache.
wp_cache_set( $cache_key, $birthdays, $cache_group, 30 * MINUTE_IN_SECONDS );
```

After — reads the saved `cache_duration` (minutes), clamps 1..1440, ×60s:

```php
$birthdays = $this->bbirthdays_get_array( $instance );

$cache_minutes = 30;
if ( class_exists( 'BP_Birthdays_Admin' ) ) {
    $saved = (int) BP_Birthdays_Admin::get_settings( 'cache_duration' );
    if ( $saved > 0 ) {
        $cache_minutes = $saved;
    }
}
$cache_minutes = max( 1, min( 1440, $cache_minutes ) );
$cache_ttl     = $cache_minutes * MINUTE_IN_SECONDS;

wp_cache_set( $cache_key, $birthdays, $cache_group, $cache_ttl );
```

The admin field's `min=1 max=1440` is mirrored in the clamp; default 30
preserves prior behaviour when no value is set or the class is unavailable.

---

## Menu re-parenting

- Old: `add_submenu_page( 'bp-settings' | 'options-general.php', …,
  'bp-birthday-settings', … )` — **removed** (the legacy `add_admin_menu`
  method was deleted).
- New: `add_menu_page( 'wbcomplugins', … )` (only if not already present) +
  `add_submenu_page( 'wbcomplugins', …, 'bp-birthday-settings', … )`.
- **Page slug `bp-birthday-settings` preserved** for URL/bookmark continuity.
- Capability `manage_options` (now filterable via
  `bp_birthdays_admin_capability`).

---

## Verification

- **`php -l`** — clean on all 12 changed/new PHP files.
- **WPCS** (wpcs MCP) — `0 new errors, 0 new warnings` on every changed/new
  file. Panel + all 9 view files + legacy class: clean. Pre-existing-only
  findings (NOT in the migration diff): main file line 88
  (`$_GET['activate']` notice — original code) and widget lines 105–442/911/952
  (original alignment/direct-DB/comment findings unrelated to the ~16-line
  cache edit, which itself adds 0 findings).
- **Path constants** — `BIRTHDAY_WIDGET_PLUGIN_PATH/URL` resolve to plugin
  root; panel, shell, css, js all reachable (WP-CLI eval).
- **Render smoke** (WP-CLI eval, plugin NOT activated): hub renders the card
  grid and filters out `wbcom-plugins-page`; settings page renders the shell,
  sidebar (active link), `options.php` form, `settings_fields` group, the
  `email_enabled` field, both sentinel inputs, the save bar, and the
  `wp-header-end` marker. Zero PHP errors.
- **Sanitizer round-trip** — multi-tab save preserves other tabs' keys
  (proof above).

Live in-browser verification (clicking through tabs, saving each tab,
console + debug.log tail, 390px responsive) was NOT performed because the
brief forbids activating the plugin (it is currently inactive). Recommend a
browser pass after activation before release.

---

## Out of scope — logged as TODO (NOT fixed, per brief)

- **BB-2 (MED, scale):** cron `get_todays_birthdays` uses non-sargable
  `DATE_FORMAT(d.value,'%m-%d')` → full `bp_xprofile_data` scan every daily
  run (`includes/class-bp-birthdays-notifications.php:~461`).
- **BB-3 (MED/HIGH, scale):** widget "all members" path `SELECT DISTINCT
  user_id` with no `LIMIT` + per-user xprofile/visibility N+1; trim happens
  after the full fetch (`assets/inc/buddypress-birthdays-widget.php:~424-438`).
- **BB-4 (LOW, scale):** non-friends notification fan-out hard-capped at
  `number => 500` with no admin notice
  (`includes/class-bp-birthdays-notifications.php:~631`).

## Needs human eyes

- Browser/QA pass after activating the plugin (tab nav, per-tab save
  round-trip, console/debug.log clean, 390px). Plugin is inactive on this
  site, so this was deferred per the no-activate constraint.
- Confirm 2.5.0 version bump is intended for this release (README.txt
  `Stable tag` was not in scope here — verify before tagging).
