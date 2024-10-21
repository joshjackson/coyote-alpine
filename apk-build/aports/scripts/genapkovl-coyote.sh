#!/bin/sh -e

HOSTNAME="coyote"

cleanup() {
        rm -rf "$tmp"
}

makefile() {
        OWNER="$1"
        PERMS="$2"
        FILENAME="$3"
        cat > "$FILENAME"
        chown "$OWNER" "$FILENAME"
        chmod "$PERMS" "$FILENAME"
}

rc_add() {
        mkdir -p "$tmp"/etc/runlevels/"$2"
        ln -sf /etc/init.d/"$1" "$tmp"/etc/runlevels/"$2"/"$1"
}

echo Creating Coyote Linux APK Overlay file....

tmp="$(mktemp -d)"
trap cleanup EXIT

mkdir -p "$tmp"/etc
makefile root:root 0644 "$tmp"/etc/hostname <<EOF
$HOSTNAME
EOF

mkdir -p "$tmp"/etc/network
makefile root:root 0644 "$tmp"/etc/network/interfaces <<EOF
auto lo
iface lo inet loopback

# auto eth0
# iface eth0 inet dhcp
EOF

mkdir -p "$tmp"/etc/apk
makefile root:root 0644 "$tmp"/etc/apk/world <<EOF
alpine-base
net-tools
dotnet8-runtime
aspnetcore8-runtime
rsync
util-linux
mc
EOF

mkdir -p "$tmp"/etc/setup
mkdir -p "$tmp"/etc/init.d
# Copy in the list of APKs to be installed on first boot
cat "$COYOTE_BUILD_ROOT"/coyote-config/firstboot-apks | makefile root:root 0644 "$tmp"/etc/setup/firstboot-apks
# Message of the day
cat "$COYOTE_BUILD_ROOT"/coyote-config/motd | makefile root:root 0644 "$tmp"/etc/motd
# OS Release info (sort of)
cat "$COYOTE_BUILD_ROOT"/coyote-config/os-release | makefile root:root 0644 "$tmp"/etc/os-release
# Coyote quick install script
cat "$COYOTE_BUILD_ROOT"/coyote-config/setup-answers | makefile root:root 0644 "$tmp"/etc/setup/answerfile
cat "$COYOTE_BUILD_ROOT"/coyote-config/setup-coyote | makefile root:root 0755 "$tmp"/etc/setup/setup-coyote
# Coyote firstboot configuration script
cat "$COYOTE_BUILD_ROOT"/coyote-config/firstboot-coyote | makefile root:root 0755 "$tmp"/etc/init.d/firstboot-coyote

rc_add devfs sysinit
rc_add dmesg sysinit
rc_add mdev sysinit
rc_add hwdrivers sysinit
rc_add modloop sysinit
rc_add machine-id sysinit

rc_add hwclock boot
rc_add modules boot
rc_add sysctl boot
rc_add hostname boot
rc_add bootmisc boot
rc_add syslog boot

rc_add mount-ro shutdown
rc_add killprocs shutdown
rc_add savecache shutdown

echo "Building from $PWD"
echo "Coyote Build Root: $COYOTE_BUILD_ROOT"

tar -c -C "$tmp" etc | gzip -9n > coyote-linux.apkovl.tar.gz
