<html>
 <head>
  <title>SL</title>
    <link rel="stylesheet" type="text/css" href="./css/SL.css">
 </head>
 <body>
    
<?php
$ini = parse_ini_file('app.ini');
$extrasSLAutoCloseTime     = $ini['extrasSLAutoCloseTime'];
$maxRefreshesDefault       = $ini['maxRefreshesDefault'];   // Max antal omladdningar av sidan innan "Visa avgångar" visas
$refreshTime               = $ini['refreshTime'];           // Antal sekunder tills att listan laddas om
$minutesOkLongRange        = $ini['minutesOkLongRange'];    // Antal minuter då refresh-tiden resettas, dvs då kan sidan laddas igen
$logLevel                  = $ini['logLevel'];              // 0 till 5

$allowedIps          = array("213.112.52.206", "83.251.241.161", "213.112.62.218");
$enableIpRestriction = false; // Tavlan verkar byta IP-adress ofta
$minutesOkShortRange = $maxRefreshesDefault * ($refreshTime / 60) + 0.1;
$activationFile = "log/SL_aktivering_" . date("Y.m") . ".txt";

// Init log
if ($logLevel > 0){
    $my_file = "log/SL_log_" . date("Y.m.d") . ".txt";
    $handle = fopen($my_file, 'a') or die('Cannot open file:  '.$my_file);
}

// Logging
printLog($logLevel, 1, "\n" . date("h:i:sa") . "");
printLog($logLevel, 1, $_SERVER['REQUEST_URI']);
if ($logLevel > 3){
    foreach (getallheaders() as $name => $value) {
        if ($logLevel >= 5){
            printLog($logLevel, 5, "$name: $value");
        }else if ($logLevel == 4){
            if ($name == "X-Forwarded-For" || $name == "Referer"){
                printLog($logLevel, 4, "$name: $value");
            }
        }else{
            if ($name == "Referer"){
                printLog($logLevel, 3, "$name: $value");
            }
        }
    }
}

if (file_exists($activationFile)) {
    $activationFileTime = filemtime($activationFile);
    $diff = (time() - $activationFileTime) / 60;
    
    // Om inom X minuter så är det OK
    if (($diff) <= $minutesOkShortRange){
        printLog($logLevel, 4, "activationFile OK. <$minutesOkShortRange min");
        $activationFileOk = 1;
        
    // Om mer än X minuter är det också OK
    }else if (($diff) > $minutesOkLongRange){
        // Uppdatera filen
        $a_file = fopen($activationFile, 'a') or die('Cannot open file:  '.$activationFile);
        fwrite($a_file, date("Y-m-d h:i:sa") . " >$minutesOkLongRange min\n");
        fclose($a_file);

        printLog($logLevel, 4, "activationFile OK. >$minutesOkLongRange min");
        $activationFileOk = 1;

    // Annars inte OK, kräv manuell aktivering
    }else{
        if (isset($_GET['aktivering']) && $_GET["aktivering"] == "1"){
            // Skapa filen, eftersom användaren har trycka på aktiverings-knappen
            $a_file = fopen($activationFile, 'a') or die('Cannot open file:  '.$activationFile);
            fwrite($a_file, date("Y-m-d h:i:sa") . " Manuellt\n");
            fclose($a_file);

            printLog($logLevel, 4, "activationFile OK. Manuellt");
            $activationFileOk = 1;
        }else{
            printLog($logLevel, 4, "activationFile vänta ($diff min)");
            $activationFileOk = 0;
        }
    }

}else{
    $a_file = fopen($activationFile, 'a') or die('Cannot open file:  '.$activationFile);
    fwrite($a_file, date("Y-m-d h:i:sa") . "\n");
    fclose($a_file);
    
    printLog($logLevel, 4, "activationFile OK. Ny fil");
    $activationFileOk = 1;
}


// Check access
$foundIP = 0;
for($x = 0; $x < count($allowedIps); $x++) {
    if ($allowedIps[$x] == $_SERVER['REMOTE_ADDR']){
        $foundIP = 1;
    }
}
if ($foundIP == 0 && $enableIpRestriction == true){
    $msg = "Okänd adress (" . $_SERVER['REMOTE_ADDR'] . ")";
    print_r("$msg");
    printLog($logLevel, 1, "\n" . date("h:i:sa") . "");
    printLog($logLevel, 1, $_SERVER['REQUEST_URI']);
    printLog($logLevel, 1, $msg);
    exit();
}

// Check i to refresh
if (isset($_GET["i"]) && $_GET["i"] != null){
    $maxRefreshes =  $_GET["i"];
}else{
    $maxRefreshes = $maxRefreshesDefault;
}
$maxRefreshes--;

function printLog($logLevel, $i, $s) {
    if ($i <= $logLevel){
        fwrite($GLOBALS['handle'], "$s\n");
    }
}

function cmp($a, $b){
    if ($a == $b)
        return 0;
        return ($a['name'] < $b['name']) ? -1 : 1;
}         

?>

<?php 

if ($maxRefreshes < 0 || $activationFileOk == 0){
    print_r("<div class='updateButtonContainer'><a href='" . basename($_SERVER['PHP_SELF']) . "?aktivering=1'><button class='myButton'><br><br><br>Uppdatera<br>&nbsp;</button></a></div>");
    printLog($logLevel, 2, "---Pause");
    exit("");
}

//printLog($logLevel, 2, "---VisaTider");

// JavaScript för att ladda om sidan
$newURL = basename($_SERVER['PHP_SELF']) . "?i=" . $maxRefreshes;
print_r("<script>");
print_r("var x = setInterval(function() { location = '" . $newURL . "'; }, " . ($refreshTime*1000) . ")");
print_r("</script>");


function uppdateraTider() {
    // Enable the global variable
    global $logLevel;

    // 9732 = Trångsund

    //open connection
    $curl = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($curl, CURLOPT_URL, "https://transport.integration.sl.se/v1/sites/9732/departures?timewindow=60");  // Set the url path we want to call
    
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);

    //execute post
    $result = curl_exec($curl);
    //print_r($result);

    //see the results
    $json = json_decode($result, true);
    curl_close($curl);
    //print_r($json);

    // Declare a multi-dimensional array containing all rides in a sorted way
    $rides = array(array(array(array())));

    if ($json != null){

        // Hämta ev info om förseningar
        foreach ((array)$json["stop_deviations"] as $deviation) {
            print_r("<div class='delayText'><span class='delayIcon'>!</span> " . $deviation["message"] . "</div>");
        }

        // Hämta varje avgång
        $rideId = 0;
        foreach ($json["departures"] as $departure) {
            $rideId++;
            if ($departure['line'] == null || $departure['line']['id'] == null){
                continue;   
            }

            $station = $departure["stop_area"]["name"];
            $type = $departure["line"]["transport_mode"];
            if(isset($departure["line"]["group_of_lines"])){
                $type = $departure["line"]["group_of_lines"]; 
            }
            if ($type == "BUS"){
                $type = "Buss";
            }
            $direction = $departure["direction_code"];
            
            $dateTime = new DateTime($departure["expected"]);
            $time = $dateTime->format('H:i');

            $buffer = "";
            $buffer .= "<td class='lineNr'>" . $departure['line']['id'] . "</td>";
            $buffer .= "<td class='destination'>" . $departure['destination'] . "</td>";
            $buffer .= "<td class='displayTime'>" . $time . "</td>";

            $rides["$type"]["$station"][$direction][$rideId] = $buffer;
        }

        // Visa upp all hämtad info i ordnad form
        foreach ((array)$rides as $type => $value) {
            foreach ((array)$rides[$type] as $station => $value) {
                
                $stationSorted = (array)$rides[$type][$station];
                krsort($stationSorted);
                
                foreach ($stationSorted as $direction => $value) {

                    if ($station == "0"){
                        continue;
                    }

                    print_r("<table>");
                    print_r("<tr><th colspan='3'><img src='img/$type.png'> $station ($type)");
                    $nr = 1;
                
                    foreach ((array)$value as $id => $value) {
                        $nr++;
                        if ($nr >= 6){
                            continue;
                        }
                        
                        print_r("<tr>");
                        print_r($value);
                    }
                    
                    //if ($nr < 5){
                    //    print_r("<tr><td><td class='lineNr noLine' colspan='2'>(inga turer hittade inom närmaste 60 min)</td>");
                    //}
                    print_r("</table>");
                }
            }
        }

        //print_r($json);
    }else{
        print_r($result);
        printLog($logLevel, 1, "$result");
    }
}

uppdateraTider();

?>

<span class="disclaimer">Avgångar närmaste 60 min enl. realtidsinfo från SL.<br>Uppdateras <?php echo $maxRefreshesDefault ?> ggr med <?php echo $refreshTime ?> s intervall och går sedan i viloläge. Stängs efter <?php echo $extrasSLAutoCloseTime ?> s</span>

</body>
</html>