#!/bin/sh

# you can change "php54" to "php" or other, which you have
# check "whereis php"
phpexec="php"
docset="Yii2.docset/Contents/Resources/Documents/"

runpath="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo "Run path: $runpath"

cd "$runpath"