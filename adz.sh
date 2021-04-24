#!/bin/bash

echo "Welcome to AdzWP! Where we put all the bullshit aside and start coding."
echo "Or as I like to call it, 'Opearation CWAL' (Can't Wait Any Longer)."

if [[ "$1" == "init" ]]; then
  chmod u+x adz.sh
  chmod -R u+x tools
fi

if [[ "$1" == "asset" ]]; then
  ./tools/manage-assets.sh 
fi