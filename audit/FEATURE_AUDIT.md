# Feature Audit — Wbcom Designs Birthday Widget for BuddyPress

Version 2.4.1 · branch 2.5.0 · derived from `audit/manifest.json`. Widget-centric BuddyPress addon.

## 1. Frontend surfaces
| Surface | How rendered | Roles | Gating |
|---|---|---|---|
| Birthday widget | `Widget_Buddypress_Birthdays` (WP_Widget), registered on `widgets_init` | All (logged-out limited to "All Members" filter) | Requires BuddyPress active + a configured datebox/birthdate xProfile field |
| `[bp_birthdays]` shortcode | `bb_birthdays_shortcode` wraps the widget render | Same as widget | Auto-loads assets; auto-detects first date field if `field_name` omitted |

No frontend tabs/templates; output is generated inline in the widget class (no `templates/` dir).

## 2. AJAX handlers
| Action | File:line | Nonce | Capability | JS caller |
|---|---|---|---|---|
| `bb_birthdays_action` (priv + nopriv) | core-init.php:469-470 | `bb_birthdays_nonce` | `refresh_widget`: logged-in + `read`; `mark_wished`: logged-in | assets/js/bb-core.js (localized `bbBirthdays.ajaxurl`/`nonce`) |

Sub-actions: `refresh_widget` (clear object cache), `mark_wished` (record wish in `bb_birthday_wished_users` user meta). Default branch returns `wp_send_json_error`.

## 3. REST endpoints
_None._

## 4. Admin page & settings
| Title | Slug | Parent | Cap | Hook |
|---|---|---|---|---|
| Birthday Settings | `bp-birthday-settings` | `bp-settings` (BuddyPress) → fallback `options-general.php` | `manage_options` | `admin_menu` @20 |

Tabbed WordPress Settings API page (`BP_Birthdays_Admin`), one stored option `bp_birthdays_settings` (group `bp_birthdays_settings_group`, sanitize `sanitize_settings`). Tabs: General, Email Notifications, Activity Feed, Notifications, Display. See manifest `settings[]` for the full key list (general/email/activity/notifications/display sub-keys + 3 internal bookkeeping options).

## 5. Shortcodes
`[bp_birthdays]` — attrs: title, limit, show_age, show_message_button, date_format, range_limit, show_birthdays_of, display_name_type, emoji, field_name, birthdays_per_page (core-init.php:264-329).

## 6. Content types / taxonomies / DB tables
_None owned._ Reads BuddyPress-owned tables read-only: `bp_xprofile_data`, `bp_xprofile_fields`, `bp_xprofile_meta`, plus core `users`/`usermeta`. Creates BP email posts (`bp-email` CPT, owned by BuddyPress) for two email types: `birthday-greeting`, `birthday-admin-summary`.

## 7. JS modules
- `bb-core` (assets/js/bb-core.js, dep jquery, localized `bbBirthdays`) — confetti/tooltip/refresh behavior.
- `bp-birthdays-admin` (admin/js/admin-settings.js) — settings page tab/UX helpers.

## 8. CSS modules
- `bb-core` (assets/css/bb-core.css) — widget layout/animations. Key classes: `.bp-birthday-users-list`, `.bp-birthday-item`, `.today-birthday`, `.bp-birthday-avatar/-content/-name/-details`, `.bp-birthday-age/-date/-emoji`, `.bp-birthday-zodiac`, `.bp-send-wishes`, `.bp-birthday-pagination`, `.bp-birthday-page-btn/-num/-current`.
- `bp-birthdays-admin` (admin/css/admin-settings.css).

## 9. Email templates
Registered via BP email schema (`bp_email_get_schema` / `bp_email_get_type_schema`):
| Type | Trigger | Tokens |
|---|---|---|
| `birthday-greeting` | Daily cron when `email_enabled` | {{{recipient.name}}}, {{{recipient.url}}}, {{{birthday.age}}}, {{{site.name}}} |
| `birthday-admin-summary` | Daily cron when `admin_email_enabled` | {{birthdays.count}}, {{{birthdays.list}}}, {{{birthdays.list_plain}}}, {{{site.name}}} |

`wp_mail` HTML fallback exists when `bp_send_email` is unavailable.

## 10. Cron jobs
| Hook | Interval | Handler | Purpose |
|---|---|---|---|
| `bp_birthdays_daily_check` | daily (at `email_send_time`) | `BP_Birthdays_Notifications::process_daily_birthdays` | Today's birthdays → emails/activity/notifications/admin summary |
| `bb_cleanup_old_wishes` | daily | `bb_cleanup_old_wishes` + `bb_daily_cache_clear` | Prune wished-users meta >7d + flush birthday object cache |

## 11. DB tables
_None created._ See §6 for external tables read.

## 12. Integrations
BuddyPress (required; self-deactivates if absent), BuddyBoss Platform (email URL + follow detection), BP-Follow / BuddyBoss follow (followers filter), bbPress n/a, Youzify (asset-load heuristic), WordPress Friends/Messages/Activity/Notifications components (optional features).

## Capabilities
| Cap | Roles | Enforced at | Meta |
|---|---|---|---|
| manage_options | administrator | admin menu cap; helpers `adminsonly` visibility | no |
| read | all logged-in | AJAX `refresh_widget` branch | no |
