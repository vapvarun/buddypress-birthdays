# Installation and Setup

## Before you start

BuddyPress must be active, and you need at least one Extended Profile field of type Date Selector (datebox) or Birthdate for members to enter their birthday.

To create one:

1. Go to Users > Profile Fields.
2. Add a new field with type Date Selector or Birthdate.
3. Name it (for example "Birthday" or "Date of Birth") and save.
4. Set an appropriate visibility level for the field.

Without a date field, every plugin surface renders empty.

## Install the plugin

1. Upload the plugin files to `/wp-content/plugins/buddypress-birthdays/`, or install the ZIP through Plugins > Add New > Upload Plugin.
2. Activate the plugin through the Plugins menu.
3. Go to WB Plugins > Birthdays to configure display, email, activity, notification and display options.

If BuddyPress is not active, the plugin shows an admin notice and deactivates itself.

## Show the birthday list

Pick whichever fits your theme:

- Block themes (no Appearance > Widgets screen): edit a template or page in the Site Editor or block editor, add the Legacy Widget block, then choose "(BuddyPress) Birthdays". Or use the shortcode.
- Classic themes: go to Appearance > Widgets and add the "(BuddyPress) Birthdays" widget to a sidebar or footer.
- Any theme, any page or post: add the `[bp_birthdays]` shortcode. This is the simplest route and works everywhere.

Then set which members to show (all, friends, followings), the range, the birthday field, how many to show, and how many per page.

## Post-installation checklist

1. Visit WB Plugins > Birthdays > Overview to confirm the plugin found your birthday profile field.
2. Configure email greetings, activity posts and notifications under the settings tabs (all are off until you enable them).
3. Ask members to fill in their birthday field, and let them know they can hide it from Settings > General if they prefer.
