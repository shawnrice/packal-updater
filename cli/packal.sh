#!/bin/bash

# important global variables
bundle="com.packal"
manifest="https://raw.github.com/packal/repository/master/manifest.xml"
path="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." && pwd -P )"

# Folders
data="$HOME/Library/Application Support/Alfred 2/Workflow Data/$bundle"
cache="$HOME/Library/Caches/com.runningwithcrayons.Alfred-2/Workflow Data/$bundle"
backups="$data/backups"
endpoints="$data/endpoints"

# Utilities
pb="/usr/libexec/PlistBuddy"

################################################################################
# Functions
################################################################################

setupDirs() {
  dir "$data"
  dir "$cache"
  dir "$backups"
  dir "$endpoints"
}

dir() {
  if [ ! -d "$1" ]; then
    mkdir "$1"
  fi
}

getManifest() {
  curl -sL "$manifest" > "$cache/manifest.xml"
  if [ `echo $?` = 0 ]; then
    if [ -f "$data/manifest.xml" ]; then
      rm "$data/manifest.xml"
      mv "$cache/manifest.xml" "$data/manifest.xml"
    else
      mv "$cache/manifest.xml" "$data/manifest.xml"
    fi
  else
    echo "Error: Server not reachable."
  fi
  echo "TRUE"

}

checkModified() {
  if [ -f "$1" ]; then
    echo `stat -f "%m" "$1"`
  else
    # File doesn't exist, so we'll just give it a 0 response.
    echo 0
  fi
}

isCacheValid() {

# Set the TTL variable from argument, fallback to 24 hours.
  if [ -z "$1" ]; then
    local $ttl = 86400
  else
    local $ttl = $1
  fi

# Check if modified within the TTL parameter
  if [ `checkModified "$data/manifest.xml"` -lt $ttl ]; then
    echo "FALSE"
  else
    echo "TRUE"
  fi
}

generateMap() {
  # Creates a map of bundles and directories.
  endpoints="$data/endpoints"

  if [ ! "$1" = "TRUE" ]; then
    if [[ ! `checkModified "$endpoints/endpoints.json"` -lt `stat -f "%m" "$path"` ]]; then
      # Nothing new has been installed, so there is no reason to update the map
      exit 0
    fi
  fi

  echo "{" > "$endpoints/endpoints.json"
  for w in "$path/"*
  do
    local bundle=`$pb -c "Print :bundleid" "$w/info.plist" 2> /dev/null`
    if [ ! -z "$bundle" ]; then
      echo "\"$bundle\": \"$w\"," >> "$endpoints/endpoints.json"
    fi
  done

  echo `cat "$endpoints/endpoints.json" | sed -e :a -e '/^\n*$/{$d;N;ba' -e '}' | sed -e '$s|,$|}|'`  > "$endpoints/endpoints.json"

  if [ -f "$endpoints/endpoints.list" ]; then
    rm "$endpoints/endpoints.list"
  fi
  for w in "$path/"*
  do
    local bundle=`$pb -c "Print :bundleid" "$w/info.plist" 2> /dev/null`
    if [ ! -z "$bundle" ]; then
      echo "\"$bundle\"=\"$w\"" >> "$endpoints/endpoints.list"
    fi
  done

}

getDir() {
  if [ ! -f "$endpoints/endpoints.list" ]; then
    generateMap
  fi

  line=`cat "$endpoints/endpoints.list" | grep "$1"`
  local bundle="$line"

  line=${line#*=}
  line=`echo $line | sed -e 's|\"||g'`

  bundle=${bundle%=*}
  bundle=`echo $bundle | sed -e 's|\"||g'`

  if [ "$bundle" = "$1" ]; then
    echo $line
  else
    echo "FALSE"
  fi

}

checkUpdate() {
  dir=`getDir "$1"`
  if [ -d "$dir/packal" ]; then
    echo "Packal WF"
  fi
}

getOpt() {
  dir=`getDir "$1"`
  echo `$pb -c "Print :$2" "$dir/info.plist"`
}

backup() {

  name=`getOpt "$1" "name"`
  dir=`getDir "$1"`
  date=`date +"%Y-%m-%d-%H.%M.%S"`

  file=`echo $date-$name.alfredworkflow | tr '&' '_' | tr ' ' '_'`
  cd "$dir"
  zip -q -r "$cache/$file" *
  cd -
  dir "$data/backups/$name"

  backups=`ls "$data/backups/$name" | grep alfredworkflow | wc -l`
  me="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd -P )"

  keep=`php "$me/cli/packal.php" getOption backups`

  
  if [[ $backups -ge $keep ]]; then
    count=$backups
    files=`ls "$data/backups/$name" | grep alfredworkflow`
    while [[ $count -gt $(( keep-1 )) ]]; do
      for f in $files; do
        if [[ $count -gt $(( keep-1 )) ]]; then
          f=`echo $f | sed 's|'\\\n'||g'`
          rm "$data/backups/$name/$f"
          count=$(( count-1 ))
        fi
      done
    done
  fi

  mv "$cache/$file" "$data/backups/$name/"

}

replaceFiles() {
  old="$1"
  new="$2"
  cd "$old"
  rm -fR *
  mv -f "$new"* .
  cd -
}

restoreBackup() {
  # Right now this function is empty.
  a=1
}

################################################################################
# Start Script
################################################################################

setupDirs

if [ "$1" = "update" ]; then
  if [ "$2" = "TRUE" ]; then
    getManifest
    generateMap TRUE
  else
    if [[ `checkModified "$data/manifest.xml"` -eq 0 ]]; then
      getManifest
    fi
    generateMap
  fi

elif [ "$1" = "getDir" ]; then
  getDir "$2"


elif [ "$1" = "checkUpdate" ]; then
  checkUpdate "$2"

elif [ "$1" = "backup" ]; then
  backup "$2"

elif [ "$1" = "replaceFiles" ]; then
  replaceFiles "$2" "$3"

else
  echo "Error: invalid command ($1)"
  exit 1

fi

exit 0
