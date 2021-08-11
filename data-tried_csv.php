<?php
$fp = fopen('csv/cobay.csv', 'a');
$conn = pg_connect("host=103.211.239.63 dbname=fms user=postgres password=pg8208)^Rw");
    $file = fopen('data-temp-2.csv', 'r');
    while (($line = fgetcsv($file)) !== FALSE) {
        $devicecode =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line[0]);
        $temp = $line[1];
        echo $devicecode;
         echo "<br />\n";;

        $sql =  "SELECT devicecode, devicetime, temperature
        FROM transaction.transaction_avl
        where devicetime between '2021-06-1 17:00:00' and '2021-06-7 17:00:00'
        and devicecode = '".$devicecode."'
        and temperature <= ".$temp."
        Order by devicetime limit 10";

        $result = pg_query($conn, $sql);
        fputcsv($fp, [$devicecode, '', '']);
        while ($row = pg_fetch_row($result)) {
            echo "devicecode: $row[0]  devicetime: $row[1] temperature: $row[2]";
            $value = [$row[0], $row[1],$row[2]];
		    fputcsv($fp, $value);
            echo "<br />\n";
        }
    }
fclose($fp);
?>