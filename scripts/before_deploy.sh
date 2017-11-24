#!/usr/bin/env bash
# Check if commit comes from Travis.
if ["$(git log -1 $TRAVIS_COMMIT --pretty="%cE")" != "Travis CI"]
  then
    cd ../vendor/bin
    wp2md -i ../../readme.txt -o ../../README.md
    cd ../../
    git config --global user.email "travis@travis-ci.org"
    git config --global user.name "Travis CI"
    git add README.md
    git commit --message "Travis build for syncing README.md with readme.txt: $TRAVIS_BUILD_NUMBER"
    git remote add origin https://${GH_TOKEN}@github.com/florianbrinkmann/lazy-loading-responsive-images.git > /dev/null 2>&1
    git push --quiet --set-upstream origin
fi
