<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['setBold'])) {
        // Command to set bold text
//        $escapeSequenceBold = "\x1B\x45\x01";
//        $escapedSequenceBold = escapeshellarg($escapeSequenceBold);
//        $cmdBold = "echo $escapedSequenceBold | sudo tee /dev/usb/lp0 > /dev/null";
//        shell_exec($cmdBold);
	$cmd = 'sudo printf "\x1B\x45\x01" | sudo tee /dev/usb/lp0 > /dev/null';
	shell_exec($cmd);
    }
    if (isset($_POST['setRegular'])) {
        // Command to reset to regular text
	$cmd = 'sudo printf "\x1B\x40" | sudo tee /dev/usb/lp0 > /dev/null';
	shell_exec($cmd);
    }
}
// Load inventory.json
$inventoryFile = 'inventory.json';
$inventoryItems = [];
if (file_exists($inventoryFile)) {
    $inventoryData = json_decode(file_get_contents($inventoryFile), true);
    if (isset($inventoryData['items'])) {
        $inventoryItems = $inventoryData['items'];
    }
}

// Helper function to check if item is in JSON array by id
function isInJson($itemId, $jsonArray) {
    foreach ($jsonArray as $item) {
        if (isset($item['id']) && $item['id'] == $itemId) {
            return true;
        }
    }
    return false;
}

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
    if (!isset($dropdownOptions['items'])) {
        $dropdownOptions['items'] = [];
    }
}

// Load settings and subnets
$configFile = 'config.json';
$subnetsFile = 'subnets.json';

$settings = [];
if (file_exists($configFile)) {
    $settings = json_decode(file_get_contents($configFile), true);
}
$settings['enableSubnetRestriction'] = $settings['enableSubnetRestriction'] ?? true;
$settings['enableMessageDuplicateRestriction'] = $settings['enableMessageDuplicateRestriction'] ?? false;

$subnets = [];
if (file_exists($subnetsFile)) {
    $subnets = json_decode(file_get_contents($subnetsFile), true);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toggle restrictions
    if (isset($_POST['toggleRestriction'])) {
        $settings['enableSubnetRestriction'] = ($_POST['toggleRestriction'] === 'enable');
        file_put_contents($configFile, json_encode($settings, JSON_PRETTY_PRINT));
        header('Location: admin.php');
        exit;
    }
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

    // Add/remove from dropdown
    if (isset($_POST['toggleDropdown'])) {
        $itemId = intval($_POST['toggleDropdown']);
        $action = $_POST['action']; // 'add' or 'remove'
        $itemToToggle = null;
        foreach ($inventoryItems as $item) {
            if ($item['id'] == $itemId) {
                $itemToToggle = $item;
                break;
            }
        }
        if ($itemToToggle) {
            if ($action === 'add') {
                if (!isInJson($itemId, $dropdownOptions['items'])) {
                    $dropdownOptions['items'][] = $itemToToggle;
                    file_put_contents($dropdownOptionsFile, json_encode($dropdownOptions, JSON_PRETTY_PRINT));
                }
            } elseif ($action === 'remove') {
                foreach ($dropdownOptions['items'] as $key => $option) {
                    if (isset($option['id']) && $option['id'] == $itemId) {
                        unset($dropdownOptions['items'][$key]);
                        $dropdownOptions['items'] = array_values($dropdownOptions['items']);
                        file_put_contents($dropdownOptionsFile, json_encode($dropdownOptions, JSON_PRETTY_PRINT));
                        break;
                    }
                }
            }
        }
        header('Location: admin.php');
        exit;
    }

    // Add/remove from subscription
    if (isset($_POST['toggleSubscription'])) {
        $itemId = intval($_POST['toggleSubscription']);
        $action = $_POST['action']; // 'add' or 'remove'
        $itemToToggle = null;
        foreach ($inventoryItems as $item) {
            if ($item['id'] == $itemId) {
                $itemToToggle = $item;
                break;
            }
        }
        if ($itemToToggle) {
            if ($action === 'add') {
                if (!isInJson($itemId, $items)) {
                    $items[] = $itemToToggle;
                    file_put_contents($itemsFile, json_encode(['items' => $items], JSON_PRETTY_PRINT));
                }
            } elseif ($action === 'remove') {
                foreach ($items as $index => $subItem) {
                    if (isset($subItem['id']) && $subItem['id'] == $itemId) {
                        unset($items[$index]);
                        $items = array_values($items);
                        file_put_contents($itemsFile, json_encode(['items' => $items], JSON_PRETTY_PRINT));
                        break;
                    }
                }
            }
        }
        header('Location: admin.php');
        exit;
    }

    // Add new subnet
    if (isset($_POST['newSubnet'])) {
        $newSubnet = trim($_POST['newSubnet']);
        if ($newSubnet !== '' && filter_var(substr($newSubnet, 0, strrpos($newSubnet, '/')), FILTER_VALIDATE_IP)) {
            $parts = explode('/', $newSubnet);
            if (count($parts) === 2 && filter_var($parts[0], FILTER_VALIDATE_IP) && is_numeric($parts[1]) && (int)$parts[1] >= 0 && (int)$parts[1] <= 32) {
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
            $subnets = array_values($subnets);
            file_put_contents($subnetsFile, json_encode($subnets, JSON_PRETTY_PRINT));
        }
        header('Location: admin.php');
        exit;
    }

    // Add new item
if (isset($_POST['addItem'])) {
    $capcode = trim($_POST['itemCapcode']);
    $frequency = trim($_POST['itemFrequency']);
    $baud = trim($_POST['itemBaud']);
    $inversion = trim($_POST['itemInversion']);

    if ($capcode !== '' && $frequency !== '' && $baud !== '' && $inversion !== '') {
        // Load existing data
        $jsonData = [];
        if (file_exists($inventoryFile)) {
            $jsonContent = file_get_contents($inventoryFile);
            $jsonData = json_decode($jsonContent, true);
            if ($jsonData === null) {
                $jsonData = ['items' => []]; // fallback if JSON is invalid
            }
        } else {
            $jsonData = ['items' => []]; // initialize if file doesn't exist
        }

        $items = isset($jsonData['items']) ? $jsonData['items'] : [];

        // Determine new ID
        $newId = 1;
        if (!empty($items)) {
            $ids = array_column($items, 'id');
            $newId = max($ids) + 1;
        }

        // Create new item
        $newItem = [
            'id' => $newId,
            'capcode' => $capcode,
            'frequency' => $frequency,
            'baud' => $baud,
            'inversion' => $inversion
        ];

        // Append new item
        $items[] = $newItem;

        // Save back to file
        $jsonData['items'] = $items;
        file_put_contents($inventoryFile, json_encode($jsonData, JSON_PRETTY_PRINT));
    }
    header('Location: admin.php');
    exit;
}
if (isset($_POST['removeItem'])) {
    $removeId = intval($_POST['removeItem']);

    // Remove from inventoryItems
    foreach ($inventoryItems as $index => $item) {
        if ($item['id'] === $removeId) {
            unset($inventoryItems[$index]);
            break;
        }
    }
    $inventoryItems = array_values($inventoryItems);
    // Save updated inventory
    file_put_contents($inventoryFile, json_encode(['items' => $inventoryItems], JSON_PRETTY_PRINT));

    // Remove from dropdownOptions['items']
    foreach ($dropdownOptions['items'] as $key => $dropItem) {
        if (isset($dropItem['id']) && $dropItem['id'] == $removeId) {
            unset($dropdownOptions['items'][$key]);
            $dropdownOptions['items'] = array_values($dropdownOptions['items']);
            break;
        }
    }
    // Save updated dropdown options
    file_put_contents($dropdownOptionsFile, json_encode($dropdownOptions, JSON_PRETTY_PRINT));

    // Remove from subscription list ($items)
    foreach ($items as $index => $subItem) {
        if (isset($subItem['id']) && $subItem['id'] == $removeId) {
            unset($items[$index]);
            $items = array_values($items);
            break;
        }
    }
    // Save updated subscription list
    file_put_contents($itemsFile, json_encode(['items' => $items], JSON_PRETTY_PRINT));

    header('Location: admin.php');
    exit;
}
    // Add new dropdown selection
    if (isset($_POST['newSelectionCapcode'])) {
        $capcode = trim($_POST['newSelectionCapcode']);
        $frequency = trim($_POST['newSelectionFrequency']);
        $baud = trim($_POST['newSelectionBaud']);
        $inversion = trim($_POST['newSelectionInversion']);
        if ($capcode !== '' && $frequency !== '' && $baud !== '' && $inversion !== '') {
            $key = 'selection' . (count($dropdownOptions['items']) + 1);
            $dropdownOptions['items'][$key] = [
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
        if (isset($dropdownOptions['items'][$removeKey])) {
            unset($dropdownOptions['items'][$removeKey]);
            file_put_contents($dropdownOptionsFile, json_encode($dropdownOptions, JSON_PRETTY_PRINT));
        }
        header('Location: admin.php');
        exit;
    }

    // Update inventory item fields
    if (isset($_POST['updateItem'])) {
        $itemId = intval($_POST['updateItem']);
        // Find the item
        foreach ($inventoryItems as &$item) {
            if ($item['id'] == $itemId) {
                // Update fields based on POST data
                if (isset($_POST['field_capcode'])) {
                    $item['capcode'] = $_POST['field_capcode'];
                }
                if (isset($_POST['field_frequency'])) {
                    $item['frequency'] = $_POST['field_frequency'];
                }
                if (isset($_POST['field_baud'])) {
                    $item['baud'] = $_POST['field_baud'];
                }
                if (isset($_POST['field_inversion'])) {
                    $item['inversion'] = $_POST['field_inversion'];
                }
                if (isset($_POST['field_description'])) {
                    $item['description'] = $_POST['field_description'];
                }
                // After updating, check if item exists in dropdownOptions or subscription list
                // If exists, update those as well
                if (isInJson($itemId, $dropdownOptions['items'])) {
                    foreach ($dropdownOptions['items'] as &$dropItem) {
                        if (isset($dropItem['id']) && $dropItem['id'] == $itemId) {
                            $dropItem['capcode'] = $item['capcode'];
                            $dropItem['frequency'] = $item['frequency'];
                            $dropItem['baud'] = $item['baud'];
                            $dropItem['inversion'] = $item['inversion'];
                            $dropItem['description'] = $item['description'];
                            break;
                        }
                    }
                }
                if (isInJson($itemId, $items)) {
                    foreach ($items as &$subItem) {
                        if (isset($subItem['id']) && $subItem['id'] == $itemId) {
                            $subItem['capcode'] = $item['capcode'];
                            $subItem['frequency'] = $item['frequency'];
                            $subItem['baud'] = $item['baud'];
                            $subItem['inversion'] = $item['inversion'];
                            $subItem['description'] = $item['description'];
                            break;
                        }
                    }
                }
                break;
            }
        }
        // Save inventory.json
        file_put_contents($inventoryFile, json_encode(['items' => $inventoryItems], JSON_PRETTY_PRINT));
        // Save dropdown options if updated
        file_put_contents($dropdownOptionsFile, json_encode($dropdownOptions, JSON_PRETTY_PRINT));
        // Save subscription list if updated
        file_put_contents($itemsFile, json_encode(['items' => $items], JSON_PRETTY_PRINT));
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
label { display: inline-block; width: 200px; font-weight: bold; vertical-align: top; }
input[type=text] { width: 200px; padding: 5px; }
button { padding: 5px 10px; margin-left: 10px; }
.item-container { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
.editable { width: 200px; }
</style>
</head>
<body>
<div style="margin-bottom:20px;">
    <!-- Set Bold Text Button -->
    <form method="post" style="display:inline;">
        <button type="submit" name="setBold">Set Bold Text</button>
    </form>
    <!-- Set Regular Text Button -->
    <form method="post" style="display:inline;">
        <button type="submit" name="setRegular">Set Regular Text</button>
    </form>
</div>

<h2>Message Duplicate Restriction</h2>
<form method="post">
    <input type="hidden" name="action" value="toggleMessageDuplicateRestriction" />
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
<h2>Add New Inventory Item</h2>
<form method="post">
    <label>Capcode:</label><input type="text" name="itemCapcode" required><br>
    <label>Frequency:</label><input type="text" name="itemFrequency" required><br>
    <label>Baud:</label><input type="text" name="itemBaud" required><br>
    <label>Inversion:</label><input type="text" name="itemInversion" required><br>
    <button type="submit" name="addItem">Add Item</button>
</form>
<h2>Inventory Items</h2>
<?php foreach ($inventoryItems as $item): ?>
    <?php
        $itemId = $item['id'];
        $inDropdown = isInJson($itemId, $dropdownOptions['items']);
        $inSubscription = isInJson($itemId, $items);
    ?>
    <div class="item-container">
        <form method="post" style="margin-bottom:10px;">
            <input type="hidden" name="updateItem" value="<?php echo $itemId; ?>">
            <div>
                <label>ID:</label>
                <span><?php echo htmlspecialchars($itemId); ?></span>
            </div>
            <div>
                <label>Capcode:</label>
                <input type="text" name="field_capcode" class="editable" value="<?php echo htmlspecialchars($item['capcode']); ?>">
            </div>
            <div>
                <label>Frequency:</label>
                <input type="text" name="field_frequency" class="editable" value="<?php echo htmlspecialchars($item['frequency']); ?>">
            </div>
            <div>
                <label>Baud:</label>
                <input type="text" name="field_baud" class="editable" value="<?php echo htmlspecialchars($item['baud']); ?>">
            </div>
            <div>
                <label>Inversion:</label>
                <input type="text" name="field_inversion" class="editable" value="<?php echo htmlspecialchars($item['inversion']); ?>">
            </div>
            <div>
                <label>Description:</label>
                <input type="text" name="field_description" class="editable" value="<?php echo htmlspecialchars($item['description']); ?>">
            </div>
            <button type="submit">Update Fields</button>
        </form>
        <!-- Delete button for this item -->
        <form method="post" style="display:inline;">
            <input type="hidden" name="removeItem" value="<?php echo $itemId; ?>">
            <button type="submit" onclick="return confirm('Are you sure you want to delete this item?');">Delete Item</button>
        </form>
        <!-- Toggle Dropdown -->
        <form method="post" style="display:inline;">
            <input type="hidden" name="toggleDropdown" value="<?php echo $itemId; ?>">
            <input type="hidden" name="action" value="<?php echo $inDropdown ? 'remove' : 'add'; ?>">
            <button type="submit"><?php echo $inDropdown ? 'Remove from Dropdown' : 'Add to Dropdown'; ?></button>
        </form>
        <!-- Toggle Subscription -->
        <form method="post" style="display:inline;">
            <input type="hidden" name="toggleSubscription" value="<?php echo $itemId; ?>">
            <input type="hidden" name="action" value="<?php echo $inSubscription ? 'remove' : 'add'; ?>">
            <button type="submit"><?php echo $inSubscription ? 'Remove from Subscription' : 'Add to Subscription'; ?></button>
        </form>
    </div>
<?php endforeach; ?>
</body>
</html>
