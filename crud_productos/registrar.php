<?php
/**
 * registrar.php - Controlador principal
 * Recibe $_POST, ejecuta switch($accion) y retorna JSON
 * Acciones: Guardar | Modificar | Buscar | Listar | BuscarId
 */

// Asegurar respuesta en JSON puro — debe ir al inicio
header("Content-Type: application/json");

// Incluir clases
require_once "Modelo/Productos.php";

// Verificar que sea una petición POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido. Use POST.",
        "accion"  => "",
        "errors"  => []
    ]);
    exit;
}

// Leer la acción enviada desde el formulario
$accion = trim($_POST["Accion"] ?? "");

// ──────────────────────────────────────────────────────────────
// SWITCH PRINCIPAL DE ACCIONES
// ──────────────────────────────────────────────────────────────
switch ($accion) {

    // ── GUARDAR ───────────────────────────────────────────────
    case "Guardar":
        // Sanitizar y capturar datos del formulario
        $codigo   = htmlspecialchars(trim($_POST["codigo"]   ?? ""));
        $producto = htmlspecialchars(trim($_POST["producto"] ?? ""));
        $precio   = (float) ($_POST["precio"]   ?? 0);
        $cantidad = (int)   ($_POST["cantidad"] ?? 0);

        // Crear instancia y llamar al método guardar
        $p        = new Producto(0, $codigo, $producto, $precio, $cantidad);
        $response = $p->guardar();

        echo json_encode($response);
        break;

    // ── MODIFICAR ─────────────────────────────────────────────
    case "Modificar":
        $id       = (int)   ($_POST["id"]       ?? 0);
        $codigo   = htmlspecialchars(trim($_POST["codigo"]   ?? ""));
        $producto = htmlspecialchars(trim($_POST["producto"] ?? ""));
        $precio   = (float) ($_POST["precio"]   ?? 0);
        $cantidad = (int)   ($_POST["cantidad"] ?? 0);

        // Crear instancia con ID y llamar al método editar
        $p        = new Producto($id, $codigo, $producto, $precio, $cantidad);
        $response = $p->editar();

        echo json_encode($response);
        break;

    // ── BUSCAR ────────────────────────────────────────────────
    case "Buscar":
        $termino   = trim($_POST["termino"] ?? "");
        $productos = Producto::buscar($termino);

        echo json_encode([
            "success"   => true,
            "message"   => count($productos) . " producto(s) encontrado(s).",
            "accion"    => "Buscar",
            "productos" => $productos,
            "errors"    => []
        ]);
        break;

    // ── LISTAR (cargar tabla completa) ────────────────────────
    case "Listar":
        $productos = Producto::listar();

        echo json_encode([
            "success"   => true,
            "message"   => "Lista de productos cargada.",
            "accion"    => "Listar",
            "productos" => $productos,
            "errors"    => []
        ]);
        break;

    // ── BUSCAR POR ID (para cargar datos al editar) ───────────
    case "BuscarId":
        $id      = (int) ($_POST["id"] ?? 0);
        $product = Producto::obtenerPorId($id);

        if ($product) {
            echo json_encode([
                "success"  => true,
                "message"  => "Producto encontrado.",
                "accion"   => "BuscarId",
                "producto" => $product,
                "errors"   => []
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "No se encontró el producto con ID {$id}.",
                "accion"  => "BuscarId",
                "errors"  => ["Producto no encontrado."]
            ]);
        }
        break;

    // ── ACCIÓN DESCONOCIDA ────────────────────────────────────
    default:
        echo json_encode([
            "success" => false,
            "message" => "Acción no reconocida: '{$accion}'.",
            "accion"  => $accion,
            "errors"  => ["La acción enviada no existe en el servidor."]
        ]);
        break;
}

exit;
