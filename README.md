# meetup_find_events

Find interesting meetup events in your area, get notified when events get popular.

# Initialization

After cloning, run this script with

    php meetup_find_events.php

and it will ask you to configure user ID and access key.

When you see a list of events, the setup is complete.

# Usage from command line

You can just run it with `php meetup_find_events.php` in command line, pass in `--help` to change settings.

Read the output from TOP to bottom, it shows which events it skipped (`--verbose`), it lists most interesting ones on the TOP of output.

I can Command+click the URL in terminal to open any event.

# Blacklist configuration

You will have to edit the source code (sorry, I was too lazy to make a proper config file).

Modify global arrays `$config_group_name_blacklist`, `$config_spammers`, `$config_event_name_blacklist`

I recommend running this script from command line in your zipcode for `--days 90`, until you are happy that you filtered out spammers and groups that you don't care about.

# Use from cron (for Mac OS X)

This will send you notifications about meetup events every hour until you either RSVP "yes", or you RSVP "no", or you somehow blacklist particular group or event.

First install terminal-notifier

    brew install terminal-notifier 

Add a line to cron:

    1 * * * * /path/to/meetup_find_events.php --rsvp_limit 65 --days 30 --notification

Also add `--zipcode`, `--miles` flags, etc.
