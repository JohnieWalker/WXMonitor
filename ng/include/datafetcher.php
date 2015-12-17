<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function file_load($type = 'metar'){
    $remote_file = 'http://weather.aero/dataserver1_5/cache/'.$type.'s.cache.xml.gz';
    $local_file = $type.'.xml';
    $dt = NULL;
    
    if (file_exists($local_file)){
        $h = get_headers($remote_file, 1);
        if (!(strstr($h[0], '200') === FALSE)) {
            $dt = new \DateTime($h['Last-Modified']);
            if($dt->getTimestamp() - filemtime($local_file) < 300){
                echo "Remote : ".$dt->getTimestamp()." VS Local: "
                .filemtime($local_file)." = "
                .($dt->getTimestamp() - filemtime($local_file))."<br>";
                $xml = simplexml_load_file($local_file);
            }else{
                echo "Updating local file";
                $data = file_get_contents('compress.zlib://'.$remote_file);
                if(file_put_contents($local_file, $data)){
                    $xml = simplexml_load_file($local_file);
                }
            }
            //echo "R - ".$dt->getTimestamp()." - "
            //.$dt->format('F d Y H:i:s')."<br/>";
        }
    }else{
        $data = file_get_contents('compress.zlib://'.$remote_file);
            if(file_put_contents($local_file, $data)){
                $xml = simplexml_load_file($local_file);
            }
    }
    
    return $xml;
}    


$xml = file_load();
$nodes = $xml->xpath("data/METAR[icao_code='OMAA']"); 
print_r($nodes);