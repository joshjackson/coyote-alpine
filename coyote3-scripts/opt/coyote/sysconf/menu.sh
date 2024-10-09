#!/bin/sh
#
# Configuration Menu System for Vortech
# Embedded Linux


if [ ! -z "$1" ]; then

	SCPTEST=`echo "$@" | cut -f 2 -d " "`

	if [ "$SCPTEST" = "scp" ]; then
		echo "You must use the debug user account for scp access."
		exit 1
	fi

	echo "Invalid call to system menu scripts"
	exit 2

fi

export PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin
umask 022

# Fix backspace for telnet sessions
if [ "$TERM" != "linux" ]; then
        stty erase ^H
fi

if [ -r /etc/config/hwconfig ]; then
	. /etc/config/hwconfig
fi

. /usr/config/functions

# If we aren't sure, take a guess
if [ -z "$BOOT_DEV" ]; then
	$BOOT_DEV=/dev/hda1
fi

HEADER="Coyote Configuration Menu"

# Save the system configuration to a floppy disk
save_config() {
	echo "Insert a pre-formatted floppy and press ENTER when ready."
	read JUNK
	mkdir /tmp/floppy
	SAVEOK=YES
	mount /dev/fd0 /tmp/floppy -t msdos 1> /dev/null 2> /dev/null
	if [ $? = 0 ]; then
		CURDIR=$PWD
		cd /mnt/config
		tar -cf /tmp/floppy/coyote.tar .
		if ! [ $? = 0 ]; then
			SAVEOK=NO
		fi
		umount /tmp/floppy
		cd $CURDIR
	else
		SAVEOK=NO
	fi
	rm -Rf /tmp/floppy
	if [ "$SAVEOK" = "NO" ]; then
		echo "Some errors were detected during backup. Press ENTER to return to menu."
	else
		echo
		echo "!!!!!!!!!!!!!!!!!! [ WARNING ] !!!!!!!!!!!!!!!!!"
		echo "The information contained on the backup floppy"
		echo "must be kept secure. It contains the private  "
		echo "key information for this VEL device."
		echo "!!!!!!!!!!!!!!!!!! [ WARNING ] !!!!!!!!!!!!!!!!!"
		echo
		echo "Configuration saved successfully. Press ENTER to return to menu."
	fi
	read JUNK
	unset JUNK
	unset SAVEOK
	unset CURDIR
}

# Load the system configuration from a floppy disk
load_config() {
	echo "Insert the floppy containing your system configuration, press ENTER when ready."
	read JUNK
	mount_flash_rw
	mkdir /tmp/floppy
	SAVEOK=YES
	mount /dev/fd0 /tmp/floppy -t msdos 1> /dev/null 2> /dev/null
	if [ $? = 0 ]; then
		if [ ! -r /tmp/floppy/coyote.tar ]; then
			echo "This floppy does not appear to contain a valid backup."
			echo
			SAVEOK=NO
		else
			CURDIR=$PWD
			cd /mnt/config
			# Restore IPSEC configuration
			rm -Rf /mnt/config/*
			tar -xf /tmp/floppy/coyote.tar
			if ! [ $? = 0 ]; then
				SAVEOK=NO
			fi
		fi

		umount /tmp/floppy
	else
		SAVEOK=NO
	fi
	rm -Rf /tmp/floppy
	if [ "$SAVEOK" = "NO" ]; then
		echo "Configuration was not restored properly. Press ENTER to return to menu."
	else
		echo "Configuration loaded successfully. Press ENTER to return to menu."
	fi
	read JUNK
	unset JUNK
	unset SAVEOK
	unset CURDIR
	mount_flash_ro
}

query() {
	eval DEFAULT=\$$2
	if [ -z "$DEFAULT" ]; then
		echo -ne "$1: "
	else
		echo -ne "$1 [$DEFAULT]: "
	fi
	read REPLY
	if [ -z "$REPLY" ]; then
		REPLY=$DEFAULT
	fi
	eval $2=$REPLY
}


query_yn() {
	eval REPLY=\$$2
	while [ : ]; do
		query "$1 (Y/N)" REPLY
		if [ "$REPLY" = "y" ]; then
			REPLY="YES"
		fi
		if [ "$REPLY" = "yes" ]; then
			REPLY="YES"
		fi
		if [ "$REPLY" = "Y" ]; then
			REPLY="YES"
		fi
		if [ "$REPLY" = "n" ]; then
			REPLY="NO"
		fi
		if [ "$REPLY" = "no" ];  then 
			REPLY="NO"
		fi
		if [ "$REPLY" = "N" ]; then
			REPLY="NO"
		fi
		if [ "$REPLY" = "YES" ]; then
			break
		fi
		if [ "$REPLY" = "NO"  ]; then
			break
		fi
		REPLY=""
	done
	eval $2=$REPLY
}


mainmenu() {

while [ : ]; do

	clear
	echo $HEADER
	echo
	echo "1) Load system configuration from a floppy **"
	echo "2) Save system configuration to a floppy **"
	echo "3) Firmware update menu"
	echo "4) Edit main configuration file"
	echo "5) Configuration reload"
	echo "6) Reboot the system"
	echo "7) Edit post-boot script"
	echo "8) System information menu"
	echo
	echo "q) Quit"
	echo
	echo " ** On supported hardware only. "
	echo
	echo -n "Enter Selection: "
	read CHOICE

	case $CHOICE in
	1)
		load_config
		;;
	2)
		save_config
		;;
	3)
		. /usr/config/menu.firmware
		;;
	4)
		mount_flash_rw
		nano -w /mnt/config/config
		mount_flash_ro
		;;
	5)
		/sbin/loadconfig
		;;
	6)
		echo
		query_yn "Are you sure you want to reboot the system" YN
		if [ "$YN" = "YES" ]; then
			/sbin/reboot
			exit
		fi
		;;
	7)
		mount_flash_rw
		if ! [ -d /mnt/config/rc.d ]; then
			mkdir /mnt/config/rc.d
		fi
		if ! [ -r /mnt/config/rc.d/post-boot-script ]; then
			touch /mnt/config/rc.d/post-boot-script
		fi
		chmod 700 /mnt/config/rc.d/post-boot-script
		nano -w /mnt/config/rc.d/post-boot-script
		mount_flash_ro
		;;
	8)
		. /usr/config/menu.sysinfo
		;;
	q)
		break
		;;
	*)
		echo "Invalid selection"
	   	sleep 2
	   	;;
	esac
done

} # mainmenu()

mainmenu

clear
