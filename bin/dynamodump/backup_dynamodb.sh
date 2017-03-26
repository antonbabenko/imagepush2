#!/bin/bash

readonly S3BUCKET="s3://backups-anton-server/dynamodb/"

readonly TMP_DIR=$(mktemp -d)
readonly TGZ_FILENAME="$(date +%Y%m%d_%H).imagepush.tgz"

readonly SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

"${SCRIPT_PATH}/dynamodump.py" -m backup -r eu-west-1 -s "*" --readCapacity 200 --dumpPath "$TMP_DIR"

cd "$TMP_DIR"

tar -zcvf "/tmp/$TGZ_FILENAME" .

aws s3 cp "/tmp/$TGZ_FILENAME" "$S3BUCKET"

rm -rf "$TMP_DIR" "/tmp/$TGZ_FILENAME"
