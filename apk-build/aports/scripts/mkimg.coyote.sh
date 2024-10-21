profile_coyote() {

        echo "Starting Coyote Linux profile build..."

        hostname="coyote"
        profile_extended
        apks="$apks \
                dosfstools \
                mkinitfs \
                util-linux \
                syslinux \
                rsync \
                mc \
                aspnetcore8-runtime \
                dotnet8-runtime \
                net-tools \
                strongswan \
                strongswan-openrc \
                nano \
                grub-efi
                "
        local _k _a
        for _k in $kernel_flavors; do
                apks="$apks linux-$_k"
                for _a in $kernel_addons; do
                        apks="$apks $_a-$_k"
                done
        done
        apks="$apks linux-firmware"
        apkovl="aports/scripts/genapkovl-coyote.sh"
}

