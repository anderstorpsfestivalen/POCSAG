<?php
function logCommand($commandLogCounter, $commandoutput, $details) {
    $logfile = 'command_execution_log.json';

    // Read existing logs or initialize
    if (file_exists($logfile) && filesize($logfile) > 0) {
        $existingLogs = json_decode(file_get_contents($logfile), true);
        if (!is_array($existingLogs)) {
            $existingLogs = [];
        }
    } else {
        $existingLogs = [];
    }

    // Determine the next ID
    if (empty($existingLogs)) {
        $nextId = 1;
    } else {
        $ids = array_column($existingLogs, 'id');
        $maxId = max($ids);
        $nextId = $maxId + 1;
    }

    // Prepare new log entry
    $entry = [
        'id' => $nextId,
        'timestamp' => date('Y-m-d H:i:s'),
        //'output' => $commandoutput,
        'details' => $details
    ];

    // Append new entry
    $existingLogs[] = $entry;

    // Save back to file
    file_put_contents($logfile, json_encode($existingLogs, JSON_PRETTY_PRINT));

    // Keep in-memory logs
    global $commandLogs;
    if (!isset($commandLogs) || !is_array($commandLogs)) {
        $commandLogs = [];
    }
    $commandLogs[] = $entry;

    // Return the nextId for later use
    return $nextId;
}
$disableRestrictions = true; // Set this variable to true to disable restrictions

$items = [
['id' => 1, 'capcode' => '0364594', 'freq' => '147650000', 'baud' => '512', 'inversion' => 'off'],
['id' => 2, 'capcode' => '0364592', 'freq' => '157499990', 'baud' => '512', 'inversion' => 'off'],
['id' => 3, 'capcode' => '1277794', 'freq' => '161437500', 'baud' => '512', 'inversion' => 'off'],
['id' => 4, 'capcode' => '0644743', 'freq' => '148625000', 'baud' => '512', 'inversion' => 'off'],
['id' => 5, 'capcode' => '0625253', 'freq' => '148625000', 'baud' => '512', 'inversion' => 'off'],
['id' => 6, 'capcode' => '0627015', 'freq' => '148625000', 'baud' => '512', 'inversion' => 'off'],
['id' => 7, 'capcode' => '0642575', 'freq' => '148625000', 'baud' => '512', 'inversion' => 'off']
];
$field1 = $_POST["stext"];
$replaced_input = str_ireplace(array('å', 'ä', 'ö', 'Å', 'Ä', 'Ö'), array('a', 'a', 'o', 'A', 'A', 'O'), $field1);
        if (isset($_POST['button1'])) {
    foreach ($items as $item) {
if ($item['inversion'] == 'on') {
$command = 'sudo echo -e "' .$item['capcode']. ':' . $replaced_input . '" | sudo ./pocsagstuff/pocsag -f "'. $item['freq'] .'" -b 3 -r "'. $item['baud'] .'" -t 3 -i';
} else {

$command = 'sudo echo -e "' .$item['capcode']. ':' . $replaced_input . '" | sudo ./pocsagstuff/pocsag -f "'. $item['freq'] .'" -b 3 -r "'. $item['baud'] .'" -t 3';
}

$output = shell_exec($command);
if (isset($output)) {
$currentID = logCommand($commandLogCounter, $output, [
    'message' => $field1,
    'capcode' => $item['capcode'],
    'frequency' => $item['freq'],
    'baud' => $item['baud'],
    'inversion' => $item['inversion']
]);
    $commandLogCounter++;
    // You can now refer to $currentID later
    // For example:
     // echo "ID: $currentID Capcode: {$item['capcode']}, Freq: {$item['freq']}, Baud: {$item['baud']}, Inversion: {$item['inversion']} Message: $field1";
}
}
}

if (isset($_POST["button2"])) {

    $selectedOption = $_POST['dropdown'];

    // Define data for each selection
    $selection1_var1 = "0364594";
    $selection1_var2 = "147650000";
    $selection1_var3 = "512";
    $selection1_var4 = "off";

    $selection2_var1 = "0364592";
    $selection2_var2 = "157499990";
    $selection2_var3 = "512";
    $selection2_var4 = "off";

    $selection3_var1 = "1277794";
    $selection3_var2 = "161437500";
    $selection3_var3 = "512";
    $selection3_var4 = "off";

    $selection4_var1 = "0644743";
    $selection4_var2 = "148625000";
    $selection4_var3 = "512";
    $selection4_var4 = "off";

    $selection5_var1 = "0625253";
    $selection5_var2 = "148625000";
    $selection5_var3 = "512";
    $selection5_var4 = "off";

    $selection6_var1 = "0627015";
    $selection6_var2 = "148625000";
    $selection6_var3 = "512";
    $selection6_var4 = "off";

    $selection7_var1 = "0642575";
    $selection7_var2 = "148625000";
    $selection7_var3 = "512";
    $selection7_var4 = "off";
    // Save selected data to variables
    $var1 = $var2 = $var3 = $var4 = "";
    $freetext1 = $freetext2 = $freetext3 = $freetext4 =  "";

    switch ($selectedOption) {
        case 'selection1':
            $var1 = $selection1_var1;
            $var2 = $selection1_var2;
            $var3 = $selection1_var3;
            $var4 = $selection1_var4;
            break;

        case 'selection2':
            $var1 = $selection2_var1;
            $var2 = $selection2_var2;
            $var3 = $selection2_var3;
            $var4 = $selection2_var4;
            break;

        case 'selection3':
            $var1 = $selection3_var1;
            $var2 = $selection3_var2;
            $var3 = $selection3_var3;
            $var4 = $selection3_var4;
            break;
        case 'selection4':
            $var1 = $selection4_var1;
            $var2 = $selection4_var2;
            $var3 = $selection4_var3;
            $var4 = $selection4_var4;
            break;
        case 'selection5':
            $var1 = $selection5_var1;
            $var2 = $selection5_var2;
            $var3 = $selection5_var3;
            $var4 = $selection5_var4;
            break;
        case 'selection6':
            $var1 = $selection6_var1;
            $var2 = $selection6_var2;
            $var3 = $selection6_var3;
            $var4 = $selection6_var4;
            break;
        case 'selection7':
            $var1 = $selection7_var1;
            $var2 = $selection7_var2;
            $var3 = $selection7_var3;
            $var4 = $selection7_var4;
            break;
        case 'freetext':
            $var1 = $_POST['freetext1'];
            $var2 = $_POST['freetext2'];
            $var3 = $_POST['freetext3'];
	    //$var4 = isset($_POST['freetext4']) ? 'off' : 'on';
	    $var4 = isset($_POST['freetext4']) ? $_POST['freetext4'] : 'off';
break;
    }
    $field1 = $_POST["stext"];
    $replaced_input = str_ireplace(array('å', 'ä', 'ö', 'Å', 'Ä', 'Ö'), array('a', 'a', 'o', 'A', 'A', 'O'), $field1);


    // Check if the local time is between 22 and 08
    $currentHour = date('H');
    if (!$disableRestrictions && ($currentHour >= 22 || $currentHour < 8)) {
        echo "<pre><br><br><br><center>Command cannot be executed between 22 and 08 local time.</center></pre>";
        exit();
    }

    // Whitelisted subnets
    $whitelistedSubnets = [
        "127.0.0.1/24", // Example of a single IP subnet
        "10.0.0.0/8", // Example of a CIDR notation
    ];

    $ip = $_SERVER['REMOTE_ADDR'];
    $isWhitelisted = false;
    foreach ($whitelistedSubnets as $subnet) {
        list($subnetIp, $subnetMask) = explode('/', $subnet);
        $subnetLong = ip2long($subnetIp);
        $ipLong = ip2long($ip);
        $maskLong = pow(2, 32) - pow(2, (32 - $subnetMask));
        if (($ipLong & $maskLong) == ($subnetLong & $maskLong)) {
            $isWhitelisted = true;
            break;
        }
    }

    if (!$disableRestrictions && !$isWhitelisted) {
        $timestamp = time();
        $file = "command_log.txt";
        $log = file_get_contents($file);
        $log = json_decode($log, true);

        if (isset($log[$ip])) {
            $last_executed = $log[$ip]["timestamp"];
            $diff = $timestamp - $last_executed;

            if ($diff < 60) {
                echo "<pre><br><br><br><center>Command execution is limited to once every 60 seconds.</center></pre>";
                exit();
            }
        }

        if (isset($log[$ip]) && $log[$ip]["field1"] == $field1) {
            echo "<pre><br><br><br><center>Field1 cannot have the same value twice in a row.</center></pre>";
            exit();
        }

        $log[$ip] = [
            "field1" => $field1,
            "timestamp" => $timestamp
        ];

        file_put_contents($file, json_encode($log));
    }
if ($var4 == 'on') {

$command = 'sudo echo -e "'. $var1. ':' . $replaced_input . '" | sudo ./pocsagstuff/pocsag -f "'. $var2 .'" -b 3 -r "'. $var3 .'" -t 3 -i';
} else {

$command = 'sudo echo -e "'. $var1. ':' . $replaced_input . '" | sudo ./pocsagstuff/pocsag -f "'. $var2 .'" -b 3 -r "'. $var3 .'" -t 3';
}
$output = shell_exec($command);
if (isset($output)) {
    $currentID = logCommand($commandLogCounter, $output, [
        'message' => $field1,
        'capcode' => $var1,
        'frequency' => $var2,
        'baud' => $var3,
        'inversion' => $var4
    ]);
    $commandLogCounter++;
    // You can now refer to $currentID later
    // For example:
//     echo "Logged command with ID: $currentID $var1 $var2 $var3 $var4";
//	echo "ID: $currentID Capcode: $var1, Freq: $var2, Baud: $var3, Inversion: $var4 Message: $field1";
}
$array2[] = "<div style='margin: 50px; font-family: Arial, sans-serif; text-align: center;'>
                <table style='width: 100%; border-collapse: collapse; margin: auto;'>
                    <tr>
                        <td style='padding: 5px; text-align: right;'><strong>Message:</strong></td>
                        <td style='padding: 5px; text-align: left;'>$field1</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px; text-align: right;'><strong>Capcode:</strong></td>
                        <td style='padding: 5px; text-align: left;'>$var1</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px; text-align: right;'><strong>Frequency:</strong></td>
                        <td style='padding: 5px; text-align: left;'>$var2</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px; text-align: right;'><strong>Baud:</strong></td>
                        <td style='padding: 5px; text-align: left;'>$var3</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px; text-align: right;'><strong>Inversion:</strong></td>
                        <td style='padding: 5px; text-align: left;'>$var4</td>
                    </tr>
                </table>
            </div>";
}
?>
<!DOCTYPE html>
<html>
<title>SUPER DUPER POCSAG SERVICE</title>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<center>
<form method="post" action="">
<select name="dropdown" id="dropdown" onchange="toggleFreetextInputs()">
        <option value="selection1">Pager1 0364594</option>
        <option value="selection2">Pager2 0364592</option>
        <option value="selection3">Pager3 1277794</option>
        <option value="selection4">Pager4 0644743</option>
        <option value="selection5">Pager5 0625253</option>
        <option value="selection6">Pager6 0627015</option>
        <option value="selection7">Pager7 0642575</option>
        <option value="freetext">Manual Page</option>
    </select>

    <div id="freetextInputs" style="display: none;">
	<br>
        <input type="text" name="freetext1" placeholder="Capcode" style="font-size:24px"><br>
        <input type="text" name="freetext2" placeholder="Frequency" style="font-size:24px"><br>
        <input type="text" name="freetext3" placeholder="Baud Rate" style="font-size:24px"><br>
	<select id="freetext4" name="freetext4" style="height:30px; width:292px;">
    	<option value="off">Inversion Off</option>
    	<option value="on">Inversion On</option>
	</select>
	<br>
    </div>
<br>
<br>
<input type="text" placeholder="Message" name="stext" style="font-size:24px" id="stext" ><br>
<br>
<br>
<input type="submit" value="Send Single Page" style="height:75px; width:400px; font-size:24px" name="button2">
<br>
<br>
<br>
<br>
<input type="submit" value="Send to subscription list" style="height:75px; width:400px; font-size:24px" name="button1">
</form>
<?php
//print_r($array);
echo implode('', $array);
//echo $outvar;
echo implode('', $array2);
//print_r($array2);
?> 
<script>
function toggleFreetextInputs() {
    const dropdown = document.getElementById('dropdown');
    const freetextInputs = document.getElementById('freetextInputs');

    if (dropdown.value === 'freetext') {
        freetextInputs.style.display = 'block';
    } else {
        freetextInputs.style.display = 'none';
    }
}
</script>
</center>
</html>





