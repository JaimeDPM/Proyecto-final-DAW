<?php
/**
 * config.php
 * Configuración de la conexión a la base de datos MySQL.
 * Utiliza PDO con preparación de consultas para prevenir inyección SQL.
 * La función getDB() implementa el patrón Singleton para reutilizar la conexión.
 */
define('DB_HOST',    'gmadm1042.siteground.biz');
define('DB_NAME',    'db1nfackwku24n');
define('DB_USER',    'uroylhqjhj0eo');
define('DB_PASS',    'JaimeEduca1.');
define('DB_CHARSET', 'utf8mb4');

define('PDF_TEMPLATE',       __DIR__ . '/../pdf/Declaracion_responsable_gancho.pdf');
define('PLANTILLAS_WORD_DIR', __DIR__ . '/../plantillas/');

date_default_timezone_set('Europe/Madrid');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
