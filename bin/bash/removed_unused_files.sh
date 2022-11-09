#!/usr/bin/env bash

SCRIPT=$(readlink -f "$0")
SCRIPTPATH=$(dirname "$SCRIPT")

KERNEL_DIR="$SCRIPTPATH/../../app"
yml=`cat ${KERNEL_DIR}/config/parameters.yml`

DELETE_AFTER_DAYS=7
pathToLog=${KERNEL_DIR}/../var/logs/removed_unused_files.log

path=$(echo "$yml" | sed -n "s/^.*upload_article_images_dir: '\(\S*\)'.*$/\1/p")
path=$(echo "$path" | sed -e "s@%kernel.root_dir%@$KERNEL_DIR@")

if [ -z ${path} ]; then
    echo 'Parameter "upload_article_images_dir" is not found'
    exit 0
fi

pathTmp=$(realpath ${path}_tmp)
if [ ! -d ${pathTmp} ]; then
    echo "Directory \"$pathTmp\" does not exist"
else
    find $pathTmp -ctime +$DELETE_AFTER_DAYS -type f | rotatelogs -n 3 $pathToLog 1M #-cmin +1 (min), -ctime +10 (day)
    find $pathTmp -ctime +$DELETE_AFTER_DAYS -type f -delete
fi
