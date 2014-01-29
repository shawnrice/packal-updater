Command Format:
    Help - Prints this information
    Exit - Exits the program, changes are not saved to the file
    Save - Saves the current changes to the file
    Revert - Reloads the last saved version of the file
    Clear [<Type>] - Clears out all existing entries, and creates root of Type
    Print [<Entry>] - Prints value of Entry.  Otherwise, prints file
    Set <Entry> <Value> - Sets the value at Entry to Value
    Add <Entry> <Type> [<Value>] - Adds Entry to the plist, with value Value
    Copy <EntrySrc> <EntryDst> - Copies the EntrySrc property to EntryDst
    Delete <Entry> - Deletes Entry from the plist
    Merge <file.plist> [<Entry>] - Adds the contents of file.plist to Entry
    Import <Entry> <file> - Creates or sets Entry the contents of file

Entry Format:
    Entries consist of property key names delimited by colons.  Array items
    are specified by a zero-based integer index.  Examples:
        :CFBundleShortVersionString
        :CFBundleDocumentTypes:2:CFBundleTypeExtensions

Types:
    string
    array
    dict
    bool
    real
    integer
    date
    data

Examples:
    Set :CFBundleIdentifier com.apple.plistbuddy
        Sets the CFBundleIdentifier property to com.apple.plistbuddy
    Add :CFBundleGetInfoString string "App version 1.0.1"
        Adds the CFBundleGetInfoString property to the plist
    Add :CFBundleDocumentTypes: dict
        Adds a new item of type dict to the CFBundleDocumentTypes array
    Add :CFBundleDocumentTypes:0 dict
        Adds the new item to the beginning of the array
    Delete :CFBundleDocumentTypes:0 dict
        Deletes the FIRST item in the array
    Delete :CFBundleDocumentTypes
        Deletes the ENTIRE CFBundleDocumentTypes array



