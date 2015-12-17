<?
error_reporting(E_ALL);
ini_set('display_errors', '1');
//$data = file_get_contents('http://db8.flightradar24.com/zones/europe_all.js');
$data = file_get_contents('http://arn.data.fr24.com/zones/fcgi/feed.js?bounds=63.875010665617644,26.825963383539968,-53.84281640624931,99.404296875&faa=1&mlat=1&flarm=1&adsb=1&gnd=1&air=1&vehicles=1&estimated=1&maxage=900&gliders=1&stats=1&selected=5ca23b5&');
//$data = str_replace( 'pd_callback(', '', $data );
//$data = substr( $data, 0, strlen( $data ) - 2 ); //strip out last paren
$object = json_decode( $data ); // stdClass object
$array = (array)$object;
print_r($array);
//echo "<pre>";
//print_r($array);
$geojson = array( 'type' => 'FeatureCollection', 'features' => array());
foreach($array as $key => $row) {
   //if(empty($row['16'])) unset($array[$key]);
   if(strpos($row[16],'GWI') !== false){
       $feature = array( 'type' => 'Feature', 
        'geometry' => array( 'type' => 'Point', 
        'coordinates' => array((float)$row[2], (float)$row[1]) ), 
        'properties' => array( 'name' => $row[16], 'externalGraphic' => 'http://gwi.lidousers.com/plane.php?deg='.$row[3])
        );
        array_push($geojson['features'], $feature);
   }else{
       unset($array[$key]);
   }
}
header("Content-Type:application/json",true);
echo json_encode($geojson);

