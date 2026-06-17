<?php
/**
 * Clase DB - Conexión segura a MySQL mediante PDO
 * Patrón Singleton para evitar múltiples conexiones
 */
class DB {
    // Configuración de la base de datos
    private static $host     = "localhost";
    private static $dbname   = "productosdb";
    private static $user     = "root";
    private static $password = "";
    private static $charset  = "utf8mb4";

    // Instancia única (Singleton)
    private static $instancia = null;
    private $pdo;

    /**
     * Constructor privado: establece la conexión PDO
     */
    private function __construct() {
        $dsn = "mysql:host=" . self::$host .
               ";dbname=" . self::$dbname .
               ";charset=" . self::$charset;

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, self::$user, self::$password, $opciones);
        } catch (PDOException $e) {
            // Retorna JSON de error y termina
            header("Content-Type: application/json");
            echo json_encode([
                "success" => false,
                "message" => "Error de conexión: " . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Obtiene la instancia única de DB (Singleton)
     * @return DB
     */
    public static function obtenerInstancia(): DB {
        if (self::$instancia === null) {
            self::$instancia = new DB();
        }
        return self::$instancia;
    }

    /**
     * Ejecuta un INSERT seguro con parámetros preparados
     * @param string $sql   Consulta SQL con placeholders
     * @param array  $params Valores a enlazar
     * @return int|false    ID del registro insertado o false
     */
    public function insertSeguro(string $sql, array $params = []): int|false {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Ejecuta un UPDATE seguro con parámetros preparados
     * @param string $sql   Consulta SQL con placeholders
     * @param array  $params Valores a enlazar
     * @return bool         true si afectó filas, false en caso contrario
     */
    public function updateSeguro(string $sql, array $params = []): bool {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Ejecuta una consulta SELECT
     * @param string $sql   Consulta SQL con placeholders
     * @param array  $params Valores a enlazar
     * @return array        Resultado de la consulta
     */
    public function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
