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

cd "$SCRIPT_DIR/src"

printf "Remove old archives\n"
find . -maxdepth 1 ! -type d -exec rm -rf {} \;
printf "Done.\n"

OLD_ARCHIVE_SRC=$(ls -d *|head -n 1)
OLD_ARCHIVE_SRC="$SCRIPT_DIR/src/$OLD_ARCHIVE_SRC"

####################
# UPSTREAM PULLING #
####################
echo "PULLING UPSTREAM..."
git clone $GIT_REPOSITORY ./git && cd ./git
git checkout $GIT_BRANCH

#Find last tag for version number
VERSION_NUMBER=`git tag | sort -V | tail -1`
VERSION_MESSAGE=`git tag -n1000 $VERSION_NUMBER`

if [[ $VERSION_NUMBER == v* ]]
then
    VERSION_NUMBER=${VERSION_NUMBER:1}
fi

#Copy .git to appropriate dir
PACKAGE_NAME_V="$PACKAGE_NAME-$VERSION_NUMBER"
PACKAGE_NAME_V_ORI="$PACKAGE_NAME"'_'"$VERSION_NUMBER"
PACKAGE_DIR="$SCRIPT_DIR/src/$PACKAGE_NAME-$VERSION_NUMBER"

#Check file not already exists
if [ -d "$PACKAGE_DIR" ]
then
    printf "This version of package already exists : $PACKAGE_DIR\n"
    cd ../
    rm -rf ./git
    exit 0
fi

cd .. ; cp -r git $PACKAGE_NAME_V ; rm -rf git

#Change to new archive dir
printf "Changedir to new archive dir : $PACKAGE_NAME_V"
cd "$PACKAGE_NAME_V"

#Remove .git files
rm -rf .git*
printf "Done.\n"

#Copy old debian dir into new one
printf "Copy old debian dir into new archive\n"
cp -r "$OLD_ARCHIVE_SRC/debian" "./debian"
printf "Done.\n"


#Remove old archive dir
printf "Remove old archive dir"
rm -rf "$OLD_ARCHIVE_SRC"
printf "Done.\n"


#Update changelog
printf "Update changelog\n"
printf "OLD >>>>>>>>>>>>\n"
cat "./debian/changelog"
printf "<<<<<<<<<<<<<<<<\n"

dch -v "$VERSION_NUMBER" "$VERSION_MESSAGE"
dch -r "$VERSION_MESSAGE"

printf "NEW >>>>>>>>>>>>\n"
cat "./debian/changelog"
printf "<<<<<<<<<<<<<<<<\n"


#Create archive
printf "Create .tar.gz archive\n"
cd ../..
tar --exclude-vcs -C "./src" -zcvf "./src/$PACKAGE_NAME_V.tar.gz" "./$PACKAGE_NAME_V"
ln -s "$SCRIPT_DIR/src/$PACKAGE_NAME_V.tar.gz" "$SCRIPT_DIR/src/$PACKAGE_NAME_V_ORI.orig.tar.gz"
printf "Done.\n"

#Generate deb archive
printf "Generate .deb\n"
cd "./src/$PACKAGE_NAME_V"
echo `pwd`
dpkg-buildpackage -us -uc
printf "Done.\n"

#Copy deb files to final dir
$SCRIPT_DIR/move_packages.sh
