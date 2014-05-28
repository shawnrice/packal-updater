<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
  <head>
    <title>Packal Updater</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="assets/css/normalize.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.min.css">
    <link href="assets/fonts/Montserrat/montserrat.css" rel='stylesheet' type='text/css'>
    <link href='assets/fonts/Source Sans Pro/source.sans.pro.css' rel="stylesheet" type="text/css">

    </head>
    <body>
      <div class='frame'>
      </div>
      <div class ='viewport'>
        <div class ='header'>
          <div class='title'><h1>Packal</h1></div>
          <nav id="bt-menu" class="bt-menu">
            <a href="#" class="bt-menu-trigger"><span>Menu</span></a>
            <ul>
              <li><a href="#" id='updates' class="nav bt-icon icon-sun">Updates</a></li>
              <li><a href="#" id='status' class="nav bt-icon icon-windows">Status</a></li>
              <li><a href="#" id='blacklist' class="nav bt-icon icon-speaker">Blacklist</a></li>
              <li><a href="#" id='backup' class="nav bt-icon icon-star">Backups</a></li>
              <li><a href="#" id='settings' class="nav bt-icon icon-bubble">Settings</a></li>
              <li><a href="#" id='about' class="nav bt-icon icon-user-outline">About</a></li>
            </ul>
          </nav>
        </div>
        <div class='pane'>
          <div class='preloader'>
            <h2>Loading...</h2>
            <img alt='preloader' src='assets/images/preloader.gif' />
          </div>
        </div>
      </div>
      <div id='updating-overlay'><h2>Updating...</h2><img src='assets/images/preloader.gif'></div>
      <script type='text/javascript' src="assets/js/jquery.min.js"></script>
      <script type='text/javascript' src="assets/js/classie.js"></script>
      <script type='text/javascript' src="assets/js/borderMenu.js"></script>
      <script type='text/javascript' src='assets/js/jquery-ui/js/jquery-ui.min.js'></script>
      <script type='text/javascript' src="assets/js/keep-alive.js"></script>
      <script type='text/javascript' >
      $( ".pane" ).load( "packal.php" );
      $( '.nav' ).click( function() {
        $( '.pane' ).html("<div class='preloader'><h2>Loading...</h2><img alt='preloader' src='assets/images/preloader.gif' /></div>");
        $( '.pane' ).load( 'packal.php', { page: $( this ).attr( 'id' ) } ).hide().fadeIn('fast').delay(50);
      });      
      </script>
    </body>
  </html>