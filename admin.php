<?php
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

// Load subnets
$subnets = [];
if (file_exists($subnetsFile)) {
    $subnets = json_decode(file_get_contents($subnetsFile), true);
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
<title>Subnet Restriction Management</title>
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
<h2>Subnet Restriction Settings</h2>

<form method="post">
    <label>Restriction Status:</label>
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
</body>
</html>
