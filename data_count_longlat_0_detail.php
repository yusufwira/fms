<?php
    $fp = fopen('csv/data-motor-.csv', 'a');
    fputcsv($fp, ["VID", 'Devicetime', 'lat(API)', 'long(API)','id','lat(RAW)', 'long(RAW)', 'Posisi']);
    $conn = pg_connect("host=103.211.239.64 dbname=fms-node1 user=postgres password=pg8208)^Rw");
        $file = fopen('data-temp-1.csv', 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $devicecode =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[0]);
    
            // Data Terakhir Kirim
            $sql =  "SELECT aps.deviceid, aps.devicetime, aps.latitude, aps.longitude, rws.id, rws.latitude, rws.longitude 
            from api.apidata as aps
            inner join raw.raw_linux rws 
            on aps.rawid = rws.id
            where aps.latitude = 0 and aps.longitude = 0
            and aps.deviceid = '".$devicecode."'
            and aps.devicetime between '2021-07-15 00:00:00' and '2021-07-15 12:00:00'
            Order by rws.id desc limit 1";

            // fputcsv($fp, [$devicecode]);
            $result = pg_query($conn, $sql);
            if (pg_num_rows($result) > 0) {
                while ($row = pg_fetch_row($result)) {
                    $address = getaddress($row[5],$row[6]);
                    echo "VID: $row[0] Devicetime: $row[1] lat(API): $row[2] long(API):$row[3] id: $row[4] lat(RAW): $row[5] long(RAW): $row[6] Posisi: $address";
                    $value = [$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6], $address];
                    fputcsv($fp, $value);
                    echo "\n";
                    echo "\n";
                }
            } else {
                echo "Tidak ada data di Raw";
                $value = [$devicecode,'Tidak ada data'];
                fputcsv($fp, $value);
                echo "\n";
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

?>