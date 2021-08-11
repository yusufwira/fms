<?php
    $fp = fopen('dataMgm/data_motor_at_active.csv', 'a');
    // fputcsv($fp, ["VID", 'Devicecode', 'Model', 'Imei','Cellnumber','Provider']);
    $conn = pg_connect("host=103.211.239.63 dbname=fms user=postgres password=pg8208)^Rw");
    $mosarPermanent = [ 201001, 201002, 201005, 201008, 201009, 201010, 201012, 201013, 201014, 
    201015, 201017, 201018, 201019, 201021, 201023, 201024, 201026, 201027, 201028, 201029, 201030, 
    201033, 201039, 201040, 201041, 201042, 201045, 201047, 201049, 201050, 201051, 201052, 201053, 
    201054, 201055, 201056, 201057, 201060, 201061, 201062, 201065, 201066, 201068, 201069, 201073, 
    201076, 201091, 201098, 201099, 201101, 201120, 201123, 201133, 201141, 201163, 201203, 201213, 201250];
    $mosarSementara = [201004, 201032, 201117, 201122, 201158, 201170, 201187, 201211, 201255];
    $mocilPermanent = [202008, 202075, 202089, 202177, 202219, 202269, 202305, 202308, 202314, 202396, 202428, 202485, 202500, 202502, 202599, 201007, 201235, 20112];
    $mocilSementara = [202081, 202191, 202192, 202194, 202271, 202306, 202344, 202425];
    $motorBelumPindah = [201043,201070,201106,201108,201134,201135,201142,201147,201151,201173,201227,201257,201259,202034,202107,202111,202114,202116,202148,202186,202288,202405,202470,202480,202589];
    // $motorkering = [202119,202153,202154,202158,202159,202162,202163,202164,202167,202230,202232,202117,202118,202122,202135,202216,201150,201148,202300,202284,202171,202176,202259,202909];
    $motorNotiN = array_merge($mosarPermanent, $mosarSementara, $mocilPermanent,  $mocilSementara, $motorBelumPindah);
    $x = substr(json_encode($motorNotiN), 1, -1);
        $sqlMaster = "SELECT deviceid 
        from master.device
        where companyid in ('200')";
        // and deviceid not in (".$x.")";
        $resultMaster = pg_query($conn, $sqlMaster);
        while ($master = pg_fetch_row($resultMaster)) {
            // $devicecode =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[0]);
    
            // Data Terakhir Kirim
            $sql =  "SELECT d.deviceid, d.devicecode, gd.model, i.imei, d.cellnumber from master.device as d 
            INNER JOIN config.imei_history i ON d.deviceid = i.deviceid
            INNER JOIN master.gpsdevice gd ON i.gpsdeviceid = gd.gpsdeviceid
            where d.deviceid = '".$master[0]."'
            and i.stopdatetime is null
            order by d.deviceid";
            $result = pg_query($conn, $sql);
            while ($row = pg_fetch_row($result)) {
                $getProvider = substr($row[4], 0, 4);
                if ($getProvider == '0811') {
                    $provider = "Halo";
                } else {
                    $provider = "Matrix";
                }
                if ( $row[3] != 0) {
                    echo "VID: $row[0] Devicecode: $row[1] Model: $row[2] Imei: $row[3] Cellnumber:$row[4] Provider:$provider";
                    $value = [$row[0],$row[1],$row[2],$row[3],$row[4],$provider];
                    fputcsv($fp, $value);
                    echo "<br />\n";
                }
            }

            
                
        }
    fclose($fp);

?>