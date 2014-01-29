// http://www.automatedworkflows.com/2012/08/26/display-notification-center-alert-automator-action-1-0-0/
// https://github.com/alloy/terminal-notifier
// http://osxdaily.com/2012/08/03/send-an-alert-to-notification-center-from-the-command-line-in-os-x/
// https://github.com/Daij-Djan/DDMountainNotifier

// Check for Growl

// ps aux | grep Growl | grep -v grep






set myApp to quoted form of "/Users/Sven/Desktop/notify.app"
set myPath to quoted form of "/Users/Sven/Dropbox/Kaitlyn-Shawn-Exchange/Shawn PSF Data Display.html"

--- set myPath to "http://localhost/~Sven/configs/textexpander/"

do shell script "open " & myApp & " --args " & myPath
set myApp to quoted form of "/Users/Sven/Desktop/notify.app"
set myArgs to "-D title='Title text' -D subtitle='Subtitle text' -D message='Message text'"
--- repeat the command to make the window in focus… strange, I know.
do shell script "open " & myApp & " --args " & myArgs

