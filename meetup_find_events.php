#!/usr/bin/php
<?php
error_reporting(E_ALL);

$config_group_name_blacklist = array(
  'rladies-san-francisco',
  'Customer-Success',
  'Women-in-Infrastructure',
  'Holberton-School',
  'cascadesf',
  'gotostartups',
  'ONA-SF',
  'ODTUGers',
  'WriteSpeakCode-SFBay',
  'motiondesigners',
  'data-driven-women',
  'EveryoneCorg',
  'SF-Cryptocurrency-Devs',
  'NorCalSnowboarders-SF',
  'San-Francisco-Lipstick-Lesbians',
  'RealSFGirlFriends',
  'NorCalSnowboarders-SouthBay',
  'SanFranciscoWomenandDiversityInTech',
  'youngblackbay',
  'a11ybay',
  'Break-Ke-Baad',
  'Women-Who-Code-SF',
  'sflesbianhh',
  'SFBayAreaFurs',
  'sfbayre',
  'kick-ass-group',
  'gay-social-san-francisco',
  'femtalks',
);

$config_spammers = array(
  55768452,   // Caroline - beer pong
  79666472,   // Sally - beer pong
  147023972,  // Michelle - beer pong
  183425920,  // Lifograph
  184377825,  // Danni Shauser - live shark tank
  14527503,   // Jose De Dios - live shark tank
);

$config_event_name_blacklist = array (
  'Drinks with International Travelers (EVERY WEDNESDAY)',
  'Indian Curry, Comedy & BYOB! (EVERY THURSDAY)',
  'Drink, Dine & Solve Crime! (EVERY SUNDAY)',
  'Drink, Dine & Solve Crime (EVERY SUNDAY)',
  'San Francisco Lipstick Lesbians',
  'The Elizabeth Taylor 50-Plus Network for Gay, Bi & Trans Men',
  'PARTY with International Travelers and Locals',
  'Bay Area Women in Machine Learning & Data Science',
  'Pub Crawl w/International Travelers (EVERY WEDNESDAY)',
  'Pub Crawl with International Travelers (EVERY WEDNESDAY)',
  'Indian Dinner, Comedy Show & BYOB! (EVERY THURSDAY)',
  'Drinks with Travelers (EVERY WEDNESDAY)',
  'Pub Crawl with International Backpackers (EVERY WEDNESDAY)',
  'A social group for gay men and their friends',
  'PARTY with International Travelers & Locals',
  'San Francisco Engineering Leadership Community (SFELC)',
  'Indian Curry, Comedy & BYOB (EVERY THURSDAY)',
);

$config_debug_event_url = 'https://www.meetup.com/ReactJS-San-Francisco/events/246453911/';

$config_skip_event_urls = array(
  'https://www.meetup.com/Bay-Area-Hike-On/events/256284677/',
);

// TODO(emir): always display events from groups:
//  'friends-of-europe', https://www.meetup.com/friends-of-europe/events/
//  'sf-photo',          https://www.meetup.com/sf-photo/events/

$shortopts = "r:d:vhn";

$longopts  = array("rsvp_limit:", "days:", "verbose", "notification", "help");
$options = getopt($shortopts, $longopts);

// Values are overriable from command line
$config_verbose = false;
$config_load_days = 4;  // including today, how many days to show events in the future
$config_min_rsvp = 50;  // don't show events that are below this threshold
$config_miles = 15;
$config_zipcode = 94102;  // Tenderloin
$config_notifications = false;

if (array_key_exists('help', $options) || array_key_exists('h', $options)) {
  print("-d --days         How many days to load (including today), default: $config_load_days\n");
  print("-m --miles        default: $config_miles\n");
  print("-z --zipcode      default: $config_zipcode\n");
  print("-r --rsvp_limit   Min require people in the group, default: $config_min_rsvp\n");
  print("-v --verbose      More output\n");
  print("-n --notification On MAC OS X\n");
  print("-h --help         Show this screen\n");
  exit(1);
}

if (array_key_exists('d', $options)) {
  $config_load_days = $options['d'];
}

if (array_key_exists('days', $options)) {
  $config_load_days = $options['days'];
}

if (array_key_exists('m', $options)) {
  $config_miles = $options['m'];
}

if (array_key_exists('miles', $options)) {
  $config_miles = $options['miles'];
}

if (array_key_exists('z', $options)) {
  $config_zipcode = $options['z'];
}

if (array_key_exists('zipcode', $options)) {
  $config_zipcode = $options['zipcode'];
}

if (array_key_exists('r', $options)) {
  $config_min_rsvp = $options['r'];
}

if (array_key_exists('rsvp_limit', $options)) {
  $config_min_rsvp = $options['rsvp_limit'];
}

if (array_key_exists('verbose', $options) || array_key_exists('v', $options)) {
  $config_verbose = true;
}

if (array_key_exists('notification', $options) || array_key_exists('n', $options)) {
  $config_notifications = true;
}

$config_show_limited = true;  // that have some restrictions on them, but maybe you can still attend


$home_dir = posix_getpwuid(posix_getuid())['dir'];
$config_dir = $home_dir."/.meetup_find_events/";
@mkdir($config_dir);

if (!file_exists($config_dir.'user_id')) {
  echo "ATTENTION!\n";
  echo "Go to https://www.meetup.com/account/\n";
  echo "Copy user ID (just a number) from that page\n";
  echo " echo ID > ".$config_dir.'user_id'."\n";
  exit(1);
}

$config_user_id = trim(file_get_contents($config_dir.'user_id'));
if (!$config_user_id) {
  echo "Invalid user id in ".$config_dir.'user_id'."\n";
  exit(1);
}

if (!file_exists($config_dir.'api_key')) {
  echo "ATTENTION!\n";
  echo "Go to https://secure.meetup.com/meetup_api/key/\n";
  echo "Copy user API key from that page\n";
  echo " echo API_KEY > ".$config_dir.'api_key'."\n";
  exit(1);
}

$config_api_key = trim(file_get_contents($config_dir.'api_key'));
if (!$config_api_key) {
  echo "Invalid user id in ".$config_dir.'api_key'."\n";
  exit(1);
}


function verbose_printf(...$args) {
  global $config_verbose;
  if ($config_verbose) {
    printf(...$args);
  }
}

class Meetup {
  const BASE = 'https://api.meetup.com';
  protected $_parameters = array(
    'sign' => 'true',
  );
  public function __construct(array $parameters = array()) {
    $this->_parameters = array_merge($this->_parameters, $parameters);
  }

  public function getEvents(array $parameters = array()) {
    return $this->get('/2/events', $parameters)->results;
  }

  public function getRsvps(array $parameters = array()) {
    return $this->get('/2/rsvps', $parameters)->results;
  }

  public function getOpenEvents(array $parameters = array()) {
    $x = $this->get('/2/open_events', $parameters);
    return $x->results;
  }

  public function getSelfEvents(array $parameters = array()) {
    return $this->get('/self/events', $parameters);
  }

  public function getPhotos(array $parameters = array()) {
    return $this->get('/2/photos', $parameters)->results;
  }

  public function getDiscussionBoards(array $parameters = array()) {
    return $this->get('/:urlname/boards', $parameters);
  }

  public function getDiscussions(array $parameters = array()) {
    return $this->get('/:urlname/boards/:bid/discussions', $parameters);
  }

  public function getMembers(array $parameters = array()) {
    return $this->get('/2/members', $parameters);
  }

  public function getNext($response) {
    if (!isset($response) || !isset($response->meta->next))
    {
      throw new Exception("Invalid response object.");
    }
    return $this->get_url($response->meta->next);
  }

  public function get($path, array $parameters = array()) {
    // var_dump($parameters);

    $parameters = array_merge($this->_parameters, $parameters);

    if (preg_match_all('/:([a-z]+)/', $path, $matches)) {

      foreach ($matches[0] as $i => $match) {

        if (isset($parameters[$matches[1][$i]])) {
          $path = str_replace($match, $parameters[$matches[1][$i]], $path);
          unset($parameters[$matches[1][$i]]);
        } else {
          throw new Exception("Missing parameter '" . $matches[1][$i] . "' for path '" . $path . "'.");
        }
      }
    }
    $url = self::BASE . $path . '?' . http_build_query($parameters);
    return $this->get_url($url);
  }
  protected function get_url($url) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept-Charset: utf-8"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $content = curl_exec($ch);

    if (curl_errno($ch)) {
      $error = curl_error($ch);
      curl_close($ch);

      throw new Exception("Failed retrieving  '" . $url . "' because of ' " . $error . "'.");
    }
    // var_dump($content);
    $response = json_decode($content);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // var_dump($status);
    // var_dump($response);

    curl_close($ch);

    if ($status != 200) {

      if (isset($response->errors[0]->message)) {
        $error = $response->errors[0]->message;
      } else {
        $error = 'Status ' . $status;
      }

      throw new Exception("Failed retrieving  '" . $url . "' because of ' " . $error . "'.");
    }
    if (isset($response) == false) {

      switch (json_last_error()) {
        case JSON_ERROR_NONE:
          $error = 'No errors';
        break;
        case JSON_ERROR_DEPTH:
          $error = 'Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
          $error = ' Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
          $error = 'Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
          $error = 'Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
          $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        default:
          $error = 'Unknown error';
        break;
      }

      throw new Exception("Cannot read response by  '" . $url . "' because of: '" . $error . "'.");
    }

    return $response;
  }
}

class FakeVenue {}

function fixEventResponse($event) {
  if (!property_exists($event, 'event_hosts')) {
    $event->event_hosts = array();
  }

  if (!property_exists($event, 'maybe_rsvp_count')) {
    $event->maybe_rsvp_count = 0;
  }

  if (!property_exists($event, 'headcount')) {
    $event->headcount = 0;
  }

  if (!property_exists($event, 'rsvp_limit')) {
    $event->rsvp_limit = 1000000000;
  }

  if (!property_exists($event, 'event_url') || !$event->event_url) {
    $event->event_url = sprintf('https://www.meetup.com/%s/events/%s/',
                                $event->group->urlname, $event->id);
  }

  if (!property_exists($event, 'venue')) {
    $event->venue = new FakeVenue();
  }

  if (!property_exists($event->venue, 'address_1')) {
    $event->venue->address_1 = '';
  }

  if (!property_exists($event->venue, 'city')) {
    $event->venue->city = '';
  }

  return $event;
}

$meetup_api = new Meetup(array(
  'key' => $config_api_key,
));

date_default_timezone_set('America/Los_Angeles');
if ($config_verbose) {
  echo "Starting\n";
}

$already_yes = array();
foreach ($meetup_api->getSelfEvents(array('status' => 'upcoming')) as $event) {
  $already_yes[$event->group->id.'__'.$event->id] = true;
}
if ($config_verbose) {
  echo "Fetched self events\n";
}

$start = strtotime('today') * 1000;
$end = $start + $config_load_days * 24 * 3600 * 1000;
$response = array();
$offset = 0;
while (true) {
  $t_response = $meetup_api->getOpenEvents(array(
    'zip' => $config_zipcode,
    'radius' => $config_miles,
    'page' => 500,
    'time' => $start.','.$end,
    'order' => 'trending',
    'desc' => 'true',
    'fields' => 'event_hosts',
    'limited_events' => $config_show_limited ? 'true' : 'false',
    'offset' => $offset,
  ));
  $offset += 1;
  $response = array_merge($response, $t_response);
  verbose_printf("Request responses %d\n", count($t_response));
  if (count($t_response) == 0) {
    break;
  }

  if ($t_response[count($t_response)-1]->yes_rsvp_count < $config_min_rsvp) {
    verbose_printf("Early exit, since RSVP counts are low\n");
    break;
  }
  if ($config_verbose) {
    echo "Fetched open events\n";
  }
}
if ($config_verbose) {
  echo "Finished fetching events\n";
}

foreach ($response as $event) {
  fixEventResponse($event);
}



verbose_printf("------------------------------------------\n");
verbose_printf("Num results %d\n", count($response));

function starts_with($name, $query) {
  return substr($name, 0, strlen($query)) === $query;
}

function is_acceptable($event) {
  global $config_min_rsvp;
  global $already_yes;

  if (array_key_exists($event->group->id.'__'.$event->id, $already_yes)) {
    verbose_printf("Skipping already YES rsvp %s | %s\n", $event->name, $event->group->name);
    return false;
  }

  if ($event->yes_rsvp_count < $config_min_rsvp) {
    verbose_printf("Skipping %d size --> %s | %s\n", $event->yes_rsvp_count, $event->name, $event->group->name);
    return false;
  }

  if ($event->waitlist_count > 1) {
    verbose_printf("Skipping %d waitlist count --> %s | %s\n", $event->waitlist_count, $event->name, $event->group->name);
    return false;
  }

  if ($event->rsvp_limit <= $event->yes_rsvp_count) {
    verbose_printf("Skipping %d rsvp limit --> %s | %s\n", $event->rsvp_limit, $event->name, $event->group->name);
    return false;
  }

  global $config_spammers;
  foreach ($config_spammers as $spammer) {
    if ($event->event_hosts) {
      foreach ($event->event_hosts as $host) {
        if ($host->member_id == $spammer) {
          verbose_printf("Skipping spammer --> %s | %s\n", $event->name, $event->group->name);
          return false;
        }
      }
    }
  }

  global $config_group_name_blacklist;
  foreach ($config_group_name_blacklist as $name) {
    if ($event->group->urlname == $name) {
      verbose_printf("Skipping blacklist --> %s | %s\n", $event->name, $event->group->name);
      return false;
    }
  }

  global $config_event_name_blacklist;
  foreach ($config_event_name_blacklist as $word) {
    if (strstr($event->name, $word) !== FALSE) {
      verbose_printf("Skipping blacklist --> %s | %s\n", $event->name, $event->group->name);
      return false;
    }
  }

  $warning_blacklist = array(
    '(EVERY SUNDAY)',
    '(EVERY THURSDAY)',
    '(EVERY WEDNESDAY)',
    'with International Travelers',
  );

  foreach ($warning_blacklist as $word) {
    if (strstr($event->name, $word) !== FALSE ||
        strstr($event->group->name, $word) !== FALSE) {
      printf("WARNING!!!! --> %s | %s\n", $event->name, $event->group->name);
      if ($event->event_hosts) {
        foreach ($event->event_hosts as $host) {
          printf("Host=%d(%s)\n", $host->member_id, $host->member_name);
        }
      }
    }
  }

  // Skip events that are livestreamed
  if (starts_with($event->venue->address_1, 'https://') ||
      starts_with($event->venue->address_1, 'http://')) {
    if (strstr($event->name, 'Livestream') !== FALSE) {
        verbose_printf("Livestream skip --> %s | %s\n", $event->name, $event->group->name);
        return false;
    }
  }

  return true;
}

function my_rsvp_is_no($event) {
  global $config_dir;
  global $meetup_api;
  global $config_user_id;

  $event_hash = $event->group->id.'__'.$event->id;
  if (file_exists($config_dir.'my_rsvp_no_'.$event_hash)) {
    return true;
  }

  echo "[Fetching RSVPS]";
  $no_rsvps = $meetup_api->getRsvps(array(
    'event_id' => $event->id,
    'rsvp' => 'no',
  ));

  foreach ($no_rsvps as $no_rsvp) {
    if ($no_rsvp->response == 'no') {
      if ((string)$no_rsvp->member->member_id == $config_user_id) {
        file_put_contents($config_dir.'my_rsvp_no_'.$event_hash, '');
        return true;
      }
    }
  }
  return false;
}


$total = count($response);
$response = array_filter($response, 'is_acceptable');
$skipped = $total - count($response);
printf("Skipped %d events\n", $skipped);
printf("------------------------------------------\n");
printf("Here are events I found for $config_load_days days\n");
printf("------------------------------------------\n\n");

foreach ($response as $event) {
  if ($config_notifications) {
    global $config_skip_event_urls;
    if (!in_array($event->event_url, $config_skip_event_urls)) {
      if (!my_rsvp_is_no($event)) {
        printf("Will send notification for %s\n", $event->name);
        passthru(sprintf(
          '%s -group %s -title %s -subtitle %s -message %s -open %s 2>&1',
          escapeshellarg('/usr/local/bin/terminal-notifier'),
          escapeshellarg($event->id),
          escapeshellarg('Meetup: '.$event->name),
          escapeshellarg('for group '.$event->group->name),
          escapeshellarg('RSVP count: '.$event->yes_rsvp_count),
          escapeshellarg($event->event_url)
        ));
        sleep(5);
      }
    }
  } else {
    printf(
      "%s (for %s in %s) time=%s (%s) ".

      "(yes=%d,maybe=%d,waitlist=%d,headcount=%d)\n\n",
      $event->name,
      $event->group->name,
      $event->venue->city,
      date('G:i', $event->time/1000),
      $event->event_url,

      $event->yes_rsvp_count,
      $event->maybe_rsvp_count,
      $event->waitlist_count,
      $event->headcount
    );
    printf("\n\n");

    global $config_debug_event_url;
    if ($config_debug_event_url == $event->event_url) {
      var_dump($event);
    }
  }
}
