<?php

    $conn = pg_connect("host=103.211.239.64 dbname=fms-node1 user=postgres password=pg8208)^Rw");
    $conn2 = pg_connect("host=103.211.239.63 dbname=fms user=postgres password=pg8208)^Rw");

    $result = pg_query($conn, "SELECT distinct(deviceid), imei, devicecode FROM api.apidata where devicetime between '2021-06-23 00:00:00' and '2021-06-23 23:59:00' and (servertime - devicetime) > '00:03:00' ORDER BY deviceid DESC");

    while ($row = pg_fetch_row($result)) {
        $jumlah = pg_query($conn, "SELECT count(imei) FROM api.apidata where deviceid = '".$row[0]."'  and devicetime between '2021-06-23 00:00:00' and '2021-06-23 23:59:00' and (servertime - devicetime) > '00:03:00'");
        $jumlahFix = 0;
        while ($row2 = pg_fetch_row($jumlah)) {
            $jumlahFix = $row2[0];
        }

        $jumlahDatas = pg_query($conn, "SELECT count(*) FROM api.apidata where deviceid = '".$row[0]."'  and devicetime between '2021-06-23 00:00:00' and '2021-06-23 23:59:00'");
        $jumlahData = 0;
        while ($row3 = pg_fetch_row($jumlahDatas)) {
            $jumlahData = $row3[0];
        }

        $cellnumber = '';
        $result2 = pg_query($conn2, "SELECT cellnumber FROM master.device where deviceid = '".$row[0]."'");
        while ($row3 = pg_fetch_row($result2)) {
            $cellnumber = $row3[0];
        }
        $getProvider = substr($cellnumber, 0, 4);
        if ($getProvider == '0811') {
            $provider = "Halo";
        } else {
            $provider = "Matrix";
        }

        $persentase = round((1- ($jumlahFix/$jumlahData)) * 100 ,2);

        echo "deviceid: $row[0]  imei: $row[1] devicecode: $row[2] notelp: $cellnumber Provider: $provider jumlah lebih 3menit: $jumlahFix totalData: $jumlahData Persentase: $persentase%";
        echo "<br />\n";
    }
    

    
    
        
    

    pg_close($conn);
    pg_close($conn2);
?>