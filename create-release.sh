#!/usr/bin/env sh
rm -f dist/*
tar --exclude='dist' --exclude='.git' --exclude='.idea' \
  -czf dist/hedgenext.tar.gz --transform 's,^,hedgenext/,' *

echo "Signature:"
openssl dgst -sha512 -sign $NC_CERT dist/hedgenext.tar.gz | openssl base64

echo -e "\nNow visit:\nhttps://apps.nextcloud.com/developer/apps/releases/new"

