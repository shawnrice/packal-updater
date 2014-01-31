import plistlib
import urllib2
# import alp
import xml.etree.ElementTree as ET

# Get latest manifest.xml from Github
request = urllib2.Request('https://api.github.com/repos/packal/repository/contents/manifest.xml')

# Headers only
request.get_method = lambda : 'HEAD'

# Only if manifest has changed
request.add_header('If-Modified-Since', 'Thu, 30 Jan 2014 15:29:17 GMT')

# Send request
try:
	response = urllib2.urlopen(request)
	for header in response.info().headers:
		if header.startswith('Last-Modified'):
			header = header.replace('Last-Modified:', '')
			header = header.strip()
			print header
except:
	print 'Denied!'
	pass

# Return workflows
