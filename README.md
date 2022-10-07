# meetup_find_events

Find interesting meetup events in your area, get notified when events get popular.

# PHP

Deprecated because meetup changed its policies on APIs.

# python

Works in combination with the browser.

Needs this piece of javascript in the browser

    JSONFormatter.objectToHTML = (response_object) => {
      navigator.clipboard.writeText(JSON.stringify(response_object));
      return 'clipboard';
    }

In order to copy the contents of response back to python script.


# Deprecated: Use from cron (for Mac OS X)

This will send you notifications about meetup events every hour until you either RSVP "yes", or you RSVP "no", or you somehow blacklist particular group or event.

First install terminal-notifier

    brew install terminal-notifier 

To edit your crontab file just do

    crontab -e

Add a line to cron:

    1 * * * * /path/to/meetup_find_events.php --rsvp_limit 65 --days 30 --notification

Also add `--zipcode`, `--miles` flags, etc.
