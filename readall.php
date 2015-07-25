<?php

//
// Crawler for the 1000000 domains project
//
// (C) Jacques Mattheij 2015
//

/* This program is free software. It comes without any warranty, to
     * the extent permitted by applicable law. You can redistribute it
     * and/or modify it under the terms of the Do What The Fuck You Want
     * To Public License, Version 2, as published by Sam Hocevar. See
     * http://www.wtfpl.net/ for more details. */

$n_domains_to_scan = 10;      // change this to 1000000 to scan the whole set
$n_workers = 20;                // adjust depending on machine capacity and bandwidth available

// run a bunch of jobs in parallel using worker processes

class WorkerPool {

        private $jobs;
        private $job_prepare;
        private $job_cleanup;
        private $n_running;
        private $pool;
        private $n_workers;

        public function WorkerPool($jobs, $n_workers, $job_prepare, $job_cleanup) {
                $this->jobs = $jobs;
                $this->job_prepare = $job_prepare;
                $this->job_cleanup = $job_cleanup;
                $this->n_workers = $n_workers;

                $this->pool = array();

                $this->n_running = 0;

                for ($i=0;$i<$n_workers;$i++) {
                        $this->pool[$i] = FALSE;
                }
        }

        public function poll() {
                for($i=0;$i<$this->n_workers;$i++) {
                        // if a job is active in this slot then check if it is still running
                        if ($this->pool[$i] !== FALSE) {
                                $status=proc_get_status($this->pool[$i]['proc']);
                                if($status['running']==FALSE) {
                                        proc_close($this->pool[$i]['proc']);

                                        call_user_func($this->job_cleanup,$this->pool[$i]['job']);

                                        $this->pool[$i] = FALSE;

                                        $this->n_running--;
                                }
                        }

                        // start new workers when there are empty slots

                        if($this->pool[$i]===FALSE) {
                                if (count($this->jobs) != 0) {
                                        $job = array_shift($this->jobs);

                                        $this->pool[$i]['job'] = $job;

                                        $command = call_user_func($this->job_prepare, $job);

                                        $this->pool[$i]['proc'] = proc_open($command,array(),$dummy);

                                        $this->n_running++;
                                }
                        }
                }

                return $this->n_running;
        }
}

function job_prepare($domain) {

        $js = "var page = require('webpage').create();

                page.settings.resourceTimeout = 10000; // 10 seconds
                page.onResourceTimeout = function(e) {
                console.log(e.url);         // the url whose request timed out
                phantom.exit(1);
        };

        page.onResourceReceived = function(response) {

                console.log('{ \"content-type\":' + JSON.stringify(response.contentType) + ', \"url\": ' + JSON.stringify(response.url) + '},');
        };

        page.open('http://$domain/', function(status) {
                phantom.exit();
        });
        ";

        file_put_contents("js/$domain.requests.js",$js);

        echo "starting job for $domain\n";

        return 'phantomjs --ssl-protocol=any --ignore-ssl-errors=yes js/' . $domain . '.requests.js > requests/' . $domain;
}

function job_cleanup($domain) {
        echo "job for $domain ended\n";

        unlink("js/$domain.requests.js");
}

function createdir($d) {
        if (!is_dir($d)) {
                mkdir($d);
        }
}

$jobs = array();

$lines = explode("\n",file_get_contents("top-1m.csv"));

foreach ($lines as $line) {
        $domain = explode(",",$line)[1];

        if (!file_exists("requests/$domain")) {
                $jobs[] = $domain;

                if (count($jobs) > $n_domains_to_scan) {
                        break;
                }
        }
}

createdir("js");
createdir("requests");

$pool = new WorkerPool($jobs, $n_workers, "job_prepare", "job_cleanup");

while ($pool->poll()) {
        usleep(50000);
}
