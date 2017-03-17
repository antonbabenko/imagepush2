#!/bin/bash

exit 0

DUMP_DIR="/home/ec2-user/dynamodb_backups"
S3BUCKET="s3://backups-anton-server/dynamodb"

mkdir -p $DUMP_DIR

rm "$DUMP_DIR/*tar.gz" > /dev/null 2>&1

echo "Dumping DynamoDB databases"
FILENAME=`date +%Y%m%d`

# Dump all tables (including: counter, images, images_tags, latest_tags, links, processed_hashes, tags)
./dynamodump.py -m backup -r eu-west-1 -s "*" --dumpPath $DUMP_DIR --readCapacity 100 --writeCapacity 20

# Restore tables (one by one)
#./dynamodump.py -m restore -r eu-west-1 -s $DUMP_DIR/images -d images_new --readCapacity 100 --writeCapacity 100
#./dynamodump.py -m restore -r eu-west-1 -s $DUMP_DIR/tags -d tags_restored --readCapacity 100 --writeCapacity 100

# Dump only schemas
#./dynamodump.py -m backup -r eu-west-1 -s "*" --schemaOnly --skipThroughputUpdate

# Restore only schema for single table (will also delete data in it!)
./dynamodump.py -m restore -r eu-west-1 -s images -d images --schemaOnly --skipThroughputUpdate

cd $DUMP_DIR

tar -zcvf imagepush_$FILENAME.tar.gz .

s3cmd put -f -rr --acl-public "imagepush_$FILENAME.tar.gz" "$S3BUCKET/imagepush_$FILENAME.tar.gz" 1>/dev/null
