<?php

$config = require __DIR__ . '/../config/install.php';

$dbHost = $config['db_host'];
$dbUser = $config['db_user'];
$dbPass = $config['db_pass'];
$dbName = $config['db_name'];
$charset = $config['charset'];
$collation = $config['collation'];

$dsn = "mysql:host=$dbHost;charset=$charset";

$results = [];
$errors = [];
$installed = false;

if (isset($_GET['run']) && $_GET['run'] === '1') {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET $charset COLLATE $collation");
        $results[] = "Database <strong>$dbName</strong> created or already exists.";

        $pdo->exec("USE `$dbName`");

        $schemaPath = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new RuntimeException('schema.sql not found');
        }

        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new RuntimeException('Failed to read schema.sql');
        }

        $pdo->exec($schema);
        $results[] = 'Schema imported successfully.';

        $seedPath = __DIR__ . '/../database/seed.sql';
        if (file_exists($seedPath)) {
            $seed = file_get_contents($seedPath);
            if ($seed !== false) {
                $statements = array_filter(array_map('trim', explode(";\n", $seed)));
                foreach ($statements as $statement) {
                    if ($statement !== '') {
                        $pdo->exec($statement);
                    }
                }
                $results[] = 'Seed data imported successfully.';
            }
        } else {
            $results[] = 'seed.sql not found, skipping seed data.';
        }

        $results[] = 'Installation completed successfully.';
        $installed = true;
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    } catch (Throwable $e) {
        $errors[] = 'Error: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Installation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f5f5f5;
            padding: 40px;
            direction: rtl;
            text-align: right;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
        }
        .error {
            color: #721c24;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
        }
        .info {
            color: #004085;
            background: #cce5ff;
            border: 1px solid #b8daff;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
        }
        .install-btn {
            display: inline-block;
            padding: 14px 28px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: #28a745;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
        }
        .install-btn:hover {
            background: #218838;
        }
        .config-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .config-info code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Installation</h1>

        <div class="config-info">
            <strong>Configuration:</strong><br>
            Host: <code><?php echo htmlspecialchars($dbHost); ?></code><br>
            Database: <code><?php echo htmlspecialchars($dbName); ?></code><br>
            User: <code><?php echo htmlspecialchars($dbUser); ?></code>
        </div>

        <?php if (!$installed && empty($errors)): ?>
            <p>Click the button below to install the database schema and seed data.</p>
            <a href="?run=1" class="install-btn">Start Installation</a>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <?php foreach ($results as $result): ?>
                <div class="success">✔ <?php echo $result; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error">✖ <?php echo $error; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($errors) && !empty($results)): ?>
            <div class="info">
                <strong>Next steps:</strong><br>
                1. Delete or rename this file (install.php) for security.<br>
                2. Access your application at the root URL.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
