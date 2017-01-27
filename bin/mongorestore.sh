#!/bin/sh

BUCKET="s3://backups-anton-server"
KEY=`aws s3 ls $BUCKET/mongo/ --recursive | sort | tail -n 1 | awk '{print $4}'`

aws s3 cp $BUCKET/$KEY /tmp/latest-mongobackup.tar.gz

mkdir -p /tmp/latest-mongobackup

cd /tmp/latest-mongobackup

tar -zxvf /tmp/latest-mongobackup.tar.gz

mongorestore -d imagepush_imported --drop --dir=/tmp/latest-mongobackup/