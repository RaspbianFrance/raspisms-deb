#!/bin/bash

#Import conf
. ./package.conf

trap error_report ERR

### Functions ###
error_report () {
    printf "\n\nError on line $(caller)\n\n";
    exit 1
}

#############
### CONST ###
#############
SCRIPT_DIR=$(dirname $(readlink -f $0))
echo $SCRIPT_DIR

PACKAGES_DIR="$SCRIPT_DIR/../packages"

#############
### CLEAN ###
#############
echo "Copy dsc, changes and deb files to $PACKAGES_DIR..."
cp $SCRIPT_DIR/src/*.deb "$PACKAGES_DIR"
cp $SCRIPT_DIR/src/*.dsc "$PACKAGES_DIR"
cp $SCRIPT_DIR/src/*.changes "$PACKAGES_DIR"
printf "Done.\n\n"

echo "Remove old latest."
rm -f "$PACKAGES_DIR/latest*"
echo "Done."

echo "Copy dsc, changes and deb files to $PACKAGES_DIR..."
cp $SCRIPT_DIR/src/*.deb "$PACKAGES_DIR/latest.deb"
cp $SCRIPT_DIR/src/*.dsc "$PACKAGES_DIR/latest.dsc"
cp $SCRIPT_DIR/src/*.changes "$PACKAGES_DIR/latest.changes"
printf "Done.\n\n"

echo "Remove current generated deb files..."
find "$SCRIPT_DIR/src/" -mindepth 1 -maxdepth 1 -type f,l -exec rm -f {} \;

