# Birthday Widget for BuddyPress

Show your members' upcoming birthdays in a widget or shortcode, and greet them on the day.

[![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv3-green.svg)](https://opensource.org/license/gpl-3-0)

## What it does

- Lists upcoming birthdays for all members, friends or followings, with No Limit, next 7 days or next 30 days ranges.
- Highlights today's birthdays, with optional age ("Turning X"), emoji, zodiac sign and confetti.
- Opens a private message in one click so members can send wishes.
- Sends birthday greeting emails, posts to the activity feed and raises BuddyPress notifications on the day. All of these are off until you turn them on.
- Emails the site owner a daily summary of the day's birthdays, if enabled.
- Reads birthdays from a BuddyPress Extended Profile date field and respects its visibility level. Every member can hide their birthday from every surface of the plugin.
- Paginates the list and reads a bounded set of candidates, so large communities stay fast.

## Who it is for

Owners of BuddyPress or BuddyBoss communities who want members to notice and celebrate each other's birthdays without managing it by hand.

## Where to get it

- Store page: [wbcomdesigns.com/downloads/buddypress-birthdays](https://wbcomdesigns.com/downloads/buddypress-birthdays/)
- Free listing: [Birthday Widget for BuddyPress on WordPress.org](https://wordpress.org/plugins/birthday-widget-for-buddypress/)

## Documentation

- [Birthday Widget for BuddyPress documentation](https://docs.wbcomdesigns.com/bp-family/buddypress-birthdays/)
- [Release notes](https://wbcomdesigns.com/release-notes/buddypress-birthdays/)

## Requirements

- WordPress 6.5 or higher
- PHP 8.1 or higher
- BuddyPress, with the Extended Profiles component on and at least one Date Selector or Birthdate profile field
- Works with BuddyBoss Platform. Followings mode needs the BP Follow plugin.

## Installation

1. Upload the `buddypress-birthdays` folder to `/wp-content/plugins/`, or install the ZIP through Plugins > Add New > Upload Plugin.
2. Activate the plugin. A single activation opens its settings page.
3. Add the "(BuddyPress) Birthdays" widget under Appearance > Widgets (or the Legacy Widget block in a block theme), or use the shortcode below.
4. Turn on emails, activity posts and notifications under WB Plugins > Birthdays if you want them.

## Shortcode

```text
[bp_birthdays range_limit="monthly" show_birthdays_of="friends" show_age="yes" limit="10"]
```

| Parameter | Options | Default |
|-----------|---------|---------|
| `title` | Any text | `Upcoming Birthdays` |
| `limit` | Positive number (`birthdays_to_display` is accepted as an alias) | `5` |
| `show_age` | `yes`, `no` | `yes` |
| `show_message_button` | `yes`, `no` | `yes` |
| `date_format` | A PHP date format | `F d` |
| `range_limit` | `no_limit`, `weekly` (next 7 days), `monthly` (next 30 days) | `no_limit` |
| `show_birthdays_of` | `all`, `friends`, `followers` | `all` |
| `display_name_type` | `user_name`, `nick_name`, `first_name` | `user_name` |
| `emoji` | `none`, `cake`, `balloon`, `party` | `balloon` |
| `field_name` | An xProfile date field ID | The Default Birthday Field |
| `birthdays_per_page` | Any number | `10` |

## For developers

- Prefix: functions `bb_*`, classes `BP_Birthdays_*`, options `bp_birthdays_*`. Text domain: `buddypress-birthdays`.
- Hooks: filters only, such as `bp_birthdays_display_timezone`, `bp_birthdays_admin_capability`, `bb_birthday_user_opted_out` and the widget candidate filters. See the [hooks and filters guide](https://docs.wbcomdesigns.com/bp-family/buddypress-birthdays/developer-guide/hooks-and-filters/).
- No REST endpoints, blocks, AJAX actions, WP-CLI commands or custom tables. The plugin only reads BuddyPress profile tables.
- Cached lists live in the `bp_birthdays` object cache group.

## Support

Need help? [Contact Wbcom Designs](https://wbcomdesigns.com/contact/) for support or custom development.

## License

GPLv3 or later. See the [license text](https://opensource.org/license/gpl-3-0).
