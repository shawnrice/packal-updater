#!/bin/bash

# This installs the check updates script into Alfred Cron
# alfred.cron.spr

bundle="alfred.cron.spr"

data="$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle"
cache="$HOME/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle"
scriptDir="$data/scripts"
enabledScriptDir="$data/scripts/enabled"

name='packal_updater'

head='#!/bin/bash\nset -o errexit'
interval='86400'

dir=`cat "$HOME/Library/Application Support/Alfred 2/Workflow Data/com.packal/endpoints/endpoints.list"|grep '"com.packal"='`
dir=`echo "$dir"|tr '"' ' '`
dir=`echo ${dir# com.packal = }`
command="#!/bin/bash
set -o errexit

php '$dir/assets/alfred.cron.script.php'"

if [[ ! "$command" =~ "#!/bin/bash" ]]; then
	command=`echo "#!/bin/bash\nset -o errexit\n$command"`
fi
echo "$command" > "$scriptDir/$name"

# Delete an job if the name is already in there. This error should already
# have been accounted for.
awk '!/'"$name"'/' "$data/registry" > "$cache/registry" && mv "$cache/registry" "$data/registry"
echo "$name"="$interval" >> "$data/registry"

# Enable the script
ln "$scriptDir/$name" "$enabledScriptDir/$name"