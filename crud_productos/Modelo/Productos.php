<?php
require_once "conexion.php";

/**
 * Clase Producto - Maneja las operaciones CRUD de productos
 * Contiene propiedades, validaciones y métodos de acceso a BD
 */
class Producto {
    // Propiedades del producto
    private int    $id;
    private string $codigo;
    private string $producto;
    private float  $precio;
    private int    $cantidad;

    // Instancia de la BD
    private DB $db;

    /**
     * Constructor: inicializa propiedades y conexión DB
     */
    public function __construct(
        int    $id       = 0,
        string $codigo   = "",
        string $producto = "",
        float  $precio   = 0.0,
        int    $cantidad = 0
    ) {
        $this->id       = $id;
        $this->codigo   = trim($codigo);
        $this->producto = trim($producto);
        $this->precio   = $precio;
        $this->cantidad = $cantidad;
        $this->db       = DB::obtenerInstancia();
    }

    // ──────────────────────────────────────────────
    // GETTERS Y SETTERS
    // ──────────────────────────────────────────────

    public function getId(): int       { return $this->id; }
    public function getCodigo(): string { return $this->codigo; }
    public function getProducto(): string { return $this->producto; }
    public function getPrecio(): float  { return $this->precio; }
    public function getCantidad(): int  { return $this->cantidad; }

    public function setId(int $id): void           { $this->id = $id; }
    public function setCodigo(string $c): void      { $this->codigo = trim($c); }
    public function setProducto(string $p): void    { $this->producto = trim($p); }
    public function setPrecio(float $p): void       { $this->precio = $p; }
    public function setCantidad(int $c): void       { $this->cantidad = $c; }

    // ──────────────────────────────────────────────
    // VALIDACIÓN
    // ──────────────────────────────────────────────

    /**
     * Valida los campos del producto
     * @param bool $esNuevo true = Guardar (cantidad mínima 1), false = Editar (cantidad mínima 0)
     * @return array Lista de errores; vacío si todo es válido
     */
    public function validar(bool $esNuevo = true): array {
        $errores = [];

        // Validar código
        if (empty($this->codigo)) {
            $errores[] = "El código del producto es obligatorio.";
        } elseif (strlen($this->codigo) > 20) {
            $errores[] = "El código no puede superar 20 caracteres.";
        }

        // Validar nombre del producto
        if (empty($this->producto)) {
            $errores[] = "El nombre del producto es obligatorio.";
        } elseif (strlen($this->producto) > 100) {
            $errores[] = "El nombre del producto no puede superar 100 caracteres.";
        }

        // Validar precio
        if ($this->precio <= 0) {
            $errores[] = "El precio debe ser mayor a 0.";
        }

        // Validar cantidad según operación
        $minCantidad = $esNuevo ? 1 : 0;
        if ($this->cantidad < $minCantidad) {
            $msg = $esNuevo
                ? "La cantidad debe ser al menos 1 al registrar un nuevo producto."
                : "La cantidad no puede ser negativa.";
            $errores[] = $msg;
        }

        return $errores;
    }

    // ──────────────────────────────────────────────
    // OPERACIONES CRUD
    // ──────────────────────────────────────────────

    /**
     * Guarda un nuevo producto en la BD
     * @return array Respuesta con success, message, accion y errors
     */
    public function guardar(): array {
        // Validar antes de guardar
        $errores = $this->validar(true);
        if (!empty($errores)) {
            return [
                "success" => false,
                "message" => "No se pudo guardar el producto.",
                "accion"  => "Guardar",
                "errors"  => $errores
            ];
        }

        // Verificar si ya existe el código
        $existente = $this->db->query(
            "SELECT id FROM productos WHERE codigo = ?",
            [$this->codigo]
        );
        if (!empty($existente)) {
            return [
                "success" => false,
                "message" => "Ya existe un producto con ese código.",
                "accion"  => "Guardar",
                "errors"  => ["El código '{$this->codigo}' ya está registrado."]
            ];
        }

        // Insertar en BD
        $sql = "INSERT INTO productos (codigo, producto, precio, cantidad)
                VALUES (?, ?, ?, ?)";
        $nuevoId = $this->db->insertSeguro($sql, [
            $this->codigo,
            $this->producto,
            $this->precio,
            $this->cantidad
        ]);

        if ($nuevoId !== false && $nuevoId > 0) {
            return [
                "success" => true,
                "message" => "Producto guardado correctamente.",
                "accion"  => "Guardar",
                "id"      => $nuevoId,
                "errors"  => []
            ];
        }

        return [
            "success" => false,
            "message" => "Error al guardar el producto en la base de datos.",
            "accion"  => "Guardar",
            "errors"  => ["Fallo en la operación INSERT."]
        ];
    }

    /**
     * Edita un producto existente en la BD
     * @return array Respuesta con success, message, accion y errors
     */
    public function editar(): array {
        // Validar ID
        if ($this->id <= 0) {
            return [
                "success" => false,
                "message" => "ID inválido para editar.",
                "accion"  => "Modificar",
                "errors"  => ["Se requiere un ID válido para modificar."]
            ];
        }

        // Validar campos (cantidad mínima 0 al editar)
        $errores = $this->validar(false);
        if (!empty($errores)) {
            return [
                "success" => false,
                "message" => "No se pudo actualizar el producto.",
                "accion"  => "Modificar",
                "errors"  => $errores
            ];
        }

        // Verificar que el código no pertenezca a otro producto
        $existente = $this->db->query(
            "SELECT id FROM productos WHERE codigo = ? AND id != ?",
            [$this->codigo, $this->id]
        );
        if (!empty($existente)) {
            return [
                "success" => false,
                "message" => "El código ya está en uso por otro producto.",
                "accion"  => "Modificar",
                "errors"  => ["El código '{$this->codigo}' ya está registrado en otro producto."]
            ];
        }

        // Actualizar en BD
        $sql = "UPDATE productos
                SET codigo = ?, producto = ?, precio = ?, cantidad = ?
                WHERE id = ?";
        $ok = $this->db->updateSeguro($sql, [
            $this->codigo,
            $this->producto,
            $this->precio,
            $this->cantidad,
            $this->id
        ]);

        if ($ok) {
            return [
                "success" => true,
                "message" => "Producto actualizado correctamente.",
                "accion"  => "Modificar",
                "errors"  => []
            ];
        }

        return [
            "success" => false,
            "message" => "No se encontró el producto o no hubo cambios.",
            "accion"  => "Modificar",
            "errors"  => ["Fallo en la operación UPDATE."]
        ];
    }

    /**
     * Busca productos por código o nombre
     * @param string $termino Texto de búsqueda
     * @return array Lista de productos encontrados
     */
    public static function buscar(string $termino): array {
        $db = DB::obtenerInstancia();
        $like = "%" . trim($termino) . "%";
        $sql  = "SELECT id, codigo, producto, precio, cantidad
                 FROM productos
                 WHERE codigo LIKE ? OR producto LIKE ?
                 ORDER BY id DESC";
        return $db->query($sql, [$like, $like]);
    }

    /**
     * Lista todos los productos
     * @return array Todos los registros de productos
     */
    public static function listar(): array {
        $db  = DB::obtenerInstancia();
        $sql = "SELECT id, codigo, producto, precio, cantidad
                FROM productos
                ORDER BY id DESC";
        return $db->query($sql);
    }

    /**
     * Obtiene un producto por su ID
     * @param int $id
     * @return array|null Datos del producto o null
     */
    public static function obtenerPorId(int $id): ?array {
        $db  = DB::obtenerInstancia();
        $sql = "SELECT id, codigo, producto, precio, cantidad
                FROM productos WHERE id = ?";
        $resultado = $db->query($sql, [$id]);
        return $resultado[0] ?? null;
    }
}
