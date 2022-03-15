#set -x

VERSION=$(grep -o '^ *"version": *"[0-9\.]*"' ../composer.json | awk '{print $2}' | sed -e 's/"\(.*\)"/\1/g')
WD=$(pwd)

# Create package update payload
ARCH_NAME=code.zip
cd ..
zip -r $ARCH_NAME ./*

# Upload package update to Marketplace
cd "${WD}"
git clone git@github.com:PowerSync/TNW_EQP.git eqp --branch main
[ -f .env ] && cp .env eqp
mv ../$ARCH_NAME eqp
cp -r package/data eqp
cd eqp
bin/main $ARCH_NAME $VERSION 0
RESULT=$?
rm $ARCH_NAME
exit $RESULT
