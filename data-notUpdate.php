<?php
    $fp = fopen('csv/data_not_post.csv', 'a');
    fputcsv($fp, ["VID", "Devicetime", "servertime", "Last Posttime"]);
    $conn = pg_connect("host=103.211.239.64 dbname=fms-node1 user=postgres password=pg8208)^Rw");
        $file = fopen('data-temp-3.csv', 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            $devicecode =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[0]);
    
            $sql =  "SELECT  aps.deviceid, aps.htmlresponse, aps.xmlresponse, aps.posttime + interval '7 hour' as postime
            FROM api.apidata as aps
            where aps.deviceid = '".$devicecode."'
            and aps.posttime is not null
            order by id desc limit 1";
    
            $result = pg_query($conn, $sql);
            while ($row = pg_fetch_row($result)) {
                echo $row[0];
                echo "<br />\n";
                if ($row[3] < '2021-07-02') {
                    echo "deviceid: $row[0]  htmlresponse: $row[1] xmlresponse: $row[2] posttime $row[3]";
                    $value = [$row[0], $row[1],$row[2], $row[3]];
                    fputcsv($fp, $value);
                    echo "<br />\n";
                }
            }
        }
    fclose($fp);

?>