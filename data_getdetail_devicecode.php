<?php
    $fp = fopen('dataMgm/data-offline-10b-agst.csv', 'a');
    // fputcsv($fp, ["VID", 'Devicecode', 'Model', 'Imei','Cellnumber','Provider', 'Last Latitude', 'Last Longitude', 'Last Location']);
    $conn = pg_connect("host=103.211.239.63 dbname=fms user=postgres password=pg8208)^Rw");   
    $file = fopen('data-temp-3.csv', 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $devicecode =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[0]);
    
            // Data Terakhir Kirim
            $sql =  "SELECT d.deviceid, d.devicecode, gd.model, i.imei, d.cellnumber from master.device as d 
            INNER JOIN config.imei_history i ON d.deviceid = i.deviceid
            INNER JOIN master.gpsdevice gd ON i.gpsdeviceid = gd.gpsdeviceid
            where d.devicecode = '".$devicecode."'
            and i.stopdatetime is null";           

            $result = pg_query($conn, $sql);
            while ($row = pg_fetch_row($result)) {
                $getProvider = substr($row[4], 0, 4);
                if ($getProvider == '0811') {
                    $provider = "Halo";
                } else {
                    $provider = "Matrix";
                }
                if ($row[3] != 0 && $row[3] != 1) {
                    // $sqlRaw = "select latitude, longitude, ioall from raw.raw_linux where imei = '".$row[3]."'
                    // order by id desc limit 1";
                    // $resultRaw = pg_query($conn, $sqlRaw);
                    // while ($raw = pg_fetch_row($resultRaw)) {
                    //     $lat = $raw[0];
                    //     $long = $raw[1];
                    //     $ioall = $raw[2];
                    // }
                    // $location = getaddress($lat, $long);

                    echo "VID: $row[0] Devicecode: $row[1] Model: $row[2] Imei: $row[3] Cellnumber:$row[4] Provider:$provider "; //  Lat:$lat Long:$long Lacation: $location
                    $value = [$row[0],$row[1],$row[2],$row[3],$row[4],$provider]; //,$lat,$long,$location 
                    fputcsv($fp, $value);
                    echo "<br />\n";                 
                }
            }

            
                
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

    function coba()
    {
        $sql = "SELECT d.deviceid, d.devicecode, d.cellnumber, d.companyid, i.imei from master.device d inner join config.imei_history i on d.deviceid = i.deviceid  where i.stopdatetime is null and d.deviceid = '".$devicecode."'";
        $result = pg_query($conn, $sql);
        while ($row = pg_fetch_row($result)) {
            $sql2 = "SELECT devicetime, servertime from raw.raw_avl where vehicleid = '".$devicecode."' order by devicetime desc limit 1";
            $result2 = pg_query($conn, $sql2);
            while ($row2 = pg_fetch_row($result2)) {
                $lastDevicetime = $row2[0];
                $lastServetime = $row2[1];
            }
            echo "VID: $row[0] Devicecode: $row[1] cellnumber: $row[2] companyid: $row[3] imei:$row[4] LastDevicetime:$lastDevicetime LastServertime:$lastServetime "; //  Lat:$lat Long:$long Lacation: $location
            $value = [$row[0],$row[1],$row[2],$row[3],$row[4], $lastDevicetime,$lastServetime]; //,$lat,$long,$location 
            fputcsv($fp, $value);
            echo "<br />\n";
        }
    }

?>