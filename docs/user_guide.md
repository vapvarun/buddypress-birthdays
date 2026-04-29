# BuddyPress Birthdays - User Guide

## Overview

The BuddyPress Birthdays plugin displays upcoming birthdays of your community members in a beautiful, responsive format. Perfect for keeping your community engaged and celebrating special moments together! Features automatic birthday greetings, email notifications, activity posts, and more.

## Features

### Core Display Features
- **Smart Birthday Display**: Show birthdays for all members, friends, or followers
- **Flexible Time Ranges**: Weekly, monthly, or unlimited view options
- **Today's Birthday Highlighting**: Special styling for today's celebrations
- **Age Display**: Optional "Turning X" age format
- **Direct Messaging**: Send birthday wishes via private messages
- **Mobile Responsive**: Works perfectly on all devices
- **Theme Compatible**: Inherits your theme's colors and fonts

### Advanced Features
- **Automatic Email Greetings**: Send birthday emails at specified time
- **Activity Feed Posts**: Automatic birthday announcements in activity stream
- **BuddyPress Notifications**: In-app birthday notifications
- **Confetti Animation**: Celebratory confetti effect for today's birthdays
- **Zodiac Signs**: Display zodiac symbols based on birth dates
- **Performance Optimized**: Smart caching and memory management

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

### 2. Plugin Settings
1. Go to **Settings > BuddyPress Birthdays**
2. Configure global settings (see Plugin Settings section below)
3. Set default birthday field and other preferences

### 3. Widget Configuration
1. Go to **Appearance > Widgets**
2. Find **"(BuddyPress) Birthdays"** widget
3. Drag it to your desired sidebar
4. Configure the settings (see Widget Settings section below)

## Plugin Settings

Access plugin settings at **Settings > BuddyPress Birthdays**

### General Settings

**Default Birthday Field**
- Select the default xProfile field for birthdays
- Widgets can override this setting
- Only shows Date/Birth Date field types

**Cache Duration**
- Set cache duration in minutes (1-1440)
- Lower values mean more database queries but fresher data
- Recommended: 60-120 minutes for most sites

### Email Notifications

**Enable Birthday Emails**
- Send automatic birthday greeting emails to members
- Requires BuddyPress Emails component

**Customize Email Content**
- Email content is managed in **BuddyPress Emails**
- Look for "Birthday Greeting" email template
- Available tokens: `{{{recipient.name}}}`, `{{{birthday.age}}}`, `{{{site.name}}}`

**Send Time**
- Set specific time to send birthday emails
- Default: 09:00 AM
- Format: 24-hour time (HH:MM)

### Activity Feed Posts

**Enable Activity Posts**
- Automatically post to activity feed on member birthdays
- Requires BuddyPress Activity component

**Activity Message**
- Customize the birthday message posted to activity feed
- Available placeholders: `{name}`, `{age}`, `{profile_url}`
- Default: "🎂 Happy {age}th birthday {name}! 🎉"

### BuddyPress Notifications

**Enable Notifications**
- Send BuddyPress notifications about member birthdays
- Requires BuddyPress Notifications component

**Notify Friends Only**
- If checked: Only friends of the birthday person receive notifications
- If unchecked: All members receive notifications

**Notification Text**
- Customize the notification message text
- Default: "🎂 {name} is celebrating their birthday today!"

### Display Settings

**Confetti Animation**
- Show confetti animation for today's birthdays
- Adds celebratory effect when viewing today's birthdays
- Works on widget and shortcode displays

**Zodiac Sign**
- Display zodiac sign next to birthday
- Shows zodiac symbols (♈ ♉ ♊ ♋ ♌ ♍ ♎ ♏ ♐ ♑ ♒ ♓)
- Calculated automatically from birth date

## Widget Settings

### Basic Settings

**Title**
- Default: "Upcoming Birthdays"
- Customize to match your community style

**Number of birthdays to show**
- Default: 5
- Range: 1-20 (recommended)
- Controls how many birthdays display in the widget

### Display Options

**Show the age of the person**
- ✅ Enabled: Shows "Turning 25"
- ❌ Disabled: Age hidden for privacy

**Birthday range limit**
- **No Limit**: Shows birthdays for next 365 days
- **Next 7 Days**: Shows only this week's birthdays
- **Next 30 Days**: Shows only this month's birthdays

**Show Birthdays of**
- **All Members**: Everyone on the site (available to all users)
- **Friends**: Only BuddyPress friends (requires Friends component, user must be logged in)
- **Followings**: Only followed users (requires Follow plugin, user must be logged in)

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
- Can override the plugin's default field setting

**Enable option to wish them**
- ✅ Enabled: Shows email icon to send wishes
- ❌ Disabled: Display only (no messaging)

**Select Emoji**
- **None**: No emoji decoration
- **🎂 Cake**: Birthday cake emoji
- **🎈 Balloon**: Balloon emoji  
- **🎉 Party**: Party emoji

**Birthdays Per Page**
- Number of birthdays per page for pagination
- Default: 10
- Useful when displaying many birthdays

## Using the Widget

### What Users See

**Today's Birthdays**
```
🎂 Upcoming Birthdays
├── John Smith - Today! 🎉 ♈
└── Jane Doe - Today! 🎉 ♊
```

**Upcoming Birthdays**  
```
🎂 Upcoming Birthdays
├── Alice Brown (Turning 25) - Jan 15 🎈 ♌
├── Bob Johnson (Turning 30) - Jan 20 🎈 ♍
└── Carol Wilson (Turning 28) - Feb 5 🎈 ♎
```

### Sending Birthday Wishes

1. Click the **📧 email icon** next to any birthday
2. Redirects to BuddyPress compose message
3. Recipient is automatically filled
4. Type your birthday message
5. Send!

### Special Effects

**Confetti Animation**
- Automatically triggers for today's birthdays
- Colorful confetti falls on the widget
- Works on both widget and shortcode displays

**Zodiac Signs**
- Shows zodiac symbol next to each birthday
- Based on actual birth date calculation
- Adds astrological touch to birthday display

## Shortcode Usage

Display birthdays anywhere with the shortcode:

```markdown
[bp_birthdays]
```

### Shortcode Attributes

```markdown
[bp_birthdays 
    title="Team Birthdays"
    limit="3" 
    show_age="no"
    date_format="M j"
    range_limit="weekly"
    show_birthdays_of="friends"
    emoji="cake"
    confetti="yes"
    zodiac="yes"
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
- `display_name_type`: user_name/nickname/first_name
- `emoji`: none/cake/balloon/party
- `field_name`: Birthday field ID
- `birthdays_per_page`: Number per page for pagination
- `confetti`: yes/no (overrides global setting)
- `zodiac`: yes/no (overrides global setting)

## Automatic Features

### Birthday Emails
- **Automatic sending**: Emails sent at configured time on user's birthday
- **Customizable content**: Edit email template in BuddyPress Emails
- **Personal tokens**: Use `{{{recipient.name}}}`, `{{{birthday.age}}}`, `{{{site.name}}}`
- **Reliable delivery**: Uses WordPress cron system

### Activity Feed Posts
- **Automatic posts**: Birthday announcements posted to activity stream
- **Customizable message**: Set your own birthday message format
- **Profile links**: Links directly to birthday person's profile
- **Community engagement**: Encourages others to join celebrations

### BuddyPress Notifications
- **In-app alerts**: Users receive birthday notifications
- **Friend filtering**: Option to notify only friends
- **Custom text**: Customize notification message
- **Real-time updates**: Instant notification delivery

## Troubleshooting

### No Birthdays Showing

**Check Profile Fields**
- Ensure birthday field exists in **Users > Profile Fields**
- Field type must be "Date Selector" or "Birth Date"
- Field must have data from users

**Check Plugin Settings**
- Verify correct field selected in plugin settings
- Check cache duration (try reducing to 1 minute for testing)
- Ensure visibility settings allow display

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
- Increase cache duration for better performance
- Consider weekly/monthly range limits

**Memory Usage**
- Plugin is optimized for performance with smart caching
- Monitor cache duration settings
- Use appropriate range limits for large sites

### Email/Notification Issues

**Emails Not Sending**
- Check WordPress cron system is working
- Verify BuddyPress Emails component is active
- Check email settings in **Settings > BuddyPress Birthdays**
- Review email queue in **BuddyPress > Emails**

**Activity Posts Not Appearing**
- Ensure BuddyPress Activity component is active
- Check activity stream permissions
- Verify activity message format is valid

**Notifications Not Working**
- Ensure BuddyPress Notifications component is active
- Check user notification preferences
- Verify notification text is set correctly

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
- Configure appropriate notification settings

## Best Practices

### Community Engagement
- Enable birthday wishes messaging for better engagement
- Use friendly, welcoming title
- Consider monthly range for active communities
- Enable age display for closer communities
- Use confetti and zodiac features for fun interaction

### Performance Optimization
- Use friends/followers filter for large sites
- Set reasonable display limits (5-10)
- Configure appropriate cache duration (60-120 minutes)
- Enable caching plugins
- Monitor server resources

### Design Integration
- Widget inherits theme styling automatically
- Customize title to match your brand
- Choose emoji that fits community tone
- Test on mobile devices
- Consider color scheme compatibility

### Content Strategy
- Customize email templates with your brand voice
- Personalize activity messages
- Use appropriate notification text
- Consider cultural differences in birthday celebrations
- Test all automatic features regularly

## Advanced Configuration

### Multisite Considerations
- Plugin works on WordPress multisite
- Settings are per-site (not network-wide)
- Each site needs separate birthday field configuration
- Consider network-wide birthday policies

### Integration with Other Plugins
- **BuddyPress Friends**: Works seamlessly with friends filtering
- **BuddyPress Follow**: Supports follower-based filtering
- **BuddyBoss Platform**: Full compatibility with BuddyBoss
- **Caching Plugins**: Compatible with WP Rocket, W3 Total Cache, etc.

### Custom Development
- Plugin includes hooks for custom development
- Filter available for custom date formats
- Actions available for custom birthday events
- Template system for custom display layouts

## Support

### Common Solutions
1. **Clear Cache**: Save widget settings to refresh cache
2. **Check Requirements**: BuddyPress must be active with xProfile component
3. **Verify Data**: Users need birthday data in profile fields
4. **Test Settings**: Try "All Members" + "No Limit" for debugging
5. **Check Components**: Ensure required BP components are active

### Getting Help
- Check plugin documentation in `/docs/` folder
- Review BuddyPress compatibility requirements
- Test with default WordPress theme
- Check WordPress debug log for errors
- Contact plugin support through Wbcom Designs

### Version Information
- **Current Version**: 2.4.1
- **WordPress Requirements**: 5.0+
- **PHP Requirements**: 7.4+
- **BuddyPress Requirements**: Latest stable version
- **Tested Up To**: WordPress 6.9

---

**Celebrate together, stay together!** 🎂

*For advanced technical documentation, see the developer guide in `/docs/developer_guide.md`*