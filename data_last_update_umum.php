<?php
    $fp = fopen('csv/data_hjs_22_juli.csv', 'a');
    fputcsv($fp, ["VID", 'Device Code', 'Imei', "Model GPS", "Cellnumber", "Company GROUP", 'Devicetime', 'Servertime', 'Last Location', 'Comment']);
    $conn = pg_connect("host=103.4.6.235 dbname=fmstracking user=postgres password=pg8208)^Rw");
    $sqlDevice = 'SELECT d.deviceid, i.imei, d.devicecode, d.cellnumber, d.companygroupid, gps.model FROM master.device as d 
    inner join config.imei_history as i on d.deviceid = i.deviceid 
	inner join master.gpsdevice as gps on i.gpsdeviceid = gps.id
    where d.companyid not in (200,211,217,307,313,315,329,543) and i.stopdatetime is null';

    $resultDevice = pg_query($conn, $sqlDevice);
    while ($device = pg_fetch_row($resultDevice)) {
        $sqlRaw = "SELECT rw.devicetime + '7 hour', rw.servertime + '7 hour', rw.latitude, rw.longitude, rw.ioall
        FROM raw.raw_linux as rw
        where rw.imei ='".$device[1]."'
        Order by id desc LIMIT 1";
        $resultRaw = pg_query($conn, $sqlRaw);
        if (pg_num_rows($resultRaw) > 0) {
            while ($raw = pg_fetch_row($resultRaw)) {
                echo "$device[0] $device[2] $device[1] $device[5] $device[3] $device[4] $raw[0] $raw[1]";
                echo "\n";
                $lastLoc = '';
                if ($raw[0] < date("Y-m-d")) {
                    echo 'Find Problem...';
                    echo "\n";

                    $lastLoc = getaddress($raw[2],$raw[3]);
                    
                    $findPower = substr($raw[4], strpos($raw[4], "|66,") + 1);
                    $sparatePower = explode("|",$findPower);
                    $getPower = substr($sparatePower[0], strpos($sparatePower[0], ",") + 1);
                    if ($getPower < 7000) {
                        echo 'Battery Power (x)('.$sparatePower[0].')';
                        echo "\n";
                        $comment = 'Battery Power Low ('.$sparatePower[0].')';
                    } else {
                        echo 'Battery Power (v)('.$sparatePower[0].')';
                        echo "\n";
                        echo 'CPU Reset Recomendation';
                        echo "\n";
                        $comment = 'CPU Reset Recomendation';
                    }

                } else {
                    echo 'Update';
                    echo "\n";
                    $comment = 'Update';
                }
               
                $value = [$device[0], $device[2], $device[1], $device[5], $device[3], $device[4], $raw[0], $raw[1], $comment, $lastLoc];
                fputcsv($fp, $value);
            }
        } else {
            echo $device[0]. $device[2]. $device[1]. $device[5]. $device[3]. $device[4].' null'.' null'.' Tidak ada data di RAW';
            // $value = [$device[0], $device[1] ,' Tidak ada data di RAW'];
            $value = [$device[0], $device[2], $device[1], $device[5], $device[3], $device[4],' null',' null',' Tidak ada data di RAW'];
            fputcsv($fp, $value);
            echo "\n";
        }
        echo "\n";
    }  
    fclose($fp);


    function getaddress($lat,$lng)
    {
        $opts = array('http'=>array('header'=>"User-Agent: StevesCleverAddressScript 3.7.6\r\n"));
        $context = stream_context_create($opts);
        $url = 'https://nominatim.openstreetmap.org/reverse.php?lat='.$lat.'&lon='.$lng.'&zoom=14&format=jsonv2';
        //  $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false&key=AIzaSyCJyDp4TLGUigRfo4YN46dXcWOPRqLD0gQ';
        $json = file_get_contents($url, false, $context);
        $data=json_decode($json);
        $address1 ="";
        $address2 ="";
        $address3 ="";
        $i = 0;
        foreach($data->address as $key => $value) {
            if ($i == 0) {
                $address1 = $value;
            }
            if ($i == 1) {
                $address2 = $value;
            }
            if ($i == 2) {
                $address3 = $value;
                break;
            }
            $i++;
        }
        return $address1.' - '.$address2. ' - '. $address3;    
    }

?>