Packal
======

This workflow is meant to interface with the new Packal.org (staging: mellifluously.org) for all things Packal. This includes submitting and downloading and installing workflows and themes as well as updating workflows. Currently this is and will remain the only official vector for submitting workflows and themes on Packal (although, you can interact with the Packal API in other ways).

Currently, the `bundleid` for this workflow is `com.packal2`, but, when released, it will be `com.packal`, which is the original `bundleid` of the __Packal Updater__, and so the upgrade path will replace that workflow.

There is only one keyword `pac:`, but that's open to change (suggestions/critiques welcome). In order to submit to Packal.org, you will have to configure the workflow with your Packal credentials. The `username` is kept in the `settings.ini` file and the `password` is kept in the keychain, so it is secure.

The workflow will also provide a migration path from the old Packal to the new Packal.

## Submitting Workflows and Themes

There are a few differences in how you need to structure your workflows and themes in order for Packal to understand them.

### Workflows
1. Workflows need a `workflow.ini` file that will contain all the Packal information as well as the version information. `workflow.ini` needs to live next to `info.plist`
> a convenient dialog will help you generate this file
2. Versions need to be in semantic versioning.
3. Long descriptions should be included in a `README.md` file. Obviously, Markdown is enabled. This change also mirrors a future change in which Alfred will do the same with its README section.
4. Screnshots need to be in a folder called `screenshots` that live next to `info.plist`.
5. Workflows are automatically built with a custom build script that also removes common development files across multiple languages.
6. The `author` (`createdby`) field in the workflow needs to match your Packal username.
7. All possible fields are pulled from the `info.plist` file.
8. You cannot submit the same `version` twice. So, you need to update the version for everything that you update. Currently, the server and the workflow will not reject submitting the same version, but this functionality is simply commented out to make other forms of testing easier.

### Themes
1. The `author` (`createdby`) needs to match your Packal username.
2. When you submit a theme, the metadata (description and tags) are added with a convenient little dialog. If updating a theme, then the previous information that has been submitted is saved in a `.json` file and repopulated.
3. Markdown is enabled in the `description` field.
4. Screenshots are no longer necessary because they are automatically generated via some js and css on the server side.

## Updating on the client side


~~So, like, if you're not me, then this won't work at all because it relies on a local dev install.~~

~~Be patient, it will work for everyone in a testing state soon, but the updated server needs to go to __testing__ at the minimum. Right now this branch exists just to save some work outside of my computer, and the code is, like, total crap and doesn't fully work, even in an expected buggy state.~~

Currently, this mostly works. It still needs more testing. The server that the this workflow connects to is defined based on the `environment.txt` file. Here are the values:

```php
	$environments = [
		'development' => 'http://localhost:3000', // Local Passenger Server (works on my local dev)
		'dev-staging' => 'http://packal.dev', 		// Local nginx proxying to Passenger (works on my local dev)
		'staging' => 'https://mellifluously.org', // Staging Server
		'production' => 'https://www.packal.org', // Actual Production (not setup yet)
	];
```

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