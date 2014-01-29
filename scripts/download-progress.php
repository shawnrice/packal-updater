<?php
file_put_contents( 'progress.txt', '' );
 
$targetFile = fopen( 'testfile.iso', 'w' );
 
$ch = curl_init( 'http://ftp.free.org/mirrors/releases.ubuntu-fr.org/11.04/ubuntu-11.04-desktop-i386-fr.iso' );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt( $ch, CURLOPT_NOPROGRESS, false );
curl_setopt( $ch, CURLOPT_PROGRESSFUNCTION, 'progressCallback' );
curl_setopt( $ch, CURLOPT_FILE, $targetFile );
curl_exec( $ch );
fclose( $ch );
 
function progressCallback( $download_size, $downloaded_size, $upload_size, $uploaded_size )
{
    static $previousProgress = 0;
    
    if ( $download_size == 0 )
        $progress = 0;
    else
        $progress = round( $downloaded_size * 100 / $download_size );
    
    if ( $progress > $previousProgress)
    {
        $previousProgress = $progress;
        $fp = fopen( 'progress.txt', 'a' );
        fputs( $fp, "$progress\n" );
        fclose( $fp );
    }
}
 
?>