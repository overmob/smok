#! /bin/sh
#
# Original Author:	Andreas Olsson <andreas@arrakis.se>
# Modified by:          Gonzalo Cao Cabeza de Vaca <gonzalo.cao@gmail.com>

# For each tunnel; make a uniquely named copy of this template.

### BEGIN INIT INFO
# Provides:          autossh
# Required-Start:    $local_fs $network
# Required-Stop:     $local_fs
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: autossh
# Description:       autossh tunnel
### END INIT INFO

## SETTINGS
#
# autossh monitoring port (unique)
MPORT=0
# the ssh tunnel to setup
TUNNEL="-R 2222:localhost:22"
# remote user
RUSER="root"
# remote server
RSERVER="165.22.70.59"
IDENTITY="-i /home/pi/.ssh/id_rsa"

# You must use the real autossh binary, not a wrapper.
DAEMON=/usr/lib/autossh/autossh
#
## END SETTINGS

PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

NAME=`basename $0`
PIDFILE=/var/run/${NAME}.pid
SCRIPTNAME=/etc/init.d/${NAME}
DESC="AutoSSH tunnel"

test -x $DAEMON || exit 0

export AUTOSSH_PORT=${MPORT}
export AUTOSSH_PIDFILE=${PIDFILE}
ASOPT=" -q -M ${MPORT} -N -o \"ServerAliveInterval 60\" -o \"ServerAliveCountMax 3\" ${IDENTITY} ${TUNNEL} "${RUSER}"@"${RSERVER}


#	Function that starts the daemon/service.
d_start() {
    echo ${ASOPT}
    echo "start-stop-daemon --start --quiet --pidfile $PIDFILE --exec $DAEMON -- $ASOPT"
	start-stop-daemon --start --quiet --pidfile $PIDFILE \
		--exec $DAEMON -- $ASOPT

	echo "->$?";
	if [ $? -gt 0 ]; then
	    echo -n " not started (or already running)"
	else
	    sleep 1
	    start-stop-daemon --stop --quiet --pidfile $PIDFILE \
		--test --exec $DAEMON > /dev/null || echo -n " not started"
	fi

}

#	Function that stops the daemon/service.
d_stop() {
	start-stop-daemon --stop --quiet --pidfile $PIDFILE \
		--exec $DAEMON \
		|| echo -n " not running"
}


case "$1" in
  start)
	echo -n "Starting $DESC: $NAME"
	d_start
	echo "."
	;;
  stop)
	echo -n "Stopping $DESC: $NAME"
	d_stop
	echo "."
	;;

  restart)
	echo -n "Restarting $DESC: $NAME"
	d_stop
	sleep 1
	d_start
	echo "."
	;;
  *)
	echo "Usage: $SCRIPTNAME {start|stop|restart}" >&2
	exit 3
	;;
esac

exit 0