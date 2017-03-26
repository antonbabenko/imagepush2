#!/bin/bash

# Dynamodb dump restore script.
#
# Make sure that IAM user has enough permissions to be able to restore DynamoDB!

readonly S3_DUMP_TGZ_FILENAME="s3://backups-anton-server/dynamodb/20170326_08.imagepush.tgz"

#####

readonly TMP_DIR=$(mktemp -d)

readonly SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

aws s3 cp "$S3_DUMP_TGZ_FILENAME" "$TMP_DIR/dynamodbdymp.tgz"

cd "$TMP_DIR"

tar -xf "$TMP_DIR/dynamodbdymp.tgz"

#####
# This will restore "images" table from backup into "images_restored"

"${SCRIPT_PATH}/dynamodump.py" -m restore -r eu-west-1 -s images -d images_restored --writeCapacity 100

#####

rm -rf "$TMP_DIR"
