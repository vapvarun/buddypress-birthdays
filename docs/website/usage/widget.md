# The Birthday Widget

The widget is named "(BuddyPress) Birthdays". Add it from Appearance > Widgets (classic themes) or through the Legacy Widget block (block themes). The same options are available through the `[bp_birthdays]` shortcode.

If no date profile field exists yet, the widget form shows a setup notice with a link to Users > Profile Fields instead of the birthday field selector.

## Widget fields

| Field | What it does | Default |
|---|---|---|
| Title | Heading shown above the list. | Upcoming Birthdays |
| Show the age of the person | Shows "Turning X" next to each member. | On |
| Enable option to wish them | Shows the send-wishes button (needs the Messages component and a logged-in viewer). | On |
| Date Format | PHP date format for the displayed date, for example `F d` or `M j`. | F d |
| Birthday range limit | No Limit, Next 7 Days, or Next 30 Days. | No Limit |
| Show Birthdays of | All Members, Friends, or Followings. Friends and Followings only appear when their component or plugin is active. | All Members |
| Display Name Type | User name, Nick name, or First Name. | User name |
| Birthday Field | Which xProfile date field to read. Lists only Date Selector and Birthdate fields. | First available date field |
| Number of birthdays to show | How many birthdays to display in total. | 5 |
| Birthdays per page | How many birthdays per page in the paginated list. | 10 |
| Select Emoji | None, cake, balloon, or party. | Balloon |

## Display modes

- All Members: everyone on the site with a birthday set. Available to logged-out visitors for public birthday fields.
- Friends: the viewer's BuddyPress friends. Needs the Friends component and a logged-in viewer.
- Followings: the people the viewer follows. Needs the BP Follow plugin and a logged-in viewer.

For the Friends and Followings modes, a logged-out visitor sees nothing because the plugin cannot determine whose connections to show.

## Ranges

- No Limit: upcoming birthdays within the next 365 days.
- Next 7 Days: a rolling seven-day window from today.
- Next 30 Days: a rolling thirty-day window from today, not a calendar month.

## Pagination

When more birthdays match than fit on one page, previous and next controls appear below the list. Set "Birthdays per page" to control the page size. Pages are numbered and the current page is marked. The list is bounded by the candidate cap described in the developer guide, so very deep pages are limited by that cap rather than by total member count.

## Caching

The rendered list is cached in the WordPress object cache (group `bp_birthdays`) for the configured cache duration (default 30 minutes). Friends and Followings lists are cached per viewer. Saving the widget, saving a member's profile date field, and friendship or follow changes all clear the cache automatically, as does a daily cron flush.
