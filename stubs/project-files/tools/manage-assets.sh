#!/bin/bash

echo "What would you like to do?"
echo "Either: `use` or `unuse`."
read action

echo "Input library or module:"
echo "Available for now: `wp-gatsby`."
read module

echo "Input Environment:"
read env

if [[ -z"$env" && "$action" == "use"  && "$module" == "wp-gatsby"]]; then
cp "./adz/lib/reactjs/gatsby" "./src/assets/"
fi