#!/bin/bash

# Set the cache directory
bundle='com.packal'
file="$HOME/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle/zombie"
# See if the time file exists; if so, check the time
# Otherwise, kill the server (just in case)
# And exit the script

# Sleep for an initial 30 seconds to make sure that everything launches before this script goes bye-bye
echo "Sleeping for thirty seconds."
sleep 30

alive=1
echo "Waking up and starting the checks"
while [ $alive -eq 1 ]
do
	echo "Doing a check..."
	# file=$HOME/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow\ Data/$bundle/zombie
	if [ -e "$file" ]
	then 
		# Find the UNIX Epoch time
		now=`date +%s`
		# Get the last update of the webserver in Unix Epoch time (read from file)
		# file="/Users/Sven/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow\\ Data/com.packal/webserver/zombie"
		date=`cat "$file"`

		# Find the difference
		diff=`expr $now - $date`
		# If the difference is greater than two minutes, then kill the webserver
		if [ $diff -gt 120 ]
			then
				pid=`ps aux | grep 'php -S localhost:7893 -t gui' | grep -v grep | awk '{print $2}'`
				rm "$file"
				kill "$pid"
				echo "Killing the webserver"
				alive=0
			else
				echo "Webserver will live just longer"
				sleep 60
		fi
	else
		pid=`ps aux | grep 'php -S localhost:7893 -t gui' | grep -v grep | awk '{print $2}'`
		rm "$file"
		kill "$pid"
		echo "Killing the webserver"
		alive=0
	fi
done
