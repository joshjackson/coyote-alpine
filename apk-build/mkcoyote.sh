#!/bin/sh

export COYOTE_BUILD_ROOT=$PWD

aports/scripts/mkimage.sh \
        --tag v3.20 \
        --outdir ~/iso \
        --workdir ~/cache \
        --arch x86_64 \
        --repository https://dl-cdn.alpinelinux.org/alpine/v3.20/main \
        --repository https://dl-cdn.alpinelinux.org/alpine/v3.20/community \
        --profile coyote

#rm -f /mnt/c/Users/jjack/Downloads/WSL\ Distro/coyote.iso
#rsync -v --progress iso/alpine-coyote-v3.10-x86_64.iso /mnt/c/Users/jjack/Downloads/WSL\ Distro/coyote.iso

