from twisted.internet import reactor
from twisted.web.server import Site

from twisted.web.twcgi import FilteredScript

class PhpPage(FilteredScript):
    filter = "/usr/bin/php"
    #                  ^^^^^^^

    # deal with cgi.force_redirect parameter by setting it to nothing.
    # you could change your php.ini, too.
    def runProcess(self, env, request, qargs=[]):
        env['REDIRECT_STATUS'] = ''
        return FilteredScript.runProcess(self, env, request, qargs)

resource = PhpPage('./gui/index.php')
factory = Site(resource)

reactor.listenTCP(8880, factory)
reactor.run()