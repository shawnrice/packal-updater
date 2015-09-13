Packal
======

~~So, like, if you're not me, then this won't work at all because it relies on a local dev install.~~

~~Be patient, it will work for everyone in a testing state soon, but the updated server needs to go to __testing__ at the minimum. Right now this branch exists just to save some work outside of my computer, and the code is, like, total crap and doesn't fully work, even in an expected buggy state.~~

Currently, this mostly works. It still needs more testing. The server that the this workflow connects to is defined based on the `environment.txt` file. Here are the values:
````php
$environments = [
	'development' => 'http://localhost:3000', // Local Passenger Server
	'dev-staging' => 'http://packal.dev', 		// Local nginx proxying to Passenger
	'staging' => 'https://mellifluously.org', // Staging Server
	'production' => 'https://www.packal.org', // Actual Production (not setup yet)
];
````

If you want to use this, then make sure that `environment.txt` reads as `staging`. Otherwise, it won't work.

There is no GUI yet. I might include that. I might not. Cheers.

If you're interested in my own bitchy comments to myself, then the source code might be entertaining to read through, but I don't recommend it.

-- Shawn

## Functionality

* Search, Download, Install {workflows, themes}.
* Submit {Themes, Workflows, Reports}.
  * Generate workflow.ini files.
  * Generate theme config files.
  * Actually submit things
* Updates
  * Blacklist
  * Ignore own workflows
  * Migrate from old Packal