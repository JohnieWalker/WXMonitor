<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

include "phpweather.php";
include "config.php";

date_default_timezone_set('UTC');

mysql_connect($mysql_host, $mysql_user, $mysql_password);
mysql_select_db($mysql_db);

/**
 * Does as named, colorizes the output of METAR/TAF
 *
 * @param $target
 * @param $raw
 * @param string $color
 * @return mixed
 */
function colorize($target, $raw, $color = 'yellow')
{
    return str_replace($target, '<span style="color:' . $color . ';font-weight:bold;">' . $target . '</span>',
        $raw);

}


/**
 * Retrieves METAR/TAF XML from weather.aero\, returns SimpleXMLElement
 *
 * @param string $type `metars` or `tafs`
 * @param int $altn Accept `0` for destinations, `1` for alternates
 * @return SimpleXMLElement
 */
function getWeatherXml($type = 'metars', $altn = 0)
{
    $stations_string = '';
    $stations = mysql_query('SELECT * FROM german_wings_stations where alternate = ' . $altn . ' and active = 1');
    while ($row = mysql_fetch_assoc($stations)) {
        $stations_string .= $row['station'] . ',';
    }
         
           
    return simplexml_load_file('http://aviationweather.gov/adds/dataserver_current/httpparam' .
        '?dataSource=' . $type . '&requestType=retrieve&format=xml&mostRecentForEachStation=true&' .
        'hoursBeforeNow=3&stationString=' . $stations_string);
        
    
}

/**
 * Gets airports and their settings (destinations or alternates) from DB
 *
 * @param int $altn Accept `0` for destinations, `1` for alternates
 * @return string
 */
function getAirportArray($altn = 0)
{
    $ap_array = '';
    $stations = mysql_query('SELECT * FROM german_wings_stations where alternate = ' . $altn . ' and active = 1');
    while ($row = mysql_fetch_assoc($stations)) {
        $ap_array[$row['station']] = array(
            '2_vis' => $row['2_vis'],
            '2_ceil' => $row['2_ceil'],
            '2_wind' => $row['2_wind'],
            '3_vis' => $row['3_vis'],
            '3_ceil' => $row['3_ceil'],
            '3_wind' => $row['3_wind'],
        );
    }
    return $ap_array;
}

/**
 * Gets Severe Weather setting from DB for yellow and red alerts.
 *
 * @return array
 */
function getSevereWxSettings()
{
    $severe_wx = array();
    $query = mysql_query('SELECT * FROM german_wings_settings where `setting` = "sig_wx_y" OR `setting` = "sig_wx_r" ORDER BY `setting` DESC LIMIT 2');
    while ($row = mysql_fetch_assoc($query)) {
        if ($row['setting'] == 'sig_wx_y') {
            $severe_wx['yellow'] = explode(' ', $row['value']);
        } elseif ($row['setting'] == 'sig_wx_r') {
            $severe_wx['red'] = explode(' ', $row['value']);
        }
    }
    return $severe_wx;
}

/**
 * Users getWeatherXML to retrieve METARs info, then parses it and returns array
 *
 * @param int $altn Accept `0` for destinations, `1` for alternates
 * @return array
 */
function getMetars($altn = 0)
{
    $metars = array();
    $wx = new phpweather();
    $xml = getWeatherXml('metars', $altn);
    //print_r($xml);
    $ap_array = getAirportArray($altn);
    $severe_wx = getSevereWxSettings();
    foreach ($xml->data->METAR as $metar) {
        $metCondition = 1;
        //print_r($metar);
        $valid = 1;
        $station_id = (string)$metar->station_id;
        $observe_time = strtotime($metar->observation_time);
        $decoded_metar = $wx->decode_metar($metar->raw_text);
        //print_r($decoded_metar);
        if (time() - $observe_time > 3900) {
            $valid = 0;
        }
        if (isset($decoded_metar['wind']['knots'])) {
            if ($ap_array[$station_id]['2_wind'] > 0) {
                $wind_yellow = $ap_array[$station_id]['2_wind'];
                $wind_red = $ap_array[$station_id]['3_wind'];
            } else {
                $wind_yellow = 30;
                $wind_red = 45;
            }
            if (isset($decoded_metar['wind']['gust_knots'])) {
                $knots = $decoded_metar['wind']['gust_knots'];
            } else {
                $knots = $decoded_metar['wind']['knots'];
            }
            if ($knots >= $wind_yellow && $knots < $wind_red) {
                $metCondition = 2;
                $decoded_metar['metar'] = colorize($decoded_metar['wind']['coded'], $decoded_metar['metar']);
            } elseif ($knots >= $wind_red) {
                $metCondition = 3;
                $decoded_metar['metar'] = colorize($decoded_metar['wind']['coded'], $decoded_metar['metar'], 'red');
            }
        }
        if (isset($decoded_metar['clouds'])) {
            foreach ($decoded_metar['clouds'] as $clouds) {
                if ($clouds['condition'] == 'BKN' || $clouds['condition'] == 'OVC' || $clouds['condition'] == 'VV') {
                    if ($clouds['ft'] < $ap_array[$decoded_metar['icao']]['2_ceil']) {
                        if ($clouds['ft'] < $ap_array[$decoded_metar['icao']]['3_ceil']) {
                            $metCondition = 3;
                            $decoded_metar['metar'] = colorize($clouds['coded'], $decoded_metar['metar'], 'red');
                        } else {
                            $metCondition = 2;
                            $decoded_metar['metar'] = colorize($clouds['coded'], $decoded_metar['metar']);
                        }
                    }
                }
            }
        }

        if (isset($decoded_metar['visibility'])) {
            foreach ($decoded_metar['visibility'] as $vis) {
                if ($vis['meter'] < $ap_array[$decoded_metar['icao']]['2_vis']) {
                    if ($vis['meter'] < $ap_array[$decoded_metar['icao']]['3_vis']) {
                        $metCondition = 3;
                        $decoded_metar['metar'] = colorize($vis['coded'], $decoded_metar['metar'], 'red');
                    } else {
                        if ($metCondition == 1) {
                            $metCondition = 2;
                        }
                        $decoded_metar['metar'] = colorize($vis['coded'], $decoded_metar['metar']);
                    }
                }
            }
        }
        if (isset($decoded_metar['weather'])) {
            foreach ($decoded_metar['weather'] as $sigwx) {
                if (in_array($sigwx['coded'], $severe_wx['yellow'])) {
                    if ($metCondition == 1) {
                        $metCondition = 2;
                    }
                    $decoded_metar['metar'] = colorize($sigwx['coded'], $decoded_metar['metar']);
                }
                if (in_array($sigwx['coded'], $severe_wx['red'])) {
                    $metCondition = 3;
                    $decoded_metar['metar'] = colorize($sigwx['coded'], $decoded_metar['metar'], 'red');
                }
            }
        }

        if ($valid == 0) {
            $display = "METAR IS NOT VALID<br/><br/>" . (string)$decoded_metar['metar'];
        } else {
            $display = (string)$decoded_metar['metar'];
        }

        /**
         * raw - colorized METAR for display
         * metCondition - Meteorological condition: 1 - green, 2 - yellow, 3 - red
         * valid - if METAR is valid or not (according to time)
         * lat, lon - latitude and longitude in WSG84 coordinates format
         */
        $metars[$station_id] = array('station_id' => $station_id,
            'raw' => $display,
            'metCondition' => $metCondition,
            'valid' => $valid,
            //'lat' => floatval(trim($metar->latitude)),
            //'lon' => floatval(trim($metar->longitude))
            'lat' => ($station_id == 'DTNH' ? '36.075833' : floatval(trim($metar->latitude))),
            'lon' => ($station_id == 'DTNH' ? '10.438611' : floatval(trim($metar->longitude)))

        );
        
    }
    
    return $metars;
}

/**
 * Uses getWeatherXML to retrieve TAF info, then parses it and returns array
 *
 * @param int $altn
 * @return array
 */
function getTafs($altn = 0)
{
    $tafs = array();
    $wx = new phpweather();
    $xml = getWeatherXml('tafs', $altn);
    $ap_array = getAirportArray($altn);
    $severe_wx = getSevereWxSettings();
    foreach ($xml->data->TAF as $taf) {
        $metCondition = 1;
        $decoded_taf = $wx->decode_taf($taf->raw_text);
        $station_id = (string)$taf->station_id;
        foreach ($decoded_taf['periods1'] as $period) {
            if (isset($period['desc']['clouds'])) {
                foreach ($period['desc']['clouds'] as $clouds) {
                    if ($clouds['condition'] == 'BKN' || $clouds['condition'] == 'OVC' || $clouds['condition'] == 'VV') {
                        if ($clouds['ft'] < $ap_array[$station_id]['2_ceil']) {
                            if ($clouds['ft'] < $ap_array[$station_id]['3_ceil']) {
                                $metCondition = 3;
                                $decoded_taf['taf'] = colorize($clouds['coded'], $decoded_taf['taf'], 'red');
                            } else {
                                if ($metCondition == 1) {
                                    $metCondition = 2;
                                }
                                $decoded_taf['taf'] = colorize($clouds['coded'], $decoded_taf['taf']);
                            }
                        }

                    }
                }
            }
            if (isset($period['desc']['visibility'])) {
                foreach ($period['desc']['visibility'] as $vis) {
                    if ($vis['meter'] < $ap_array[$station_id]['2_vis']) {
                        if ($vis['meter'] < $ap_array[$station_id]['3_vis']) {
                            $metCondition = 3;
                            $decoded_taf['taf'] = colorize($vis['coded'], $decoded_taf['taf'], 'red');
                        } else {
                            if ($metCondition == 1) {
                                $metCondition = 2;
                            }
                            $decoded_taf['taf'] = colorize($vis['coded'], $decoded_taf['taf']);
                        }
                    }
                }
            }
            if (isset($period['desc']['weather'])) {
                foreach ($period['desc']['weather'] as $sigwx) {
                    if (in_array($sigwx['coded'], $severe_wx['yellow'])) {
                        if ($metCondition == 1) {
                            $metCondition = 2;
                        }
                        $decoded_taf['taf'] = colorize($sigwx['coded'], $decoded_taf['taf']);
                    }
                    if (in_array($sigwx['coded'], $severe_wx['red'])) {
                        $metCondition = 3;
                        $decoded_taf['taf'] = colorize($sigwx['coded'], $decoded_taf['taf'], 'red');
                    }
                }
            }
        }
        $tafs[$station_id] = array('station_id' => $station_id,
            'raw' => (string)$decoded_taf['taf'],
            'metCondition' => $metCondition);
    }

    return $tafs;
}

/**
 * Returns the KML by combining results of getMetars & getTafs
 *
 * @param int $altn
 * @return string
 */
function returnKML($altn = 0)
{
    global $web_path;
    $metars = getMetars($altn);
    $tafs = getTafs($altn);
    $old_metar = '';
    

    $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
    $kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2">';
    $kml[] = ' <Document>';
    $kml[] = ' <Style id="1-1Style">';
    $kml[] = ' <IconStyle id="1-1Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/green-green.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $kml[] = ' <Style id="1-2Style">';
    $kml[] = ' <IconStyle id="1-2Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/green-yellow.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $kml[] = ' <Style id="1-3Style">';
    $kml[] = ' <IconStyle id="1-3Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/green-red.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $kml[] = ' <Style id="2-1Style">';
    $kml[] = ' <IconStyle id="2-1Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/yellow-green.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $kml[] = ' <Style id="2-2Style">';
    $kml[] = ' <IconStyle id="2-2Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/yellow-yellow.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $kml[] = ' <Style id="2-3Style">';
    $kml[] = ' <IconStyle id="2-3Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/yellow-red.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $kml[] = ' <Style id="3-1Style">';
    $kml[] = ' <IconStyle id="3-1Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/red-green.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $kml[] = ' <Style id="3-2Style">';
    $kml[] = ' <IconStyle id="3-2Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/red-yellow.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $kml[] = ' <Style id="3-3Style">';
    $kml[] = ' <IconStyle id="3-3Icon">';
    $kml[] = ' <Icon>';
    $kml[] = ' <href>' . $web_path . 'img/red-red.png</href>';
    $kml[] = ' </Icon>';
    $kml[] = ' <hotSpot x="0.5"  y="0.5" xunits="fraction" yunits="fraction"/>';
    $kml[] = ' </IconStyle>';
    $kml[] = ' </Style>';
    $i = 1;

    foreach ($metars as $metar) {
        // Fix for some African stations with no TAF
        if (!isset($tafs[$metar['station_id']]['metCondition'])) {
            $taf_metcond = 1;
        } else {
            $taf_metcond = $tafs[$metar['station_id']]['metCondition'];
        }
        $kml[] = ' <Placemark id="placemark' . $i . '">';
        $kml[] = ' <name>' . $metar['station_id'] . '</name>';
        $kml[] = ' <description><![CDATA[' . $metar['raw'] . " <br/><br/> " . $tafs[$metar['station_id']]['raw'] . ']]></description>';
        $kml[] = ' <styleUrl>#' . $metar['metCondition'] . "-" . $taf_metcond . 'Style</styleUrl>';
        $kml[] = ' <Point>';
        $kml[] = ' <coordinates>' . floatval(trim($metar['lon'])) . ',' . floatval(trim($metar['lat'])) . '</coordinates>';
        $kml[] = ' </Point>';
        $kml[] = ' </Placemark>';

        if ($metar['valid'] == 0) {
            $old_metar .= $metar['station_id'] . " ";
        }

        if ($altn == 0) {
            $setting = 'old_dest';
        } elseif ($altn == 1) {
            $setting = 'old_altn';
        }

        mysql_query('UPDATE german_wings_settings SET `value` = "' . $old_metar . '" where `setting` = "' . $setting . '"');
    }

    $kml[] = ' </Document>';
    $kml[] = '</kml>';
    $kmlOutput = join("\n", $kml);
    return $kmlOutput;


}

/**
 * Gets Old METARs strings from DB to display on index page
 *
 * @return string
 */
function returnOldWx()
{
    $old_altn = '';
    $old_dest = '';
    $query = mysql_query('SELECT * FROM german_wings_settings where `setting` = "old_dest" OR `setting` = "old_altn" ORDER BY `setting` ASC');
    while ($row = mysql_fetch_assoc($query)) {
        if ($row['setting'] == 'old_dest') {
            $old_dest = $row['value'];
        } elseif ($row['setting'] == 'old_altn') {
            $old_altn = $row['value'];
        }
    }
    $output = "Last update: " . gmdate('Hi', time()) . "Z<br/>";
    $output .= "Non valid METARS:<br/>";
    $output .= "Dest: " . $old_dest . "<br/>";
    $output .= "Altn: " . $old_altn;
    return $output;
}


if (isset($_GET['status'])) {
    echo returnOldWx();
} elseif (isset($_GET['altn']) && $_GET['altn'] == 1) {
    echo returnKML(1);
} elseif (!isset($_GET['altn']) && !isset($_GET['status'])) {
    echo returnKML();
}


