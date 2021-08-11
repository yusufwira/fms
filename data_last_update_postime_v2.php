<?php
    $rustart = getrusage();

    $CSV_TO = 'csv/data_online_agst.csv';
    $CSV_FROM = 'dataMgm/data-offline-10a-agst.csv';
    // $CSV_FROM = 'data-temp-1.csv';

    $fp = fopen( $CSV_TO , 'a');
    $conn = pg_connect("host=103.211.239.64 dbname=fms-node1 user=postgres password=pg8208)^Rw");
    $conn2 = pg_connect("host=103.211.239.63 dbname=fms user=postgres password=pg8208)^Rw");
    echo "Process...";
    $sqlCheckApi = "SELECT aps.deviceid, max(aps.devicetime)
                    FROM api.apidata as aps                   
                    group by deviceid";
    $resultCheckApi = pg_query($conn, $sqlCheckApi);
    $dataInApi = [];
    while ($check = pg_fetch_row($resultCheckApi)) {
        array_push($dataInApi,$check[0]);
    }  

    $file = fopen( $CSV_FROM, 'r');
    while (($line = fgetcsv($file)) !== FALSE) {
        $deviceid =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[0]);
        $imei =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[3]);
        // echo $imei;
        if (array_search($deviceid, $dataInApi) !== false) {
            DataInAPI($deviceid, $fp, $conn);
        } else {
            DataNotInApi($deviceid, $imei, $fp, $conn2);
        }
        // FindMissingIo($deviceid, $imei, $fp, $conn2);
        echo "\n";
    }

    fclose($fp);

    function DataInAPI($deviceid, $fp, $conn){        
        $sql =  "SELECT aps.deviceid, aps.devicetime, aps.servertime ,aps.htmlresponse, aps.xmlresponse, aps.posttime + interval '7 hour' as postime, aps.rawid, aps.id
        FROM api.apidata as aps
        where aps.deviceid = '".$deviceid."'
        and aps.posttime is not null
        order by id desc limit 1";
        $result = pg_query($conn, $sql);
        if (pg_num_rows($result) > 0) {
            while ($row = pg_fetch_row($result)) {
                echo "deviceid: $row[0] devicetime: $row[1] servertime: $row[2]  htmlresponse: $row[3] xmlresponse: $row[4] posttime $row[5] raw: $row[6] id: $row[7]";
                echo "\n";
                $comment="";
                if ($row[5] < date("Y-m-d")) {
                    // echo 'Find Problem...';
                    // echo "\n";           
                    // $sqlCheckTemp =  "SELECT aps.rawid
                    //         FROM api.apidata as aps
                    //         where aps.deviceid = '".$deviceid."'
                    //         order by id desc limit 1";
                    // $resultCheckTemp = pg_query($conn, $sqlCheckTemp);
                    // while ($y = pg_fetch_row($resultCheckTemp)) {
                    //     $rawIdY = $y[0];
                    // }
                    $comment = FindProblem($row[6], 0, $conn);
                } else {
                    echo 'Update';
                    echo "\n";
                    $comment = 'Update';
                }
                $value = [$row[0], $row[1],$row[2], $row[3], $row[4], $row[5], $comment];
                fputcsv($fp, $value);
            }
        } else {
            DataNeverSend($deviceid, $fp, $conn);
        }
    }


    function DataNeverSend($deviceid, $fp, $conn)
    {        
        $sql2 =  "SELECT aps.deviceid, aps.devicetime, aps.servertime ,aps.htmlresponse, aps.xmlresponse, aps.posttime + interval '7 hour' as postime, aps.rawid
        FROM api.apidata as aps
        where aps.deviceid = '".$deviceid."'
        order by id desc limit 1";
        $result2 = pg_query($conn, $sql2);
        while ($row2 = pg_fetch_row($result2)) {
            echo "deviceid: $row2[0] devicetime: $row2[1] servertime: $row2[2]  htmlresponse: null xmlresponse: null posttime null";
            echo "\n";
            $comment = FindProblem($row2[6], 1, $conn);
            
            $value = [$row2[0], $row2[1],$row2[2],'null','null','null', $comment];
            fputcsv($fp, $value);
        }
    }


    function DataNotInApi($deviceid, $imei, $fp, $conn2)
    {                
        $sqlraw =  "SELECT rw.ioall, rw.devicetime + interval '7 hour', rw.servertime + interval '7 hour'
                    FROM raw.raw_linux as rw
                    where rw.imei =".$imei."
                    Order by rw.id desc limit 1";
        $resultRaw = pg_query($conn2, $sqlraw);
        while ($x = pg_fetch_row($resultRaw)) {
            $io = $x[0];
            $devicetimeRaw = $x[1];
            $servertimeRaw = $x[2];
        }
        echo "deviceid: $deviceid devicetime: $devicetimeRaw servertime: $servertimeRaw  Tidak Ada DI API";
        echo "\n";
        echo 'Find Problem...';
        echo "\n";                    
        $comment ="";
        $findPower = substr($io, strpos($io, "|66,") + 1);
        $sparatePower = explode("|",$findPower);
        $getPower = substr($sparatePower[0], strpos($sparatePower[0], ",") + 1);
        if ($getPower < 7000) {
            echo 'Battery Power (x)('.$sparatePower[0].')';
            echo "\n";
            $comment = 'Battery Power Low ('.$sparatePower[0].') (api null)';
        } else {
            echo 'Battery Power (v)('.$sparatePower[0].')';
            echo "\n";
            $comment = 'CPU Reset';
            echo 'CPU Reset Recomandation';
            echo "\n";
        }                   
        $value = [$deviceid, $devicetimeRaw,$servertimeRaw,'Api null','Api null','Api null', $comment];
        fputcsv($fp, $value);
        echo "\n";
    }



    function FindProblem($rawId, $check, $conn)
    {       
        echo 'Find Problem...';
        echo "\n";
        $sqlraw =  "SELECT rw.ioall
                FROM raw.raw_linux as rw
                where rw.id =" . $rawId;
        $resultRaw = pg_query($conn, $sqlraw);
        while ($x = pg_fetch_row($resultRaw)) {
            $io = $x[0];
        }
        $comment ="";
        $findPower = substr($io, strpos($io, "|66,") + 1);
        $sparatePower = explode("|",$findPower);
        $getPower = substr($sparatePower[0], strpos($sparatePower[0], ",") + 1);
        if ($getPower < 7000) {
            echo 'Battery Power (x)('.$sparatePower[0].')';
            echo "\n";
            $comment = 'Battery Power Low ('.$sparatePower[0].')';
        } else {
            echo 'Battery Power (v)('.$sparatePower[0].')';
            echo "\n";
            $findTemp = substr($io, strpos($io, "|72,") + 1);
            $sparateTemp = explode("|",$findTemp);
            $getTemp = substr($sparateTemp[0], strpos($sparateTemp[0], ",") + 1);
            if ($getTemp >= 850) {
                    echo 'Temp Error (x)('.$sparateTemp[0].')';
                    echo "\n";
                    $comment = 'Temp Error ('.$sparateTemp[0].')';
            } else {
                    echo 'Temp Ok (v)('.$sparateTemp[0].')';
                    echo "\n";
                    if ($check == 0) {
                        $comment = 'CPU Reset';
                    } else {
                        $comment = 'Mobil Kering';
                    }
                    echo $comment;
                    echo "\n";                    
            }    
        }

        return $comment;
    }


    function FindMissingIo($deviceid, $imei, $fp, $conn2, $ioInput)
    {
        $sqlraw =  "SELECT rw.ioall, rw.devicetime + interval '7 hour', rw.servertime + interval '7 hour'
                    FROM raw.raw_linux as rw
                    where rw.imei =".$imei."
                    Order by rw.id desc limit 1";
        $resultRaw = pg_query($conn, $sqlraw);
        while ($x = pg_fetch_row($resultRaw)) {
            $io = $x[0];
        }

        $findio = substr($io, strpos($io, "|".$ioInput.",") + 1);
        $sparate= explode("|",$findio);
        $getIo = substr($sparate[0], strpos($sparate[0], ",") + 1);
        if ($getIo == null) {
            echo $deviceid.' '.$ioInput.' Missing / Not Exist';
            $comment = 'Missing / Not Exist';
        } else {
            echo $deviceid.' '.$ioInput.' Already Exist';
            $comment = 'Already Exist';
        }
        $value = [$deviceid, $ioInput,$comment,'','','', ];
        fputcsv($fp, $value);
    }

    function rutime($ru, $rus, $index) {
        return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
         -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
    }

?>