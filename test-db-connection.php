<?php
/**
 * Script de diagnóstico de conexión a base de datos
 *
 * Sube este archivo a: /public_html/admin/public/test-db-connection.php
 * Accede via: https://admin.inmodb.net/test-db-connection.php
 *
 * IMPORTANTE: Elimina este archivo después de usarlo (contiene info sensible)
 */

echo "<h1>Diagnóstico de Conexión MySQL</h1>";
echo "<pre>";

// Cargar .env
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    die("❌ ERROR: Archivo .env no encontrado en: $envFile\n");
}

echo "✓ Archivo .env encontrado\n\n";

// Función para leer .env de Laravel
function parseEnvFile($filePath) {
    $env = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Saltar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Buscar líneas con formato KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remover comillas si existen
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }

            $env[$key] = $value;
        }
    }

    return $env;
}

// Leer variables del .env
$env = parseEnvFile($envFile);

$dbHost = $env['DB_HOST'] ?? 'NO CONFIGURADO';
$dbPort = $env['DB_PORT'] ?? '3306';
$dbDatabase = $env['DB_DATABASE'] ?? 'NO CONFIGURADO';
$dbUsername = $env['DB_USERNAME'] ?? 'NO CONFIGURADO';
$dbPassword = $env['DB_PASSWORD'] ?? 'NO CONFIGURADO';

echo "=== CONFIGURACIÓN ACTUAL ===\n";
echo "DB_HOST: $dbHost\n";
echo "DB_PORT: $dbPort\n";
echo "DB_DATABASE: $dbDatabase\n";
echo "DB_USERNAME: $dbUsername\n";
echo "DB_PASSWORD: " . (strlen($dbPassword) > 0 ? str_repeat('*', strlen($dbPassword)) : 'VACÍO') . "\n\n";

echo "=== INFORMACIÓN DEL SERVIDOR ===\n";
echo "Hostname del servidor: " . gethostname() . "\n";
echo "IP del servidor (SERVER_ADDR): " . ($_SERVER['SERVER_ADDR'] ?? 'NO DISPONIBLE') . "\n";
echo "IP del cliente (REMOTE_ADDR): " . ($_SERVER['REMOTE_ADDR'] ?? 'NO DISPONIBLE') . "\n\n";

echo "=== PRUEBA DE RESOLUCIÓN DNS ===\n";
$resolvedIP = gethostbyname($dbHost);
echo "Resolviendo '$dbHost' → $resolvedIP\n";
if ($resolvedIP === $dbHost && $dbHost !== 'localhost') {
    echo "⚠️  ADVERTENCIA: El hostname no se pudo resolver a una IP\n";
}
echo "\n";

echo "=== PRUEBA DE CONEXIÓN SOCKET ===\n";
$connection = @fsockopen($dbHost, $dbPort, $errno, $errstr, 5);
if ($connection) {
    echo "✓ Puerto $dbPort en $dbHost está ABIERTO\n";
    fclose($connection);
} else {
    echo "❌ ERROR: No se puede conectar al puerto $dbPort en $dbHost\n";
    echo "   Error ($errno): $errstr\n";
}
echo "\n";

echo "=== PRUEBA DE EXTENSIÓN MySQL ===\n";
if (extension_loaded('mysqli')) {
    echo "✓ Extensión mysqli está cargada\n";
} else {
    echo "❌ ERROR: Extensión mysqli NO está disponible\n";
}

if (extension_loaded('pdo_mysql')) {
    echo "✓ Extensión pdo_mysql está cargada\n";
} else {
    echo "❌ ERROR: Extensión pdo_mysql NO está disponible\n";
}
echo "\n";

echo "=== PRUEBA DE CONEXIÓN MySQL (mysqli) ===\n";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli($dbHost, $dbUsername, $dbPassword, $dbDatabase, $dbPort);
    echo "✓ CONEXIÓN EXITOSA usando mysqli\n";
    echo "  Versión del servidor MySQL: " . $mysqli->server_info . "\n";
    echo "  Charset actual: " . $mysqli->character_set_name() . "\n";

    // Verificar permisos
    $result = $mysqli->query("SHOW GRANTS");
    echo "\n=== PERMISOS DEL USUARIO ===\n";
    while ($row = $result->fetch_row()) {
        echo "  " . $row[0] . "\n";
    }

    $mysqli->close();
} catch (Exception $e) {
    echo "❌ ERROR DE CONEXIÓN: " . $e->getMessage() . "\n";
    echo "\nPOSIBLES CAUSAS:\n";
    echo "1. Usuario/contraseña incorrectos\n";
    echo "2. Base de datos no existe\n";
    echo "3. Usuario no tiene permisos desde este host\n";
    echo "4. Firewall bloqueando la conexión\n";
}
echo "\n";

echo "=== PRUEBA DE CONEXIÓN PDO ===\n";
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbDatabase;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ CONEXIÓN EXITOSA usando PDO\n";

    // Probar una consulta simple
    $stmt = $pdo->query("SELECT DATABASE() as db, USER() as user, @@hostname as hostname");
    $info = $stmt->fetch();
    echo "  Base de datos conectada: " . $info['db'] . "\n";
    echo "  Usuario conectado: " . $info['user'] . "\n";
    echo "  Hostname MySQL: " . $info['hostname'] . "\n";

} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN PDO: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== HOSTS PERMITIDOS PARA EL USUARIO ===\n";
echo "Para verificar qué hosts pueden conectarse, ejecuta en MySQL:\n";
echo "  SELECT host, user FROM mysql.user WHERE user = '$dbUsername';\n\n";

echo "=== SOLUCIÓN RECOMENDADA ===\n";
echo "Si la conexión falla, necesitas en cPanel → Remote MySQL:\n";
echo "1. Agregar: localhost\n";
echo "2. Agregar: 127.0.0.1\n";
echo "3. Agregar: " . ($_SERVER['SERVER_ADDR'] ?? 'IP_SERVIDOR') . "\n";
echo "4. Agregar el hostname del servidor: " . gethostname() . "\n";
echo "5. O usar el comodín: % (permite desde cualquier host - menos seguro)\n\n";

echo "=== IMPORTANTE ===\n";
echo "⚠️  ELIMINA ESTE ARCHIVO después de usarlo:\n";
echo "    rm /home/u403607455/domains/inmodb.net/public_html/admin/public/test-db-connection.php\n";
echo "</pre>";
