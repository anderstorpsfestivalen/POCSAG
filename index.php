<?php
if (isset($_POST['print_receipt'])) {
    $printerDevice = '/dev/usb/lp0';

    // Check if the device is writable
    if (is_writable($printerDevice)) {
//        echo "Device is writable. Proceeding with printing.\n";

        // Number of empty lines to add
        $emptyLinesCount = 8; // Changed from 50 to 8
        $emptyLines = str_repeat("\n", $emptyLinesCount);

        // Append empty lines to the file
        file_put_contents('/var/www/dashboard/ascii', $emptyLines, FILE_APPEND);

        // Run the print command
        $printCommand = "sudo sh -c 'cat /var/www/dashboard/ascii > " . escapeshellarg($printerDevice) . "'";
        shell_exec($printCommand);
    } else {
        echo "printer not available
";
    }
}
// Function to log commands into a JSON file with incremental IDs
function logCommand($commandLogCounter, $commandOutput, $details) {
    $logFile = 'command_execution_log.json';

    // Read existing logs or initialize empty array
    if (file_exists($logFile) && filesize($logFile) > 0) {
        $existingLogs = json_decode(file_get_contents($logFile), true);
        if (!is_array($existingLogs)) {
            $existingLogs = [];
        }
    } else {
        $existingLogs = [];
    }

    // Determine next ID
    $nextId = empty($existingLogs) ? 1 : max(array_column($existingLogs, 'id')) + 1;

    // Prepare new log entry
    $entry = [
        'id' => $nextId,
        'timestamp' => date('Y-m-d H:i:s'),
        //'output' => $commandOutput, // Uncomment if you want to store output
        'details' => $details
    ];

    // Append and save logs
    $existingLogs[] = $entry;
    file_put_contents($logFile, json_encode($existingLogs, JSON_PRETTY_PRINT));

    // Keep in-memory logs
    global $commandLogs;
    if (!isset($commandLogs) || !is_array($commandLogs)) {
        $commandLogs = [];
    }
    $commandLogs[] = $entry;

    return $nextId;
}

// Add these functions somewhere near your existing functions

function getLastLogEntry() {
    $logFile = 'command_execution_log.json';

    if (!file_exists($logFile) || filesize($logFile) == 0) {
        return null;
    }

    $logs = json_decode(file_get_contents($logFile), true);

    if (is_array($logs) && !empty($logs)) {
        return end($logs); // get the last entry
    }
    return null;
}

function runLogToUsb() {
    $devicePath = '/dev/usb/lp0';

if (!is_writable($devicePath)) {
    // Device not writable, skip
    return false;
}
    $entry = getLastLogEntry();
    if ($entry === null) {
        return false; // nothing to send
    }

    // Reformat the log entry to match the specified JSON structure
    $formattedData = [
        "id" => $entry['id'],
        "timestamp" => $entry['timestamp'],
        "details" => [
            "message" => $entry['details']['message'],
            "capcode" => $entry['details']['capcode'],
            "frequency" => $entry['details']['frequency'],
            "baud" => $entry['details']['baud'],
            "inversion" => $entry['details']['inversion']
        ]
    ];

    // Encode with pretty print
    $jsonData = json_encode($formattedData, JSON_PRETTY_PRINT);

    // Prepare 5 empty lines
    $emptyLines = str_repeat("\n", 8);

    // Combine JSON data and empty lines (empty lines after)
    $messageToPrint = $jsonData . $emptyLines;

    // Write the message to a temporary file
    $tmpPrintFile = sys_get_temp_dir() . "/print.json";
    file_put_contents($tmpPrintFile, $messageToPrint);

    // Send the JSON content to the USB device
    $command = "sudo sh -c 'cat " . escapeshellarg($tmpPrintFile) . " > " . escapeshellarg($devicePath) . "'";
    shell_exec($command);

    return true;
}




// --- Configuration: IP Subnet Restriction ---
// Load config
$configPath = 'config.json';
$subnetsPath = 'subnets.json';

$enableSubnetRestriction = true; // default
$whitelistedSubnets = [];

if (file_exists($configPath)) {
    $configData = json_decode(file_get_contents($configPath), true);
    if (isset($configData['enableSubnetRestriction'])) {
        $enableSubnetRestriction = $configData['enableSubnetRestriction'];
    }
}

if (file_exists($subnetsPath)) {
    $whitelistedSubnets = json_decode(file_get_contents($subnetsPath), true);
}
// Sample items data
$items = [
    ['id' => 1, 'capcode' => '0364594', 'freq' => '147650000', 'baud' => '512', 'inversion' => 'off'],
    ['id' => 2, 'capcode' => '0364592', 'freq' => '157499990', 'baud' => '512', 'inversion' => 'off'],
    ['id' => 3, 'capcode' => '1277794', 'freq' => '161437500', 'baud' => '512', 'inversion' => 'off'],
    ['id' => 4, 'capcode' => '0644743', 'freq' => '148625000', 'baud' => '512', 'inversion' => 'off'],
    ['id' => 5, 'capcode' => '0625253', 'freq' => '148625000', 'baud' => '512', 'inversion' => 'off'],
    ['id' => 6, 'capcode' => '0627015', 'freq' => '148625000', 'baud' => '512', 'inversion' => 'off'],
    ['id' => 7, 'capcode' => '0642575', 'freq' => '148625000', 'baud' => '512', 'inversion' => 'off']
];

$field1 = $_POST["stext"] ?? '';

// Replace special characters for compatibility
$replaced_input = str_ireplace(
    ['å', 'ä', 'ö', 'Å', 'Ä', 'Ö'],
    ['a', 'a', 'o', 'A', 'A', 'O'],
    $field1
);

// Handle "Send to subscription list" button
if (isset($_POST['button1'])) {
    foreach ($items as $item) {
        if ($item['inversion'] == 'on') {
            $command = 'sudo echo -e "' . $item['capcode'] . ':' . $replaced_input .
                '" | sudo ./pocsag -f "' . $item['freq'] .
                '" -b 3 -r "' . $item['baud'] . '" -t 3 -i';
        } else {
            $command = 'sudo echo -e "' . $item['capcode'] . ':' . $replaced_input .
                '" | sudo ./pocsag -f "' . $item['freq'] .
                '" -b 3 -r "' . $item['baud'] . '" -t 3';
        }

        $output = shell_exec($command);
        if ($output !== null) {
            $currentID = logCommand($commandLogCounter, $output, [
                'message' => $field1,
                'capcode' => $item['capcode'],
                'frequency' => $item['freq'],
                'baud' => $item['baud'],
                'inversion' => $item['inversion']
            ]);
            $commandLogCounter++;
      //echo "ID: $currentID Capcode: {$item['capcode']}, Freq: {$item['freq']}, Baud: {$item['baud']}, Inversion: {$item['inversion']} Message: $field1";
        runLogToUsb();
        }
    }
}

// Handle manual page sending
if (isset($_POST["button2"])) {
    // Selection options
    $selections = [
        'selection1' => ['0364594', '147650000', '512', 'off'],
        'selection2' => ['0364592', '157499990', '512', 'off'],
        'selection3' => ['1277794', '161437500', '512', 'off'],
        'selection4' => ['0644743', '148625000', '512', 'off'],
        'selection5' => ['0625253', '148625000', '512', 'off'],
        'selection6' => ['0627015', '148625000', '512', 'off'],
        'selection7' => ['0642575', '148625000', '512', 'off']
    ];

    $selectedOption = $_POST['dropdown'] ?? '';
    list($var1, $var2, $var3, $var4) = ['', '', '', ''];

    // Handle selection
    if (array_key_exists($selectedOption, $selections)) {
        list($var1, $var2, $var3, $var4) = $selections[$selectedOption];
    } elseif ($selectedOption === 'freetext') {
        $var1 = $_POST['freetext1'] ?? '';
        $var2 = $_POST['freetext2'] ?? '';
        $var3 = $_POST['freetext3'] ?? '';
        $var4 = isset($_POST['freetext4']) ? $_POST['freetext4'] : 'off';
    }

    $field1 = $_POST["stext"] ?? '';

    // Replace special characters
    $replaced_input = str_ireplace(
        ['å', 'ä', 'ö', 'Å', 'Ä', 'Ö'],
        ['a', 'a', 'o', 'A', 'A', 'O'],
        $field1
    );

    // --- Subnet restriction check ---
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $isWhitelisted = false;

    if ($enableSubnetRestriction) {
        foreach ($whitelistedSubnets as $subnet) {
            list($subnetIp, $subnetMask) = explode('/', $subnet);
            $subnetLong = ip2long($subnetIp);
            $ipLong = ip2long($ip);
            $maskLong = pow(2, 32) - pow(2, (32 - (int)$subnetMask));
            if (($ipLong & $maskLong) == ($subnetLong & $maskLong)) {
                $isWhitelisted = true;
                break;
            }
        }
        if (!$isWhitelisted) {
            echo "<pre><br><br><br><center>Access restricted to whitelisted subnets.</center></pre>";
            exit();
        }
    }

    // --- Optional restrictions (time, rate limit, duplicate) are removed ---

    // Build command
    $command = ($var4 === 'on') ?
        'sudo echo -e "' . $var1 . ':' . $replaced_input . '" | sudo ./pocsag -f "' . $var2 . '" -b 3 -r "' . $var3 . '" -t 3 -i' :
        'sudo echo -e "' . $var1 . ':' . $replaced_input . '" | sudo ./pocsag -f "' . $var2 . '" -b 3 -r "' . $var3 . '" -t 3';

    $output = shell_exec($command);
    if ($output !== null) {
        $currentID = logCommand($commandLogCounter, $output, [
            'message' => $field1,
            'capcode' => $var1,
            'frequency' => $var2,
            'baud' => $var3,
            'inversion' => $var4
        ]);
        $commandLogCounter++;
        //echo "ID: $currentID Capcode: $var1, Freq: $var2, Baud: $var3, Inversion: $var4 Message: $field1";
        runLogToUsb();
    }

    // Prepare display info
    $array2[] = "<div style='margin: 50px; font-family: Arial, sans-serif; text-align: center;'>
        <table style='width: 100%; border-collapse: collapse; margin: auto;'>
            <tr><td style='padding: 5px; text-align: right;'><strong>Message:</strong></td><td style='padding: 5px; text-align: left;'>$field1</td></tr>
            <tr><td style='padding: 5px; text-align: right;'><strong>Capcode:</strong></td><td style='padding: 5px; text-align: left;'>$var1</td></tr>
            <tr><td style='padding: 5px; text-align: right;'><strong>Frequency:</strong></td><td style='padding: 5px; text-align: left;'>$var2</td></tr>
            <tr><td style='padding: 5px; text-align: right;'><strong>Baud:</strong></td><td style='padding: 5px; text-align: left;'>$var3</td></tr>
            <tr><td style='padding: 5px; text-align: right;'><strong>Inversion:</strong></td><td style='padding: 5px; text-align: left;'>$var4</td></tr>
        </table>
    </div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SUPER DUPER POCSAG SERVICE</title>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
</head>
<body>
<center>
<form method="post" action="" onsubmit="showLoading()">
    <select name="dropdown" id="dropdown" onchange="toggleFreetextInputs()" style="font-size:24px">
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
    <input type="text" placeholder="Message" name="stext" style="font-size:24px" id="stext"><br>
    <br>
    <br>
    <input type="submit" value="Send Single Page" style="height:75px; width:400px; font-size:24px" name="button2">
    <br>
    <br>
    <br>
    <br>
    <input type="submit" value="Send to subscription list" style="height:75px; width:400px; font-size:24px" name="button1">
    <br>
    <br>
    <br>
    <br>
<input type="submit" value="Print Beer Purchase Receipt" style="height:75px; width:400px; font-size:24px" name="print_receipt">
</form>

<?php
// Output the accumulated display info
echo implode('', $array);
echo implode('', $array2);
?>

<script>
function toggleFreetextInputs() {
    const dropdown = document.getElementById('dropdown');
    const freetextInputs = document.getElementById('freetextInputs');
    freetextInputs.style.display = (dropdown.value === 'freetext') ? 'block' : 'none';
}
</script>
</center>
</body>
</html>
<!-- Loading spinner -->
<div id="loading" style="
    display:none;
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
    padding: 20px;
    background-color: rgba(0,0,0,0.8);
    color: #fff;
    font-size: 24px;
    border-radius: 10px;
    z-index: 9999;">
    Processing...
</div>
<script>
function showLoading() {
    document.getElementById('loading').style.display = 'block';
}
</script>
