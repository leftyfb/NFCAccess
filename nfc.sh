#!/bin/bash

DB=/home/pi/frontdoor.db
echo "22" > /sys/class/gpio/unexport 2>/dev/null
echo "23" > /sys/class/gpio/unexport 2>/dev/null
echo "25" > /sys/class/gpio/unexport 2>/dev/null
echo "22" > /sys/class/gpio/export
echo "23" > /sys/class/gpio/export
echo "25" > /sys/class/gpio/export
echo "out" > /sys/class/gpio/gpio22/direction
echo "out" > /sys/class/gpio/gpio23/direction
echo "out" > /sys/class/gpio/gpio25/direction
echo "1" > /sys/class/gpio/gpio25/value


tone (){
  local note="$1" time="$2"
  if test "$note" -eq 0; then
    gpio -g mode 18 in
  else
    local period="$(perl -e"printf'%.0f',600000/440/2**(($note-69)/12)")"
    gpio -g mode 18 pwm
    gpio pwmr "$((period))"
    gpio -g pwm 18 "$((period/2))"
    gpio pwm-ms
  fi
  sleep "$time"
}

playxmas(){
 tone 63 0.2
 tone 63 0.2
 tone 63 0.3
 tone 0 0
 tone 0 0
 tone 0 0
 tone 63 0.2
 tone 63 0.2
 tone 63 0.3
 tone 0 0
 tone 0 0
 tone 0 0
 tone 63 0.2
 tone 65 0.2
 tone 61 0.3
 tone 62 0.1
 tone 63 0.6
 tone 0 0
}

playshave(){
	tone 60 0.2
	tone 55 0.1
	tone 55 0.1
	tone 57 0.2
	tone 55 0.2
	tone 0 0.2
	tone 59 0.2
	tone 60 0.2
	tone 0 0
}

beep(){
	tone 107 0.2
	tone 0 0

}
enterdb(){
	# insert cardID and timestamp into sqlite database
	sqlite3 $DB "INSERT INTO Entry VALUES('$output', strftime('%s','now'));"
	# get Name from sqlite database from the CardID
	Name=$(sqlite3 $DB "SELECT Name FROM AccessCards WHERE CardID='$output'")
	# get the TIMESTAMP(in epoch format) from the databbase so we can output on the screen the time the card was swiped
	time=$(sqlite3 $DB "SELECT TIMESTAMP FROM Entry WHERE CardID='$output' ORDER BY TIMESTAMP DESC limit 1")
	# covert epoch TIMESTAMP to human readable form
	dtime=$(date -d @$time)
	echo $dtime $Name entered
}

unlockdoor(){
	# unlock door
	echo "0" > /sys/class/gpio/gpio25/value
	# turn on green LED
	echo "1" > /sys/class/gpio/gpio22/value
}

lockdoor(){
	# lock door
	echo "1" > /sys/class/gpio/gpio25/value
	# turn on green LED
	echo "0" > /sys/class/gpio/gpio22/value
}

while true
	do 
	# read from NFC/RFID reader
	output=$(nfc-poll 2>/dev/null|grep UID|awk '{print $3,$4,$5,$6}'|sed 's/ //g')
	# lookup CardID access rights in sqlite database
	AllowAccess=$(sqlite3 $DB "SELECT AllowAccess FROM AccessCards WHERE CardID='$output'")
	if [ $AllowAccess = "yes" ] ; then
		month=$(date +%m)
		if [ $month = "12" ]; then
			playxmas
		else
			beep
		fi
		unlockdoor
		enterdb
		sleep 4
		lockdoor
	elif [ $AllowAccess = "no" ] ; then
		# turn on red LED
		echo "1" > /sys/class/gpio/gpio23/value
		beep
		# turn off red LED
		echo "0" > /sys/class/gpio/gpio23/value
		echo "$(enterdb) but does not have access"
		sleep 1
	elif [ -z $output ]; then
		continue
	else
		echo $(date) $output
		# turn on red LED
		echo "1" > /sys/class/gpio/gpio23/value
		beep
		# turn off red LED
		echo "0" > /sys/class/gpio/gpio23/value
		sleep 1
	fi

done
