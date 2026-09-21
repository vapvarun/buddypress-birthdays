# Admin Settings

Settings live at WB Plugins > Birthdays. The page uses a card-panel layout with an overview dashboard and grouped settings tabs. All settings are stored in a single option, `bp_birthdays_settings`. The capability required to see the page is `manage_options`, filterable with `bp_birthdays_admin_capability`.

## Tabs

The admin page has seven tabs: Overview, General, Email, Activity, Notifications, Display, and Discover.

### Overview

A read-only dashboard confirming the plugin found a birthday profile field and showing the current configuration. Start here after activation.

### General

| Setting | What it does | Default |
|---|---|---|
| Default Birthday Field | The xProfile date field used by the cron notifications, the shortcode fallback, and as the widget default. Lists only Date Selector and Birthdate fields. | Empty |
| Cache Duration | Widget object-cache lifetime in minutes, clamped to 1 to 1440. | 30 |

### Email

| Setting | What it does | Default |
|---|---|---|
| Enable Birthday Emails | Send a greeting email to each member on their birthday. | Off |
| Send Time | Time of day the daily birthday check runs, in 24-hour HH:MM. | 09:00 |
| Enable Admin Summary Email | Send the site owner a daily summary of the day's birthdays. | Off |
| Admin Email | Recipient for the summary. Falls back to the site admin email when empty. | Empty |

### Activity

| Setting | What it does | Default |
|---|---|---|
| Enable Activity Posts | Auto-post to the BuddyPress activity feed on member birthdays. | Off |
| Activity Message | The template posted to the feed. Placeholders: `{name}`, `{age}`, `{profile_url}`. | Today is {name}'s birthday! Send your wishes! |

### Notifications

| Setting | What it does | Default |
|---|---|---|
| Enable Notifications | Send BuddyPress in-app notifications about birthdays. | Off |
| Notify Friends Only | Restrict notifications to the birthday member's friends. | Off |
| Notification Text | The notification template. Placeholder: `{name}`. | It's {name}'s birthday today! |

### Display

| Setting | What it does | Default |
|---|---|---|
| Confetti Animation | Show a confetti animation for today's birthdays on the widget and shortcode. | Off |
| Zodiac Sign | Show the member's zodiac symbol next to their birthday. | Off |

### Discover

Links to other free Wbcom Designs community plugins. This tab is informational only.

## Notes

- Every notification and email option is off until you enable it, so activating the plugin never sends anything on its own.
- The Confetti and Zodiac options are global display settings. They are not shortcode attributes; the shortcode reads the same saved options.
