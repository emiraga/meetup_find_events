import re
import json
import time
from pprint import pprint
from datetime import date
from dateutil.relativedelta import relativedelta

import subprocess
import webbrowser


def process_it(text):
	events = json.loads(text)['events']
	print('Found', len(events), 'events in total')
	events = list(filter(lambda ev: ev['yes_rsvp_count'] >= 30, events))
	print('Found', len(events), 'events with attendance')
	events = list(filter(lambda ev: 'rsvp_limit' not in ev or ev['yes_rsvp_count'] < ev['rsvp_limit'], events))
	print('Found', len(events), 'events and open rsvp limit')
	events = list(filter(lambda ev: 'is_online_event' not in ev or not ev['is_online_event'], events))
	print('Found', len(events), 'events and open rsvp limit (in-person)')

	for ev in events:
		print(ev['name'], ev['link'], ev['group']['name'])

		if 'rsvp_limit' in ev:
			print('Limit:', ev['yes_rsvp_count'], '/', ev['rsvp_limit'])
		print('')
		webbrowser.open_new_tab(ev['link'])


def wait_on_clipboard(then):
	while True:
		clip = subprocess.check_output("pbpaste").decode('utf-8')
		if clip.startswith('{"city":{"id":'):
			then(clip)
			return
		time.sleep(0.1)


def main():
	today = date.today()
	end_date_range = (today + relativedelta(weeks=+3)).strftime("%Y-%m-%dT23:59:59")

	meetup_url = 'https://secure.meetup.com/meetup_api/console/?' \
		'path=/find/upcoming_events&autostart=1&' \
		'omit=description&' \
		'end_date_range=%s&radius=5&page=20000' % end_date_range

	webbrowser.open_new_tab(meetup_url)

	wait_on_clipboard(then=process_it)


if __name__ == '__main__':
	main()
