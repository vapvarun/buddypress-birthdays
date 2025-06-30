# BuddyPress Birthday Widget - User Guide

## Overview

The BuddyPress Birthday Widget displays upcoming birthdays of your community members in a beautiful, responsive format. Perfect for keeping your community engaged and celebrating special moments together!

## Features

- **Smart Birthday Display**: Show birthdays for all members, friends, or followers
- **Flexible Time Ranges**: Weekly, monthly, or unlimited view
- **Today's Birthday Highlighting**: Special styling for today's celebrations
- **Age Display**: Optional "Turning X" age format
- **Direct Messaging**: Send birthday wishes via private messages
- **Mobile Responsive**: Works perfectly on all devices
- **Theme Compatible**: Inherits your theme's colors and fonts

## Installation

### Via WordPress Admin
1. Go to **Plugins > Add New**
2. Search for "BuddyPress Birthday Widget"
3. Click **Install Now** and then **Activate**

### Manual Installation
1. Upload the plugin files to `/wp-content/plugins/buddypress-birthdays/`
2. Activate the plugin through the **Plugins** menu in WordPress

## Setup Requirements

### 1. BuddyPress Profile Fields
Before using the widget, you need a birthday field in BuddyPress:

1. Go to **Users > Profile Fields**
2. Click **Add New Field**
3. Choose field type: **Date Selector** or **Birth Date**
4. Set field name (e.g., "Birthday", "Date of Birth")
5. Make the field **Required** (recommended)
6. Set appropriate visibility level

### 2. Widget Configuration
1. Go to **Appearance > Widgets**
2. Find **"(BuddyPress) Birthdays"** widget
3. Drag it to your desired sidebar
4. Configure the settings (see below)

## Widget Settings

### Basic Settings

**Title**
- Default: "Upcoming Birthdays"
- Customize to match your community style

**Number of birthdays to show**
- Default: 5
- Range: 1-20 (recommended)

### Display Options

**Show the age of the person**
- ✅ Enabled: Shows "Turning 25"
- ❌ Disabled: Age hidden for privacy

**Birthday range limit**
- **No Limit**: Shows birthdays for next 365 days
- **Next 7 Days**: Shows only this week's birthdays
- **Next 30 Days**: Shows only this month's birthdays

**Show Birthdays of**
- **All Members**: Everyone on the site
- **Friends**: Only BuddyPress friends (if Friends component active)
- **Followings**: Only followed users (if Follow plugin active)

### Advanced Settings

**Display Name Type**
- **User name**: Shows username (john_doe)
- **Nick name**: Shows nickname from profile
- **First Name**: Shows first name only

**Date Format**
- **F d**: January 15
- **M j**: Jan 15
- **j F**: 15 January
- **j M**: 15 Jan
- Custom: Use PHP date format codes

**Field's name**
- Select the birthday field created in Profile Fields
- Only shows Date/Birth Date field types

**Enable option to wish them**
- ✅ Enabled: Shows email icon to send wishes
- ❌ Disabled: Display only (no messaging)

**Select Emoji**
- **None**: No emoji decoration
- **🎂 Cake**: Birthday cake emoji
- **🎈 Balloon**: Balloon emoji  
- **🎉 Party**: Party emoji

## Using the Widget

### What Users See

**Today's Birthdays**
```
🎂 Upcoming Birthdays
├── John Smith - Today! 🎉
└── Jane Doe - Today! 🎉
```

**Upcoming Birthdays**  
```
🎂 Upcoming Birthdays
├── Alice Brown (Turning 25) - Jan 15 🎈
├── Bob Johnson (Turning 30) - Jan 20 🎈
└── Carol Wilson (Turning 28) - Feb 5 🎈
```

### Sending Birthday Wishes

1. Click the **📧 email icon** next to any birthday
2. Redirects to BuddyPress compose message
3. Recipient is automatically filled
4. Type your birthday message
5. Send!

## Shortcode Usage

Display birthdays anywhere with the shortcode:

```
[bp_birthdays]
```

### Shortcode Attributes

```
[bp_birthdays 
    title="Team Birthdays"
    limit="3" 
    show_age="no"
    date_format="M j"
    range_limit="weekly"
    show_birthdays_of="friends"
    emoji="cake"
]
```

**Available Attributes:**
- `title`: Widget title
- `limit`: Number to display (1-20)
- `show_age`: yes/no
- `show_message_button`: yes/no  
- `date_format`: PHP date format
- `range_limit`: weekly/monthly/no_limit
- `show_birthdays_of`: all/friends/followers
- `display_name_type`: user_name/nick_name/first_name
- `emoji`: none/cake/balloon/party
- `field_name`: Birthday field ID

## Troubleshooting

### No Birthdays Showing

**Check Profile Fields**
- Ensure birthday field exists in **Users > Profile Fields**
- Field type must be "Date Selector" or "Birth Date"
- Field must have data from users

**Check Widget Settings**
- Verify correct field selected in widget
- Check birthday range limit (try "No Limit")
- Ensure visibility settings allow display

**Check User Data**
- Users must fill their birthday field
- Birthday field visibility must not be "Only Me"
- Check if you're filtering by friends/followers

### Wrong Dates Showing

**Timezone Issues**
- Check **Settings > General > Timezone**
- Ensure WordPress timezone matches your location
- Clear widget cache (save widget settings)

**Date Format Issues**
- Try different date format in widget settings
- Use standard formats like "F d" or "M j"

### Performance Issues

**Large Communities**
- Use "Friends" or "Followers" filter instead of "All Members"
- Reduce number of birthdays displayed
- Consider weekly/monthly range limits

## Privacy & Visibility

### User Privacy Control
Users can control birthday visibility via:
1. **Profile > Edit > Privacy Settings**
2. Set birthday field visibility:
   - **Public**: Everyone can see
   - **Logged In Users**: Members only
   - **Friends**: Friends only  
   - **Only Me**: Hidden from widget

### Admin Considerations
- Respect user privacy choices
- Consider community guidelines
- Test with different user roles
- Monitor performance on large sites

## Best Practices

### Community Engagement
- Enable birthday wishes messaging
- Use friendly, welcoming title
- Consider monthly range for active communities
- Enable age display for closer communities

### Performance Optimization
- Use friends/followers filter for large sites
- Set reasonable display limits (5-10)
- Enable caching plugins
- Monitor server resources

### Design Integration
- Widget inherits theme styling automatically
- Customize title to match your brand
- Choose emoji that fits community tone
- Test on mobile devices

## Support

### Common Solutions
1. **Clear Cache**: Save widget settings to refresh
2. **Check Requirements**: BuddyPress must be active
3. **Verify Data**: Users need birthday data
4. **Test Settings**: Try "All Members" + "No Limit"

### Getting Help
- Check plugin documentation
- Review BuddyPress compatibility
- Test with default theme
- Contact plugin support

---

**Celebrate together, stay together!** 🎂