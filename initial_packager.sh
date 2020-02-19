#!/bin/bash

#Import conf
. ./package.conf

trap error_report ERR

### Functions ###
error_report () {
    printf "\n\nError on line $(caller)\n\n";
    exit 1
}

uncomment_line () {
    local file=$1
    local line_start=$2
    sed -i "s/^#$line_start: \(.*\)/$line_start: \1/g" "$file"
}

create_line_or_replace () {
    local file=$1
    local line_start=$2
    local replace=$3

    #check if line exists
    grep -q "^$line_start: .*" "$file"

    if [ $? -eq 0 ]
    then
        printf "    Update value of $line_start to \"$replace\"\n";
        sed -i "s/^$line_start: .*/$line_start: ${replace//\//\\/}/g" "$file"
    else
        printf "    Add $line_start with value \"$replace\"\n";
        echo "$line_start: $replace" >> "$file"
    fi
}

create_line_or_append () {
    local file=$1
    local line_start=$2
    local append=$3

    #check if line exists
    grep -q "^$line_start: .*" "$file"
    
    if [ $? -eq 0 ]
    then
        printf "    Append \"$append\" to $line_start\n";
        sed -i "s/^$line_start: \(.*\)/$line_start: \1, ${append//\//\\/}/g" "$file"
    else
        printf "    Add $line_start with value \"$append\"\n";
        echo "$line_start: $append" >> $file
    fi
}

#############
### CONST ###
#############
SCRIPT_DIR=$(dirname $(readlink -f $0))
echo $SCRIPT_DIR

#############
### CLEAN ###
#############
echo "CLEANING..."

#Delete and remake sourcecode directory
rm -rf ./src ; mkdir ./src

#Delete *.tar.gz
rm -f *.tar.gz

printf "OK.\n\n"



####################
# UPSTREAM PULLING #
####################
echo "PULLING UPSTREAM..."

git clone $GIT_REPOSITORY ./src/git && cd ./src/git
git checkout $GIT_BRANCH

#Find last tag for version number
VERSION_NUMBER=`git tag | tail -1`
VERSION_MESSAGE=`git tag -n1000 $VERSION_NUMBER`

if [[ $VERSION_NUMBER == v* ]]
then
    VERSION_NUMBER=${VERSION_NUMBER:1}
fi

#Copy .git to appropriate dir
PACKAGE_NAME_V="$PACKAGE_NAME-$VERSION_NUMBER"
cd .. ; cp -r git $PACKAGE_NAME_V ; rm -rf git ; cd $PACKAGE_NAME_V

#Remove .git files
rm -rf .git*

#Create archive
cd ../..
tar --exclude-vcs -C "./src" -zcvf "./src/$PACKAGE_NAME_V.tar.gz" "./$PACKAGE_NAME_V"

printf "OK.\n\n"

#################
# .DEB CREATION #
#################
echo "STARTING DEB CREATION..."

cd "./src/$PACKAGE_NAME_V"

#Generate global debian package structure
if [ $LICENSE == 'custom' ]
then
    dh_make --single --packagename="$PACKAGE_NAME" --email="$DEBEMAIL"  --copyright="$LICENSE" --copyrightfile="$CUSTOM_LICENSE" --yes -f "../$PACKAGE_NAME_V.tar.gz"
else
    dh_make --single --packagename="$PACKAGE_NAME" --email="$DEBEMAIL"  --copyright="$LICENSE" --yes -f "../$PACKAGE_NAME_V.tar.gz"
fi

#Go in generated debian package structure
cd debian


#Update Control file
printf "Update control file...\n"

#Delete lines starting with a space
sed -i '/^ .*/d' ./control
create_line_or_replace "./control" "Section" "$CONTROL_SECTION"
create_line_or_replace "./control" "Homepage" "$CONTROL_HOMEPAGE"
create_line_or_replace "./control" "Architecture" "$CONTROL_ARCHITECTURE"

uncomment_line "./control" "Vcs-Git"
create_line_or_replace "./control" "Vcs-Git" "$GIT_REPOSITORY"
uncomment_line "./control" "Vcs-Browser"
create_line_or_replace "./control" "Vcs-Browser" "$GIT_REPOSITORY"

create_line_or_replace "./control" "Description" "$CONTROL_DESCRIPTION_SHORT\n$CONTROL_DESCRIPTION_LONG"

create_line_or_append "./control" "Pre-Depends" "$CONTROL_PRE_DEPENDS"
create_line_or_append "./control" "Depends" "$CONTROL_DEPENDS"
create_line_or_append "./control" "Recommends" "$CONTROL_RECOMMENDS"


printf "Done.\n\n"


#Update Copyright file
printf "Update copyright file...\n"
create_line_or_replace "./copyright" "Source" "$GIT_REPOSITORY"
create_line_or_replace "./copyright" "Copyright" "$COPYRIGHT_DATE $COPYRIGHT_AUTHOR_NAME <$COPYRIGHT_AUTHOR_EMAIL>"

#Remove useless author
sed -i "/           <years> <likewise for another author>/d" ./copyright

#Delete commented lines
sed -i '/^#.*/d' ./copyright

printf "Done.\n\n"


#Update changelog file
printf "Update changelog file...\n"
CHANGELOG_HEAD=`head -n1 ./changelog`
CHANGELOG_TAIL=`tail -n1 ./changelog`

printf "$CHANGELOG_HEAD\n\n$CHANGELOG_MESSAGE\n\n$CHANGELOG_TAIL\n" > ./changelog

printf "OK.\n\n"


#Create manpages
#cp "$SCRIPT_DIR/files/manpage.1" "./$PACKAGE_NAME.1"
#echo "docs/$PACKAGE_NAME.1" > "./$PACKAGE_NAME.manpages"


#Copy files
cp -r $SCRIPT_DIR/files/* .



#Remove useless files
rm ./README.Debian
rm ./README.source
rm ./*.ex
rm ./*.EX
rm ./"$PACKAGE_NAME"-doc*



