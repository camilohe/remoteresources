<?php

//
// A program to analyze the 1,000,000 homepages dataset
// to answer a number of questions:
//
// General questions about resources
// - what is the average number of resources a webpage loads
// - what does the distribution of the number of resources loaded look like
//
// General questions about external resources:
// + what percentage of the pages loads external resources
// - what percentage of the pages uses https
// - what percentage of the pages has advertising on them
// - what percentage of the pages have trackers on them
// - and how many trackers do they have on average
//
// Questions about javascript
// + how many pages have javascript on them
// + how many pages have externally sourced javascript on them
//
// Questions about libraries and frameworks
// + how many pages are made with bootstrap
// + how many pages are made with angular
// + how many pages are made with drupal
// + how many pages are made with wordpress
// + how many pages are made with jquery
//
// Questions about programming languages
// - 
//
// Questions about flash
// + how many pages (still) have flash on them
// + how many pages have externally loaded flash on them
//
// Questions about specific providers of external resources
// + how many pages have resources embedded from google on them
// - how many pages refer to google in some way
// + how many pages have resources embedded from facebook on them
// - how many pages refer to facebook on them
// + how many pages have resources embedded from yahoo on them
// - are there any 'stealth' companies whose resources are present on a large number of pages
// + how many pages have resources embedded from twitter on them
// - how many pages refer to twitter
//
// Questions about evercookies
// + how many sites have evidence of evercookies on them
// - which sites are those
//
// Questions that I can't answer with this data
// - how many sites use cookies
// - how many sites use cookies with a very long lifetime


require("dir.php");

function strupto($s,$t) {

        $p = strpos($s,$t);

        if ($p !== false) {
                $s = substr($s,0,$p);
        }

        return $s;
}

function load_keys($list) {
        $c = file_get_contents($list);

        $lines = explode("\n", $c);

        $result = [];

        foreach ($lines as $line) {
                $line = trim($line);
                if (substr($line,-1) == "\r") {
                        $line = substr($line,0,strlen($line)-1);
                }
                if (startswith($line,"0.0.0.0 ")) {

                        $line = substr($line,8);

                        $result[strupto($line,' ')] = true;
                }
        }

        return $result;
}

$adservers = load_keys("hosts.txt");

assert(strupto("abc","?") == "abc");
assert(strupto("abcde?abc","?") == "abcde");

function startswith($s, $c) {

        if (substr($s, 0, strlen($c)) == $c) {
                return true;
        }

        return false;
}

function contains($s, $c) {
        if (stripos($s, $c) !== false) {
                return true;
        }

        return false;
}

function isjs($type, $url) 

{
        if (substr($url,-3) == '.js') {
                return true;
        }

        $type_triggers = array("javascript", "ecmascript", "application/js");

        foreach ($type_triggers as $t) {
                if (contains($type, $t)) {
                        return true;
                }
        }

        return false;
}

function isflash($type, $url)

{
        if (substr($url,-3) == '.swf') {
                return true;
        }

        $type_triggers = array("x-shockwave-flash");

        foreach ($type_triggers as $t) {
                if (contains($type, $t)) {
                        return true;
                }
        }

        return false;
}

function google($url) {

        return contains($url,'google') || contains($url,'gstatic') || contains($url,'doubleclick') || contains($url,'youtube');
}

function facebook($url) {
        return contains($url,'facebook') || contains($url,'fbcdn');
}

function yahoo($url) {
        return contains($url,'yahoo') || contains($url,'yimg.com');
}

function twitter($url) {
        return contains($url,'twitter');
}

function ads($url) {

        global $adservers;

        $parts = parse_url($url);

        if (isset($parts['host'])) {
                if (isset($adservers[$parts['host']])) {
                        return true;
                }
        }

        return false;
}

// check to see if the url specified by the string
// is relative to the domain or is on a different
// domain

$hosts = [];

function same_domain($d,$s) {
        global $hosts;

        $parts = parse_url($s);

        // no host specified so relative path

        if (!isset($parts['host'])) {
                return true;
        }

        // domains are identical

        if ($d == $parts['host']) {
                return true;
        }

        // host has a subdomain but otherwise identical

        if ($d == substr($parts['host'],-strlen($d))) {
                return true;
        }

        @$hosts[$parts['host']]++;

        // otherwise it's a different domain
        return false;
}

assert(same_domain("abc.com","https://abc.com/xyz.js"));
assert(same_domain("abc.com","/test.js"));
assert(same_domain("abc.com","http://subdomain.abc.com/"));

assert(!same_domain("abc.com","http://subdomain.xyz.com/"));
assert(!same_domain("abc.com","http://xyz.com/"));
assert(!same_domain("abc.com","//xyz.com/"));

// reset hosts array after unit tests

$hosts = [];

$inputdir = "requests/";

$stats = [];

$stats['n_domains'] = 0;
$stats['n_domains_with_errors'] = 0;
$stats['n_domains_with_js'] = 0;
$stats['n_domains_with_external_js'] = 0;
$stats['n_domains_with_external_content'] = 0;
$stats['n_domains_with_google'] = 0;
$stats['n_domains_with_facebook'] = 0;
$stats['n_domains_with_yahoo'] = 0;
$stats['n_domains_with_twitter'] = 0;
$stats['n_domains_with_flash'] = 0;
$stats['n_domains_with_external_flash'] = 0;
$stats['n_domains_clean'] = 0;
$stats['n_domains_with_ads'] = 0;

// simple triggers in urls
// a counter is created for each of these

$triggers = array("angular", "jquery", "evercookie","bootstrap","drupal","wp-content");

foreach ($triggers as $trigger) {
        $stats["n_domains_with_$trigger"] = 0;
}

$domains = [];

$messages = [];

$nresources = [];

function process_domain($d) {
        global $stats;
        global $inputdir;
        global $triggers;
        global $domains;
        global $messages;
        global $nresources;

        echo("$d\n");

        $stats['n_domains']++;

        $c = file_get_contents($inputdir . $d);

        $lines = explode("\n", $c);

        foreach ($lines as $i => $line) {
                if (substr($line,0,1) != '{') {
                        $lines[$i] = '';
                }
        }

        $c = implode("\n", $lines);

        $c = "[" . $c . "]";

        $c = str_replace("\n","",$c);

        $c = str_replace(",]", "]", $c);

        $c = str_replace("Syntax error: parse error","",$c);

        $c = str_replace("\n","",$c);

        $resources = array_unique(json_decode($c, 1),SORT_REGULAR);

        $n = count($resources);

        if ($n == 0) {

                echo "$d has errors\n";

                $stats['n_domains_with_errors']++;
                return;
        }

        @$nresources[$n / 10]++;

        if ($n > 500) {
                $messages[] = "$d has $n resources";
        }

        // and then update the statistics

        $has_js = false;
        $has_external_js = false;

        $has_flash = false;
        $has_external_flash = false;

        $has_external_content = false;

        $has_google = false;
        $has_facebook = false;
        $has_yahoo = false;
        $has_twitter = false;

        $has_ads = false;

        $triggered = [];

        foreach ($triggers as $trigger) {
                $triggered[$trigger] = false;
        }

        foreach ($resources as $res) {

                $url = $res['url'];
                $type = $res['content-type'];

                if (isflash($type,$url)) {
                        $has_flash = true;
                        $is_flash = true;
                }
                else {
                        $is_flash = false;
                }
        
                if (isjs($type,$url)) {
                        $has_js = true;
                        $is_js = true;
                }
                else {
                        $is_js = false;
                }

                if (!$has_ads) {
                        if (ads($url)) {
                                echo "$d contains ads from $url\n";
                                $has_ads = true;
                        }
                }

                foreach ($triggers as $trigger) {
                        if (contains($url,$trigger)) {
                                $triggered[$trigger] = true;
                        }
                }

                if (contains($url,'jquery')) {
                        $has_jquery = true;
                }

                if (facebook($url)) {
                        $has_facebook = true;
                }

                if (google($url)) {
                        $has_google = true;
                }

                if (yahoo($url)) {
                        $has_yahoo = true;
                }

                if (twitter($url)) {
                        $has_twitter = true;
                }

                if (!same_domain($d,$url)) {
                        if ($is_js) {
                                $has_external_js = true;
                        }

                        if ($is_flash) {
                                $has_external_flash = true;
                        }

                        $has_external_content = true;
                }
        }

        foreach ($triggers as $trigger) {
                if ($triggered[$trigger]) {
                        $stats["n_domains_with_$trigger"] += 1;
                        $domains[$trigger][] = $d;
                }
        }


        $stats['n_domains_with_js'] += $has_js ? 1 : 0;
        $stats['n_domains_with_external_js'] += $has_external_js ? 1 : 0;
        $stats['n_domains_with_external_content'] += $has_external_content ? 1 : 0;
        $stats['n_domains_clean'] += $has_external_content ? 0 : 1;
        $stats['n_domains_with_flash'] += $has_flash ? 1 : 0;
        $stats['n_domains_with_external_flash'] += $has_external_flash ? 1 : 0;
        $stats['n_domains_with_google'] += $has_google ? 1 : 0;
        $stats['n_domains_with_facebook'] += $has_facebook ? 1 : 0;
        $stats['n_domains_with_yahoo'] += $has_yahoo ? 1 : 0;
        $stats['n_domains_with_twitter'] += $has_twitter ? 1 : 0;
        $stats['n_domains_with_ads'] += $has_ads ? 1 : 0;
}

dir_apply($inputdir,"process_domain");

// show which hosts were found how many times

asort($hosts);

echo "resource distribution (divided by 10):\n";

ksort($nresources);

print_r($nresources);

echo "domains using evercookies:\n";

print_r(@$domains['evercookie']);

echo "messages:\n";

print_r($messages);

foreach ($stats as $k => $v) {

        $p = intval(100*($v / $stats['n_domains']));

        echo "$v $k ($p %)\n";
}
