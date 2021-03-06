# remoteresources
a project to determine how many websites load remote resources for their homepages

The blog entry related to this repository is at [One Million Websites](http://jacquesmattheij.com/one-million-websites).

To run this code you'll need:

- The Alexa top 1 million sites list, you can download it from:

    http://s3.amazonaws.com/alexa-static/top-1m.csv.zip

- The advertisers hosts file, you can download this from:

    http://winhelp2002.mvps.org/hosts.txt

- php

- wget

- phantomjs

    http://phantomjs.org/

- A lot of free diskspace.

- A pretty good internet connection

Adjust the values in the beginning of the 'readall.php' script to reflect your machine capacity, desire to fill up diskspace and how much bandwidth you've got to play with. On my connection with 200 Mbit down and 20 Mbit up downloading all 1M homepages with 30 concurrent workers took more than a week.

Once you have all that in place you can run the crawler by typing:

php readall.php

I suggest you do a short run first with the default settings to ensure that everything is working properly, then run the analyzer to make sure that is working properly as well.

Run it with this command:

php process.php

After a while that should spit out some interesting statistics about the data the crawler retrieved.

If all that works out then you can do a larger run.

Here is the exact sequence of commands you need to execute to run the test from just a blank directory:

(Tested on Ubuntu Linux)

    git clone https://github.com/jacquesmattheij/remoteresources.git
    cd remoteresources/
    wget http://s3.amazonaws.com/alexa-static/top-1m.csv.zip
    unzip top-1m.csv.zip 
    php readall.php
    wget http://winhelp2002.mvps.org/hosts.txt
    php process.php

Things that affect the accuracy of the results produced:

- the hosts file contains many entries that are not just advertisers but also analytics related
- the algorithm for the determination of whether or not a resource is external is imperfect
- the number of domains you scan before you run the processor
- the fact that the crawler only reads homepages and does not descend deeper into the websites 
- the 'js detector' only looks at javascript loaded via separate urls, *not* for javascript embedded in the pages
 


