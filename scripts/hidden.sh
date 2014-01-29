showfiles=`defaults read com.apple.Finder AppleShowAllFiles`
if [ "$showfiles" = "TRUE" ]; then
    say Hidden items disabled
    defaults write com.apple.finder AppleShowAllFiles FALSE
else
    say Hidden items enabled
    defaults write com.apple.finder AppleShowAllFiles TRUE	
fi
