import re
import json
import time
from pprint import pprint
from datetime import date

import subprocess


def process_it(text):
	events = json.loads(text)['events']
	print 'Found', len(events), 'events in total'
	events = filter(lambda ev: ev['yes_rsvp_count'] >= 100, events)
	print 'Found', len(events), 'events with attendance'
	events = filter(lambda ev: 'rsvp_limit' not in ev or ev['yes_rsvp_count'] < ev['rsvp_limit'], events)
	print 'Found', len(events), 'events and open rsvp limit'

	# pprint(events)

	for ev in events:
		print ev['name'], ev['link'], ev['group']['name']

		if 'rsvp_limit' in ev:
			print 'Limit:', ev['yes_rsvp_count'], '/', ev['rsvp_limit']
		print ''


def wait_on_clipboard():
	while True:
		clip = subprocess.check_output("pbpaste")
		if clip.startswith('{"city":{"id":'):
			process_it(clip)
			return
		time.sleep(0.1)


def main():
	today = date.today()
	end_date_range = today.strftime("%Y-%m-%dT23:59:59")

	print 'https://secure.meetup.com/meetup_api/console/?' \
		'path=/find/upcoming_events&' \
		'end_date_range=%s&radius=5&page=20000' % end_date_range

	wait_on_clipboard()

if __name__ == '__main__':
	main()
