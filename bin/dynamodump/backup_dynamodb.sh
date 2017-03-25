#!/bin/bash

S3BUCKET="s3://backups-anton-server/dynamodb/"

TMP_DIR=$(mktemp -d)
TGZ_FILENAME="$(date +%Y%m%d_%H).imagepush.tgz"

./dynamodump.py -m backup -r eu-west-1 -s "*" --readCapacity 100 --dumpPath "$TMP_DIR"

cd "$TMP_DIR"

tar -zcvf "/tmp/$TGZ_FILENAME" .

aws s3 cp "/tmp/$TGZ_FILENAME" "$S3BUCKET"

rm -rf "$TMP_DIR" "/tmp/$TGZ_FILENAME"
