<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Frameset//EN' 'http://www.w3.org/TR/html4/frameset.dtd'>
<html>
  <head>
    <title>Packal Updater</title>
    <link rel='stylesheet' type='text/css' href='assets/css/normalize.css'>
    <link rel='stylesheet' type='text/css' href='assets/css/font-awesome.min.css'>
    <link href='assets/fonts/Montserrat/montserrat.css' rel='stylesheet' type='text/css'>
    <link href='assets/fonts/Source Sans Pro/source.sans.pro.css' rel='stylesheet' type='text/css'>
    <link rel='stylesheet' type='text/css' href='assets/css/style.css'>
  </head>
  <body>
    <div class='frame'>
    </div>
    <div class ='viewport'>
      <div class ='header'>
        <div class='title'><h1>Packal</h1></div>
        <nav id='bt-menu' class='bt-menu'>
          <a href='#' class='bt-menu-trigger'><span>Menu</span></a>
          <ul>
            <li><a href='#' id='status' class='nav'>Status</a></li>
            <li><a href='#' id='updates' class='nav'>Updates</a></li>
            <li><a href='#' id='settings' class='nav'>Settings</a></li>
            <li><a href='#' id='backup' class='nav'>Backups</a></li>
            <li><a href='#' id='blacklist' class='nav'>Blacklist</a></li>
            <li><a href='#' id='about' class='nav'>About</a></li>
          </ul>
        </nav>
      </div>
      <div class='pane'>
        <div class='preloader'>
          <h2>Loading...</h2>
          <img alt='preloader' src='assets/images/preloader.gif' />
        </div>
      </div>
      <div id='updating-overlay'><h2>Updating...</h2><img src='assets/images/preloader.gif'></div>
    </div>

    <script type='text/javascript' src='assets/js/jquery.min.js'></script>
    <script type='text/javascript' src='assets/js/classie.js'></script>
    <script type='text/javascript' src='assets/js/borderMenu.js'></script>
    <script type='text/javascript' src='assets/js/jquery-ui/js/jquery-ui.min.js'></script>
    <script type='text/javascript' src='assets/js/keep-alive.js'></script>
    <script type='text/javascript' >
      // Load the initial content.
      $( '.pane' ).load( 'packal.php' );
      $( '.nav' ).click( function() {
        // An menu item has been clicked, so, start the callback to get the content.
        // Add in a preloader.
        $( '.pane' ).html("<div class='preloader'><h2>Loading...</h2><img alt='preloader' src='assets/images/preloader.gif' /></div>");
        // Actually grab the content and put it into the 'pane'
        $( '.pane' ).load( 'packal.php', { page: $( this ).attr( 'id' ) } ).hide().fadeIn('fast').delay(50);
        // This just makes the menu go away when you select an option, basically duplicated from the borderMenu.js script.
        // Also, add a timeout so it's not too sudden.
        var menu = document.getElementById( 'bt-menu' );
        setTimeout( function() {
          classie.remove( menu, 'bt-menu-open' );
          classie.add( menu, 'bt-menu-close' );
        }, 250);
      });
    </script>
  </body>
</html>