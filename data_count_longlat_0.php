<?php
    $fp = fopen('csv/data-check-26-juli.csv', 'a');
    fputcsv($fp, ["Device ID", 'Device code','Count','Total Data', 'Persentase longlat (0)']);
    $conn = pg_connect("host=103.211.239.64 dbname=fms-node1 user=postgres password=pg8208)^Rw");
    $conn2 = pg_connect("host=103.211.239.63 dbname=fms user=postgres password=pg8208)^Rw");
        $file = fopen('data-temp-2.csv', 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $devicecode =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[0]);
    
            // Data Terakhir Kirim
            $sql =  "SELECT count(*) from api.apidata 
            where latitude = 0 and longitude = 0
            and deviceid = '".$devicecode."'
            and devicetime between '2021-08-2 00:00:00' and '2021-08-2 23:00:00'"; 

            $result = pg_query($conn, $sql);
            while ($row = pg_fetch_row($result)) {
                $jumlah = $row[0];
            }

            $sqlTotal =  "SELECT count(*) from api.apidata 
            where deviceid = '".$devicecode."'
            and devicetime between '2021-08-2 00:00:00' and '2021-08-2 23:00:00'"; 

            $resultTotal = pg_query($conn, $sqlTotal);
            while ($rowTotal = pg_fetch_row($resultTotal)) {
                $total = $rowTotal[0];
            }

            $nopol = '';
            $result2 = pg_query($conn2, "SELECT devicecode FROM master.device where deviceid = ".$devicecode);
            while ($row3 = pg_fetch_row($result2)) {
                $nopol = $row3[0];
            }

            $persentase = 0;
            if ($jumlah != 0) {
                $persentase = round((($jumlah/$total)) * 100 ,2);
            }

            echo "VID: $devicecode  Device code: $nopol jumlah: $jumlah Total: $total persentase: $persentase";
            $value = [$devicecode, $nopol, $jumlah, $total, $persentase.'%'];
            fputcsv($fp, $value);
            echo "<br />\n";
                
        }
    fclose($fp);

?>