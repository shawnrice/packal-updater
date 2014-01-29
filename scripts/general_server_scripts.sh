packal-maestro

for each in /data/scripts/minute/*.sh;
do 
	sh $each ; 
done ;

---
clam-assassin

#!bin/bash

# I want to just make sure that the webserver doesn't die...

if [ -z "$(pidof php5-fpm)" ] 
then
  service php5-fpm start
fi


if [ -z "$(pidof mysqld)" ] 
then
  service mysql start
fi

if [ -z "$(pidof nginx)" ] 
then
  service nginx start
fi

---
revive-webserver

#!bin/bash

# So, I haven't figured out how to keep clamd from starting up,
# but I don't want to screw with config files that I haven't 
# taken the time to understand. So... This is a script to see
# if clamav-daemon is running and to kill it if it is.

if [ "$(pidof clamd)" ] 
then
  kill "$(pidof clamd)"
fi




sh -c 'echo "$(pidof sh friendly-system-bot)"'