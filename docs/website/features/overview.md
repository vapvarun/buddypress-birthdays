# Features

Everything below is available in the free plugin.

## Birthday display

- Show birthdays for all members, friends, or followings (the people a member follows).
- Friends mode needs the BuddyPress Friends component. Followings mode needs the third-party BP Follow plugin; without it the option does not appear.
- Time ranges: No Limit, Next 7 Days, or Next 30 Days. The ranges are rolling windows from today, not calendar weeks or months.
- Today's birthdays get special highlighting.
- Optional age display, shown as "Turning X".
- Pagination: set how many birthdays per page and members move through the list with previous and next controls.

## Privacy and GDPR

- Per-member opt-out: every member can hide their birthday from the widget, shortcode, activity feed, notifications and emails, from their BuddyPress Settings > General screen.
- The opt-out is enforced on every surface and can be driven programmatically with the `bb_birthday_user_opted_out` filter.
- BuddyPress profile field visibility is respected, so a birthday set to "Only Me" or restricted to friends stays private.

## Notifications and emails

All of the following are off by default and enabled per option:

- Birthday greeting emails sent to members on their birthday.
- Activity feed auto-posts announcing a member's birthday.
- BuddyPress in-app notifications, optionally limited to the member's friends.
- A daily summary email of the day's birthdays sent to the site owner.
- A configurable daily send time and editable templates.

## Display options

- Optional emoji next to each name (cake, balloon, or party).
- Zodiac sign display option.
- Confetti animation option for celebrations.
- Multiple name display options: username, nickname, or first name.
- Customizable date format using PHP date format codes.
- Send-wishes button that opens a private message to the birthday member (needs the Messages component and a logged-in viewer).

## Performance

- Backed by the WordPress object cache, with a configurable cache duration (default 30 minutes).
- Assets load only where the widget or shortcode renders.
- Every widget mode fetches a bounded, batch-primed candidate pool instead of walking every member, tunable with the `bb_birthdays_widget_candidate_multiplier` and `bb_birthdays_widget_candidate_cap` filters.

## Accessibility

- Honours `prefers-reduced-motion`, so animations stand down for members who ask for less motion.
- ARIA labels on the pagination controls and the send-wishes button.
- Keyboard-reachable controls.

## Not included

The plugin ships no REST API endpoints, no Gutenberg blocks, and no custom database tables. It reads BuddyPress xProfile data only, and never writes to BuddyPress tables outside BuddyPress APIs.
