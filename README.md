NFCAccess
=========

Function: RFID/NFC entry

#### Usage:
	Swipe card to gain access
	Hold button then swipe new card to add it to the SQLite3 database
	Hold button then swipe existing card to toggle access privileges

#### Hardware:
	Raspberry Pi - http://raspberrypi.org
	PN532 NFC/RFID controller breakout board - https://www.adafruit.com/products/364

#### Software:
	Raspbian - http://www.raspberrypi.org/downloads
	sudo apt-get install libusb-dev sqlite3
	WiringPi - https://projects.drogon.net/raspberry-pi/wiringpi/
	libnfc - http://libnfc.googlecode.com

#### Reference:
	http://learn.adafruit.com/adafruit-nfc-rfid-on-raspberry-pi/
	


### TODO
 - [_] Add multiple entry points
 - [✓] Hours of the Day access
 - [✓] Day of the week access
 - [_] Time allotted access
 - [✓] Different tones for different access/errors/holidays
 - [✓] LED light
 - [✓] Script to add cards
 - [✓] Button to auto-add card
 - [_] Web interface
 - [_] Mobile interface
