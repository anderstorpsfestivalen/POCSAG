<?php
// Load items.json
$itemsFile = 'items.json';
$items = [];
if (file_exists($itemsFile)) {
    $itemsData = json_decode(file_get_contents($itemsFile), true);
    if (isset($itemsData['items'])) {
        $items = $itemsData['items'];
    }
}
// Load dropdown options
$dropdownOptionsFile = 'dropdown_options.json';
$dropdownOptions = [];
if (file_exists($dropdownOptionsFile)) {
    $dropdownOptions = json_decode(file_get_contents($dropdownOptionsFile), true);
    if (!isset($dropdownOptions['selections'])) {
        $dropdownOptions['selections'] = [];
    }
}

// admin.php - Manage IP subnet restrictions

// Paths to config and subnets files
$configFile = 'config.json';
$subnetsFile = 'subnets.json';

// Load current settings
$settings = [];
if (file_exists($configFile)) {
    $settings = json_decode(file_get_contents($configFile), true);
}
if (!isset($settings['enableSubnetRestriction'])) {
    $settings['enableSubnetRestriction'] = true;
}
if (!isset($settings['enableMessageDuplicateRestriction'])) {
    $settings['enableMessageDuplicateRestriction'] = false; 
}
// Load subnets
$subnets = [];
if (file_exists($subnetsFile)) {
    $subnets = json_decode(file_get_contents($subnetsFile), true);
}

// Add new item
if (isset($_POST['addItem'])) {
    $capcode = trim($_POST['itemCapcode']);
    $frequency = trim($_POST['itemFrequency']);
    $baud = trim($_POST['itemBaud']);
    $inversion = trim($_POST['itemInversion']);

    if ($capcode !== '' && $frequency !== '' && $baud !== '' && $inversion !== '') {
        // Generate new id
        $newId = 1;
        if (!empty($items)) {
            $ids = array_column($items, 'id');
            $newId = max($ids) + 1;
        }
        $newItem = [
            'id' => $newId,
            'capcode' => $capcode,
            'frequency' => $frequency,
            'baud' => $baud,
            'inversion' => $inversion
        ];
        $items[] = $newItem;
        // Save back to JSON
        file_put_contents($itemsFile, json_encode(['items' => $items], JSON_PRETTY_PRINT));
    }
    header('Location: admin.php');
    exit;
}

// Remove item
if (isset($_POST['removeItem'])) {
    $removeId = intval($_POST['removeItem']);
    foreach ($items as $index => $item) {
        if ($item['id'] === $removeId) {
            unset($items[$index]);
            break;
        }
    }
    // Reindex array
    $items = array_values($items);
    // Save changes
    file_put_contents($itemsFile, json_encode(['items' => $items], JSON_PRETTY_PRINT));
    header('Location: admin.php');
    exit;
}



// Add new dropdown option
if (isset($_POST['newSelectionCapcode'])) {
    $capcode = trim($_POST['newSelectionCapcode']);
    $frequency = trim($_POST['newSelectionFrequency']);
    $baud = trim($_POST['newSelectionBaud']);
    $inversion = trim($_POST['newSelectionInversion']);

    if ($capcode !== '' && $frequency !== '' && $baud !== '' && $inversion !== '') {
        // Generate a unique key for the selection
        $key = 'selection' . (count($dropdownOptions['selections']) + 1);
        $dropdownOptions['selections'][$key] = [
            'capcode' => $capcode,
            'frequency' => $frequency,
            'baud' => $baud,
            'inversion' => $inversion
        ];
        file_put_contents($dropdownOptionsFile, json_encode($dropdownOptions, JSON_PRETTY_PRINT));
    }
    header('Location: admin.php');
    exit;
}

// Remove dropdown option
if (isset($_POST['removeSelection'])) {
    $removeKey = $_POST['removeSelection'];
    if (isset($dropdownOptions['selections'][$removeKey])) {
        unset($dropdownOptions['selections'][$removeKey]);
        file_put_contents($dropdownOptionsFile, json_encode($dropdownOptions, JSON_PRETTY_PRINT));
    }
    header('Location: admin.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toggle enable/disable
    if (isset($_POST['toggleRestriction'])) {
        $settings['enableSubnetRestriction'] = ($_POST['toggleRestriction'] === 'enable');
        file_put_contents($configFile, json_encode($settings, JSON_PRETTY_PRINT));
        header('Location: admin.php');
        exit;
    }
    // New toggle for rate limiting
    if (isset($_POST['toggleRateLimit'])) {
        $settings['enableRateLimit'] = ($_POST['toggleRateLimit'] === 'enable');
        file_put_contents($configFile, json_encode($settings, JSON_PRETTY_PRINT));
        header('Location: admin.php');
        exit;
    }
if (isset($_POST['action']) && $_POST['action'] === 'toggleMessageDuplicateRestriction') {
    $settings['enableMessageDuplicateRestriction'] = !$settings['enableMessageDuplicateRestriction'];
    file_put_contents($configFile, json_encode($settings, JSON_PRETTY_PRINT));
    header('Location: admin.php');
    exit;
}

    // Add new subnet
    if (isset($_POST['newSubnet'])) {
        $newSubnet = trim($_POST['newSubnet']);
        if ($newSubnet !== '' && filter_var(substr($newSubnet, 0, strrpos($newSubnet, '/')), FILTER_VALIDATE_IP)) {
            // Basic validation: check IP and CIDR
            $parts = explode('/', $newSubnet);
            if (count($parts) === 2 && filter_var($parts[0], FILTER_VALIDATE_IP) && is_numeric($parts[1]) && (int)$parts[1] >=0 && (int)$parts[1] <= 32) {
                if (!in_array($newSubnet, $subnets)) {
                    $subnets[] = $newSubnet;
                    file_put_contents($subnetsFile, json_encode($subnets, JSON_PRETTY_PRINT));
                }
            }
        }
        header('Location: admin.php');
        exit;
    }

    // Remove subnet
    if (isset($_POST['removeSubnet'])) {
        $removeSubnet = $_POST['removeSubnet'];
        $index = array_search($removeSubnet, $subnets);
        if ($index !== false) {
            unset($subnets[$index]);
            $subnets = array_values($subnets); // reindex
            file_put_contents($subnetsFile, json_encode($subnets, JSON_PRETTY_PRINT));
        }
        header('Location: admin.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>POCSAG Management</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
form { margin-bottom: 20px; }
label { display: inline-block; width: 200px; font-weight: bold; }
input[type=text] { width: 200px; padding: 5px; }
button { padding: 5px 10px; margin-left: 10px; }
</style>
</head>
<body>
<h2>Message Duplicate Restriction</h2>
<form method="post">
    <input type="hidden" name="action" value="toggleMessageDuplicateRestriction">
    <button type="submit">
        <?php echo $settings['enableMessageDuplicateRestriction'] ? 'Disable' : 'Enable'; ?> Message Duplicate Restriction
    </button>
</form>
<h2>Rate Limit Restriction Settings</h2>
<form method="post">
    <label>Rate Limit Restriction:</label>
    <button type="submit" name="toggleRateLimit" value="<?php echo $settings['enableRateLimit'] ? 'disable' : 'enable'; ?>">
        <?php echo $settings['enableRateLimit'] ? 'Disable' : 'Enable'; ?> Rate Limiting
    </button>
</form>


<h2>Subnet Restriction Settings</h2>

<form method="post">
    <label>Subnet Restriction Status:</label>
    <button type="submit" name="toggleRestriction" value="<?php echo $settings['enableSubnetRestriction'] ? 'disable' : 'enable'; ?>">
        <?php echo $settings['enableSubnetRestriction'] ? 'Disable' : 'Enable'; ?> Restriction
    </button>
</form>

<h2>Current Whitelisted Subnets</h2>
<ul>
<?php foreach ($subnets as $subnet): ?>
    <li>
        <?php echo htmlspecialchars($subnet); ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="removeSubnet" value="<?php echo htmlspecialchars($subnet); ?>">
            <button type="submit">Remove</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

<h2>Add New Subnet</h2>
<form method="post">
    <label>Subnet (e.g., 192.168.0.0/24):</label>
    <input type="text" name="newSubnet" placeholder="e.g., 192.168.0.0/24" required>
    <button type="submit">Add</button>
</form>
<h2>Add / Remove Dropdown Options</h2>

<h3>Current Selections</h3>
<ul>
<?php foreach ($dropdownOptions['selections'] as $key => $option): ?>
    <li>
        <strong><?php echo htmlspecialchars($key); ?>:</strong>
        Capcode: <?php echo htmlspecialchars($option['capcode']); ?>,
        Frequency: <?php echo htmlspecialchars($option['frequency']); ?>,
        Baud: <?php echo htmlspecialchars($option['baud']); ?>,
        Inversion: <?php echo htmlspecialchars($option['inversion']); ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="removeSelection" value="<?php echo htmlspecialchars($key); ?>">
            <button type="submit">Remove</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

<h3>Add New Selection</h3>
<form method="post">
    <label>Capcode:</label>
    <input type="text" name="newSelectionCapcode" placeholder="e.g., 0364594" required><br><br>
    <label>Frequency:</label>
    <input type="text" name="newSelectionFrequency" placeholder="e.g., 147650000" required><br><br>
    <label>Baud:</label>
    <input type="text" name="newSelectionBaud" placeholder="e.g., 512" required><br><br>
    <label>Inversion:</label>
    <input type="text" name="newSelectionInversion" placeholder="off/on" required><br><br>
    <button type="submit">Add Selection</button>
</form>
<h2>Subscription List</h2>

<h3>Current Items</h3>
<ul>
<?php foreach ($items as $item): ?>
    <li>
        <strong>ID: <?php echo htmlspecialchars($item['id']); ?>:</strong>
        Capcode: <?php echo htmlspecialchars($item['capcode']); ?>,
        Frequency: <?php echo htmlspecialchars($item['frequency']); ?>,
        Baud: <?php echo htmlspecialchars($item['baud']); ?>,
        Inversion: <?php echo htmlspecialchars($item['inversion']); ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="removeItem" value="<?php echo htmlspecialchars($item['id']); ?>">
            <button type="submit">Remove</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

<h3>Add New Item</h3>
<form method="post">
    <label>Capcode:</label>
    <input type="text" name="itemCapcode" placeholder="e.g., 0364594" required><br><br>
    <label>Frequency:</label>
    <input type="text" name="itemFrequency" placeholder="e.g., 147650000" required><br><br>
    <label>Baud:</label>
    <input type="text" name="itemBaud" placeholder="e.g., 512" required><br><br>
    <label>Inversion:</label>
    <input type="text" name="itemInversion" placeholder="off/on" required><br><br>
    <button type="submit" name="addItem">Add Item</button>
</form>
</body>
</html>
