<?php
// Function to log the "Print Beer Receipt" button presses
function logPrintBeerReceipt() {
    $logFile = 'print_receipt_log.json';

    // Read existing logs
    if (file_exists($logFile) && filesize($logFile) > 0) {
        $logs = json_decode(file_get_contents($logFile), true);
        if (!is_array($logs)) {
            $logs = [];
        }
    } else {
        $logs = [];
    }

    // Create a new log entry
    $newEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'Print Beer Receipt pressed'
    ];

    // Append and save
    $logs[] = $newEntry;
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}

// Function to get the last message sent
function getLastMessage() {
    $msgFile = 'messages.json';
    if (!file_exists($msgFile) || filesize($msgFile) == 0) {
        return '';
    }
    $data = json_decode(file_get_contents($msgFile), true);
    return isset($data['last_message']) ? $data['last_message'] : '';
}

// Function to set the last message sent
function setLastMessage($message) {
    $msgFile = 'messages.json';
    $data = ['last_message' => $message];
    file_put_contents($msgFile, json_encode($data));
}

function isDuplicateMessage($message, $config) {
    if (!$config['enableMessageDuplicateRestriction']) {
        return false; // restriction disabled
    }
    $lastMessage = getLastMessage();
    return ($message === $lastMessage);
}


$itemsFilePath = 'items.json';

$items = [];
if (file_exists($itemsFilePath)) {
    $jsonContent = file_get_contents($itemsFilePath);
    $data = json_decode($jsonContent, true);
    if (isset($data['items']) && is_array($data['items'])) {
        $items = $data['items'];
    }
}

// Load dropdown options from JSON file
$dropdownFile = 'dropdown_options.json';
$dropdownOptions = [];

if (file_exists($dropdownFile)) {
    $jsonContent = file_get_contents($dropdownFile);
    $data = json_decode($jsonContent, true);
    if (isset($data['selections']) && is_array($data['selections'])) {
        $dropdownOptions = $data['selections'];
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

function getLastCommandTimestamp() {
    $logFile = 'command_execution_log.json';

    if (!file_exists($logFile) || filesize($logFile) == 0) {
        return null;
    }

    $logs = json_decode(file_get_contents($logFile), true);
    if (is_array($logs) && !empty($logs)) {
        $lastEntry = end($logs);
        if (isset($lastEntry['timestamp'])) {
            return strtotime($lastEntry['timestamp']);
        }
    }
    return null;
}

function canPressButton() {
    global $enableRateLimit;
    if (!$enableRateLimit) {
        return true; // Rate limiting disabled
    }
    $lastTimestamp = getLastCommandTimestamp();
    if ($lastTimestamp === null) {
        return true; // No previous command, can proceed
    }
    $now = time();
    return (($now - $lastTimestamp) >= 60);
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




// Load config
$configPath = 'config.json';

//$enableRateLimit = true; // default
if (file_exists($configPath)) {
    $configData = json_decode(file_get_contents($configPath), true);
    if (isset($configData['enableRateLimit'])) {
        $enableRateLimit = $configData['enableRateLimit'];
    }
}

if (file_exists($configPath)) {
    $configData = json_decode(file_get_contents($configPath), true);
    if (isset($configData['enableMessageDuplicateRestriction'])) {
        $enableMsgDupRestriction  = $configData['enableMessageDuplicateRestriction'];
    }
}


$subnetsPath = 'subnets.json';

//$enableSubnetRestriction = true; // default
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

if (isset($_POST['print_receipt'])) {
    if (!canPressButton()) {
        echo "<script>
            alert('Please wait at least 60 seconds before pressing this button again.');
            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
        </script>";
        exit();
    }

    // Log the print receipt action into command log
    // You can log a generic message or details relevant to printing
    logCommand($commandLogCounter, null, [
        'message' => 'Print Beer Purchase Receipt',
        'action' => 'print_receipt'
    ]);
    $commandLogCounter++;

    // Proceed with the printing logic
    $filePath = '/var/www/dashboard/ascii';

    // Append empty lines
    $emptyLinesCount = 8;
    $emptyLines = str_repeat("\n", $emptyLinesCount);
    file_put_contents($filePath, $emptyLines, FILE_APPEND);

    // Path to the printer device
    $printerDevice = '/dev/usb/lp0'; // Adjust if different

    // Read the content to print
    $data = file_get_contents($filePath);

    // Write directly to the device
    $result = @file_put_contents($printerDevice, $data);

    if ($result === false) {
        echo "Failed to write to printer device.";
    } else {
        echo "Print job sent successfully.";
        logPrintBeerReceipt();
    }
}


$field1 = $_POST["stext"] ?? '';

// Replace special characters for compatibility
$replaced_input = str_ireplace(
    ['å', 'ä', 'ö', 'Å', 'Ä', 'Ö'],
    ['a', 'a', 'o', 'A', 'A', 'O'],
    $field1
);

// Handle "Send to subscription list" button
if (isset($_POST['button1'])) {
    if (!canPressButton()) {
        echo "<script>
            alert('Please wait at least 60 seconds before pressing this button again.');
            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
        </script>";
        exit();
    }
$messageToSend = $field1; // or assemble message parts as needed

    if (isDuplicateMessage($messageToSend, ['enableMessageDuplicateRestriction' => $enableMsgDupRestriction])) {
        echo "<script>
            alert('Duplicate message detected. Please change the message or wait before sending the same message again.');
            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
        </script>";
        exit();
    }


    foreach ($items as $item) {
if ($item['inversion'] == 'on') {
    $command = 'sudo echo -e "' . $item['capcode'] . ':' . $replaced_input . '" | sudo ./pocsag -f "' . $item['frequency'] . '" -b 3 -r "' . $item['baud'] . '" -t 3 -i';
} else {
    $command = 'sudo echo -e "' . $item['capcode'] . ':' . $replaced_input . '" | sudo ./pocsag -f "' . $item['frequency'] . '" -b 3 -r "' . $item['baud'] . '" -t 3';
}
        $output = shell_exec($command);
        if ($output !== null) {
            $currentID = logCommand($commandLogCounter, $output, [
                'message' => $field1,
                'capcode' => $item['capcode'],
                'frequency' => $item['frequency'],
                'baud' => $item['baud'],
                'inversion' => $item['inversion']
            ]);
            $commandLogCounter++;
      //echo "ID: $currentID Capcode: {$item['capcode']}, Freq: {$item['freq']}, Baud: {$item['baud']}, Inversion: {$item['inversion']} Message: $field1";
	runLogToUsb();
	setLastMessage($messageToSend);
        }
    }
}

// Handle manual page sending
if (isset($_POST["button2"])) {

    if (!canPressButton()) {
        echo "<script>
            alert('Please wait at least 60 seconds before pressing this button again.');
            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
        </script>";
        exit();
    }
$messageToSend = $field1; // or assemble message parts as needed

    if (isDuplicateMessage($messageToSend, ['enableMessageDuplicateRestriction' => $enableMsgDupRestriction])) {
        echo "<script>
            alert('Duplicate message detected. Please change the message or wait before sending the same message again.');
            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
        </script>";
        exit();
    }

$selectedOption = $_POST['dropdown'] ?? '';

$selections = $dropdownOptions; // from above

if (array_key_exists($selectedOption, $selections)) {
    $var1 = $selections[$selectedOption]['capcode'];
    $var2 = $selections[$selectedOption]['frequency'];
    $var3 = $selections[$selectedOption]['baud'];
    $var4 = $selections[$selectedOption]['inversion'];
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
	setLastMessage($messageToSend);
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
    <?php
    foreach ($dropdownOptions as $key => $option) {
        // Use the key as value, and display custom text
        echo "<option value=\"$key\">Pager: {$option['capcode']}</option>";
    }
    ?>
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
