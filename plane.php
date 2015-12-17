<?php
ob_start();
$filename       = './img/plane.png';
$degrees        = 360-$_GET['deg'];
$im = imagecreatefrompng( $filename );
$transparency = imagecolorallocatealpha( $im,0,0,0,127 );
$rotated = imagerotate( $im, $degrees, $transparency, 1);
imagealphablending( $rotated, false );
imagesavealpha( $rotated, true );
ob_end_clean();
header( 'Content-Type: image/png' );
imagepng( $rotated );
imagedestroy( $im );
imagedestroy( $rotated );
?>