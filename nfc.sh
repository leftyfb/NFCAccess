#!/bin/bash

runfile=/var/run/nfc
logfile=/var/log/nfc.log
DB=/home/pi/NFCAccess/frontdoor.db
gpio=/usr/local/bin/gpio

log(){
	echo "$(date "+%b %d %T") $@" >> $logfile
}

# switch GPIO 4
# RED LED GPIO 22
# BLUE LED GPIO 23
# RELAY/Door lock GPIO 25
for i in 4 22 23 25 ; do echo "$i" > /sys/class/gpio/unexport 2>/dev/null ; echo "$i" > /sys/class/gpio/export ;done

echo "in" > /sys/class/gpio/gpio4/direction
echo "out" > /sys/class/gpio/gpio22/direction
echo "out" > /sys/class/gpio/gpio23/direction
echo "out" > /sys/class/gpio/gpio25/direction

# turn on green LED
green_on(){
	echo "1" > /sys/class/gpio/gpio22/value
}

# turn off green LED
green_off(){
	echo "0" > /sys/class/gpio/gpio22/value
}

# turn on red LED
red_on(){
	echo "1" > /sys/class/gpio/gpio23/value
}

# turn on red LED
red_off(){
	echo "0" > /sys/class/gpio/gpio23/value
}

unlock(){
	# unlock 
	echo "1" > /sys/class/gpio/gpio25/value
	green_on
}

lock(){
	# lock 
	echo "0" > /sys/class/gpio/gpio25/value
	green_off
}
#Make sure lock is enabled on startup
lock

tone (){
  local note="$1" time="$2"
  if test "$note" -eq 0; then
    $gpio -g mode 18 in
  else
    local period="$(perl -e"printf'%.0f',600000/440/2**(($note-69)/12)")"
    $gpio -g mode 18 pwm
    $gpio pwmr "$((period))"
    $gpio -g pwm 18 "$((period/2))"
    $gpio pwm-ms
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
	sqlite3 $DB "INSERT INTO Entry VALUES(strftime('%s','now'),'$output','$@');"
	# get Name from sqlite database from the CardID
	Name=$(sqlite3 $DB "SELECT Name FROM AccessCards WHERE CardID='$output'")
	if [ -z "$Name" ]; then
		Name=$output
	fi	
	log "$Name $@" 
}

echo "1" > $runfile
runstat=$(cat $runfile)

# beep to signify it's ready
beep

while [ "$runstat" = "1" ]
	do 
	runstat=$(cat $runfile)
	DOW=$(date +%u)
	Hour=$(date +%H)
	# read from NFC/RFID reader
	output=$(/usr/local/bin/nfc-poll 2>/dev/null|grep UID|awk '{print $3,$4,$5,$6}'|sed 's/ //g')
	CheckDOW=$(sqlite3 $DB "SELECT DOW$DOW FROM AccessCards WHERE CardID='$output'")
	CheckHour=$(sqlite3 $DB "SELECT Hour$Hour FROM AccessCards WHERE CardID='$output'")
	switch=$(cat /sys/class/gpio/gpio4/value)
	if [ $switch = "1" ] ; then
		beep
		# lookup CardID in database
		CardExists=$(sqlite3 $DB "SELECT CardID FROM AccessCards WHERE CardID='$output'")
		if [ -z $CardExists ] ;then
			enterdb "Adding new card with name \"newcard\" and no access" 
			red_on
			# Add new card
			sqlite3 $DB "INSERT INTO AccessCards VALUES('$output','newcard','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0');"
			sleep 0.2
			red_off
			sleep 0.3
			beep
			green_on
			sleep 0.2
			green_off
		elif [ -n $CardExists ]; then
			Name=$(sqlite3 $DB "SELECT Name FROM AccessCards WHERE CardID='$output'")
			if [[ $CheckDOW = "0" ]] || [[ $CheckHour = "0" ]]; then
				enterdb "enabled access"
				green_on
				for i in `seq -w 0 23`;do sqlite3 $DB "UPDATE AccessCards SET Hour$i='1' WHERE CardID='$output';";done
				for i in `seq -w 1 7`;do sqlite3 $DB "UPDATE AccessCards SET DOW$i='1' WHERE CardID='$output';";done
				green_off
				sleep 0.3
				green_on
				sleep 0.2
				green_off
			elif [[ $CheckDOW = "1" ]] && [[ $CheckHour = "1" ]]; then
				enterdb "disabed access"
				green_on
				for i in `seq -w 0 23`;do sqlite3 $DB "UPDATE AccessCards SET Hour$i='0' WHERE CardID='$output';";done
				for i in `seq -w 1 7`;do sqlite3 $DB "UPDATE AccessCards SET DOW$i='0' WHERE CardID='$output';";done
				green_off
				sleep 0.3
				red_on
				sleep 0.2
				red_off
			fi
		fi
		switch=1
	elif [ $switch = "0" ];then

		# lookup CardID access rights in sqlite database
		if [[ $CheckDOW = "1" ]] && [[ $CheckHour = "1" ]]; then
			month=$(date +%m)
			if [ $month = "12" ]; then
				playxmas
			else
				beep
			fi
			unlock
			enterdb granted
			sleep 4
			lock
		elif [[ $CheckDOW = "0" ]] || [[ $CheckHour = "0" ]]; then
			red_on
			beep
			red_off
			enterdb denied
			sleep 1
		elif [ -z $output ]; then
			continue
		else
			enterdb unknown
			beep
			red_on
			sleep 0.1
			red_off
			sleep 0.2
			red_on
			sleep 0.1
			red_off
			sleep 1
		fi
	fi
done
