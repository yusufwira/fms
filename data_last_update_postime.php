<?php
    $fp = fopen('csv/data_last_update_postime_6a_juli_2021.csv', 'a');
    fputcsv($fp, ["Tracker ID", 'Devicetime', 'Servertime', "HTML Response", "XML Response", "Last Posttime"]);
    $conn = pg_connect("host=103.211.239.64 dbname=fms-node1 user=postgres password=pg8208)^Rw");
        $file = fopen('data-temp-1.csv', 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $devicecode =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[0]);

            // Data Terakhir Kirim
            $sql =  "SELECT aps.deviceid, aps.devicetime, aps.servertime ,aps.htmlresponse, aps.xmlresponse, aps.posttime + interval '7 hour' as postime
            FROM api.apidata as aps
            where aps.deviceid = '".$devicecode."'
            and aps.posttime is not null
            order by id desc limit 1"; 

            $result = pg_query($conn, $sql);
            if (pg_num_rows($result) > 0) {
                while ($row = pg_fetch_row($result)) {
                    echo "deviceid: $row[0] devicetime: $row[1] servertime: $row[2]  htmlresponse: $row[3] xmlresponse: $row[4] posttime $row[5]";                    
                    $value = [$row[0], $row[1],$row[2], $row[3], $row[4], $row[5]];
                    fputcsv($fp, $value);
                    echo "<br />\n";
                }
            } else {
                // Data ada di API tetapi tidak terkirim sama sekali
                $sql2 =  "SELECT aps.deviceid, aps.devicetime, aps.servertime ,aps.htmlresponse, aps.xmlresponse, aps.posttime + interval '7 hour' as postime
                FROM api.apidata as aps
                where aps.deviceid = '".$devicecode."'
                order by id desc limit 1";

                $result2 = pg_query($conn, $sql2);
                if (pg_num_rows($result2) > 0) {
                    while ($row2 = pg_fetch_row($result2)) {
                        echo "deviceid: $row2[0] devicetime: $row2[1] servertime: $row2[2]  htmlresponse: null xmlresponse: null posttime null";
                        $value = [$row2[0], $row2[1],$row2[2],'null','null','null'];
                        fputcsv($fp, $value);
                        echo "<br />\n";
                    }
                } else {
                    // Data tidak terdapat pada API
                    echo $devicecode .' Tidak ada data di API';
                    $value = [$devicecode, ' Tidak ada data di API'];
                    fputcsv($fp, $value);
                    echo "<br />\n";
                }
            }
        }
    fclose($fp);

?>