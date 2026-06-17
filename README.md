# 📦 CRUD Productos — Fetch API + PHP OOP + MySQL

> Laboratorio Práctico · Desarrollo de Software VII  
> Universidad Tecnológica — Facultad de Ingeniería en Sistemas  
> Instructor: Ing. Irina Fong · I Semestre 2026

---

## 📋 Tabla de Contenidos

1. [Descripción](#descripción)
2. [Tecnologías utilizadas](#tecnologías-utilizadas)
3. [Estructura del proyecto](#estructura-del-proyecto)
4. [Requisitos previos](#requisitos-previos)
5. [Instalación y configuración](#instalación-y-configuración)
6. [Base de datos](#base-de-datos)
7. [Arquitectura y flujo de datos](#arquitectura-y-flujo-de-datos)
8. [Descripción de archivos](#descripción-de-archivos)
9. [API — Acciones disponibles](#api--acciones-disponibles)
10. [Reglas de negocio](#reglas-de-negocio)
11. [Rúbrica y criterios cubiertos](#rúbrica-y-criterios-cubiertos)

---

## Descripción

Aplicación web dinámica bajo arquitectura **cliente-servidor** que implementa las operaciones **CRUD** (Crear, Leer, Actualizar, Eliminar) sobre un catálogo de productos. La comunicación entre el frontend y el backend se realiza de forma **asíncrona** mediante la **Fetch API** de JavaScript, sin recargar la página.

Funcionalidades principales:

- **Guardar** un nuevo producto con validación en cliente y servidor
- **Editar** un producto existente cargando sus datos en el formulario
- **Buscar** productos en tiempo real por código o nombre
- **Listar** todos los productos en una tabla dinámica con Bootstrap
- **Alertas** amigables con SweetAlert2 para éxito y errores

---

## Tecnologías utilizadas

| Capa | Tecnología |
|------|-----------|
| Frontend | HTML5, Bootstrap 5, JavaScript (ES2022) |
| Comunicación | Fetch API, FormData, JSON |
| Backend | PHP 8+ (OOP) |
| Base de datos | MySQL / MariaDB con PDO |
| Alertas | SweetAlert2 v11 |
| Servidor local | XAMPP / WampServer |

---

## Estructura del proyecto

```
crud_productos/
│
├── index.html              # Interfaz principal: formulario + tabla de productos
├── script.js               # Lógica del cliente: Fetch, validaciones, SweetAlert2
├── registrar.php           # Controlador: recibe POST y retorna JSON
├── productosdb.sql         # Script SQL para crear la BD y tabla
│
└── Modelo/
    ├── conexion.php        # Clase DB — conexión PDO (Singleton)
    └── Productos.php       # Clase Producto — CRUD, validaciones
```

---

## Requisitos previos

- **XAMPP** o **WampServer** instalado y en ejecución
- **PHP 8.0** o superior
- **MySQL 5.7** / **MariaDB 10.4** o superior
- Navegador moderno (Chrome, Firefox, Edge)
- Editor de código (VS Code recomendado)

---

## Instalación y configuración

### 1. Clonar / copiar el proyecto

Coloca la carpeta `crud_productos` dentro del directorio raíz de tu servidor:

- **XAMPP:** `C:\xampp\htdocs\crud_productos\`
- **WampServer:** `C:\wamp64\www\crud_productos\`

### 2. Importar la base de datos

Tienes dos opciones:

**Opción A — phpMyAdmin:**
1. Abre `http://localhost/phpmyadmin`
2. Clic en **Importar**
3. Selecciona el archivo `productosdb.sql`
4. Clic en **Continuar**

**Opción B — Terminal MySQL:**
```bash
mysql -u root -p < productosdb.sql
```

### 3. Configurar la conexión (si es necesario)

Edita `Modelo/conexion.php` y ajusta las credenciales:

```php
private static $host     = "localhost";
private static $dbname   = "productosdb";
private static $user     = "root";
private static $password = "";       // Cambia si tu MySQL tiene contraseña
```

### 4. Abrir la aplicación

```
http://localhost/crud_productos/
```

---

## Base de datos

### Esquema

```sql
CREATE DATABASE IF NOT EXISTS productosdb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE productosdb;

CREATE TABLE IF NOT EXISTS productos (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    codigo    VARCHAR(20)   NOT NULL,
    producto  VARCHAR(100)  NOT NULL,
    precio    DECIMAL(10,2) NOT NULL,
    cantidad  INT           NOT NULL
);
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | Identificador único |
| `codigo` | VARCHAR(20) | Código único del producto (ej. PROD-001) |
| `producto` | VARCHAR(100) | Nombre descriptivo del producto |
| `precio` | DECIMAL(10,2) | Precio con dos decimales |
| `cantidad` | INT | Unidades en inventario |

---

## Arquitectura y flujo de datos

```
┌─────────────────────────────────────────────────────────┐
│                     index.html                          │
│  Formulario Bootstrap ──► script.js (evento clic)       │
│                              │                          │
│              Validación en cliente (JS)                 │
│                              │                          │
│              FormData + fetch("registrar.php")          │
└──────────────────────────────┼──────────────────────────┘
                               │  POST (multipart/form-data)
                               ▼
┌─────────────────────────────────────────────────────────┐
│                    registrar.php                        │
│   switch($_POST['Accion'])                              │
│   ├── "Guardar"   → new Producto() → guardar()         │
│   ├── "Modificar" → new Producto() → editar()          │
│   ├── "Buscar"    → Producto::buscar($termino)         │
│   ├── "Listar"    → Producto::listar()                 │
│   └── "BuscarId"  → Producto::obtenerPorId($id)        │
│                              │                          │
│   echo json_encode($response)  ◄── JSON puro           │
└──────────────────────────────┼──────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────┐
│                    Modelo/                              │
│  Productos.php                                          │
│  ├── validar()      ← valida campos antes de BD        │
│  ├── guardar()      ← INSERT con insertSeguro()        │
│  ├── editar()       ← UPDATE con updateSeguro()        │
│  ├── buscar()       ← SELECT con LIKE                  │
│  └── listar()       ← SELECT todos                     │
│                                                         │
│  conexion.php (Clase DB - Singleton)                    │
│  ├── insertSeguro() ← PDO prepare + execute            │
│  ├── updateSeguro() ← PDO prepare + execute            │
│  └── query()        ← PDO prepare + fetchAll()         │
└─────────────────────────────────────────────────────────┘
```

---

## Descripción de archivos

### `Modelo/conexion.php` — Clase DB

Implementa el patrón **Singleton** para gestionar una única conexión PDO a MySQL.

```php
DB::obtenerInstancia()   // Retorna la instancia única
$db->insertSeguro($sql, $params)  // INSERT preparado
$db->updateSeguro($sql, $params)  // UPDATE preparado
$db->query($sql, $params)         // SELECT preparado
```

Características:
- Conexión segura con **PDO** (previene inyección SQL)
- Modo de errores: `PDO::ERRMODE_EXCEPTION`
- Retorna JSON de error si la conexión falla

---

### `Modelo/Productos.php` — Clase Producto

Encapsula toda la lógica de negocio relacionada a productos.

**Propiedades:** `id`, `codigo`, `producto`, `precio`, `cantidad`

**Métodos de instancia:**

| Método | Descripción |
|--------|-------------|
| `validar(bool $esNuevo)` | Valida campos; `$esNuevo=true` exige cantidad ≥ 1 |
| `guardar()` | Inserta un nuevo producto; verifica código duplicado |
| `editar()` | Actualiza un producto existente por ID |

**Métodos estáticos:**

| Método | Descripción |
|--------|-------------|
| `Producto::listar()` | Devuelve todos los productos |
| `Producto::buscar($termino)` | Búsqueda LIKE por código o nombre |
| `Producto::obtenerPorId($id)` | Retorna un producto por su ID |

---

### `registrar.php` — Controlador

Punto de entrada del backend. Recibe `$_POST` y ejecuta el `switch` de acciones.

Reglas importantes:
- `header("Content-Type: application/json")` siempre al inicio
- Solo `echo json_encode(...)` como salida — sin `var_dump`, `print`, ni espacios extra
- Responde siempre con la estructura: `success`, `message`, `accion`, `errors`

---

### `index.html` — Vista principal

Formulario construido con **Bootstrap 5** que incluye:

- Campos: Código, Producto, Precio, Cantidad
- Botón **Registrar** (modo Guardar) / **Actualizar** (modo Modificar)
- Botón **Cancelar** para salir del modo edición
- Barra de búsqueda en tiempo real
- Tabla dinámica con renderizado JavaScript
- Indicador de stock (verde / rojo)

---

### `script.js` — Lógica del cliente

Organizado en funciones independientes:

| Función | Descripción |
|---------|-------------|
| `accionFormulario()` | Valida y envía el formulario (Guardar o Modificar) |
| `ListarProductos()` | Carga todos los productos en la tabla |
| `buscarProductos(termino)` | Búsqueda en tiempo real mientras se escribe |
| `editarProducto(id)` | Carga un producto en el formulario para editar |
| `cancelarEdicion()` | Limpia el formulario y vuelve al modo Guardar |
| `renderizarTabla(productos)` | Genera el HTML de las filas de la tabla |
| `validarCamposCliente(...)` | Validaciones antes de llamar a fetch |
| `mostrarErrores(errores, accion)` | Alerta SweetAlert2 con lista de errores |

El `switch` en JavaScript determina la acción según `modoActual`:

```javascript
switch (modoActual) {
    case "Guardar":   accion = "Guardar";   break;
    case "Modificar": accion = "Modificar"; break;
    default:          accion = "Guardar";
}
```

---

## API — Acciones disponibles

Todas las peticiones se realizan por `POST` a `registrar.php` con `FormData`.

### Guardar

```
POST registrar.php
Accion=Guardar&codigo=PROD-001&producto=Laptop&precio=850&cantidad=5
```

Respuesta exitosa:
```json
{
  "success": true,
  "message": "Producto guardado correctamente.",
  "accion": "Guardar",
  "id": 1,
  "errors": []
}
```

### Modificar

```
POST registrar.php
Accion=Modificar&id=1&codigo=PROD-001&producto=Laptop HP&precio=900&cantidad=3
```

### Buscar

```
POST registrar.php
Accion=Buscar&termino=laptop
```

Respuesta:
```json
{
  "success": true,
  "message": "1 producto(s) encontrado(s).",
  "accion": "Buscar",
  "productos": [ { "id": 1, "codigo": "PROD-001", ... } ],
  "errors": []
}
```

### Listar

```
POST registrar.php
Accion=Listar
```

### BuscarId (para editar)

```
POST registrar.php
Accion=BuscarId&id=1
```

### Respuesta de error

```json
{
  "success": false,
  "message": "No se pudo guardar el producto.",
  "accion": "Guardar",
  "errors": [
    "El código es obligatorio.",
    "El precio debe ser mayor a 0."
  ]
}
```

---

## Reglas de negocio

### Cantidad mínima según operación

| Operación | Mínimo | Razón |
|-----------|--------|-------|
| **Guardar** | 1 | No tiene sentido registrar un producto sin stock inicial |
| **Modificar** | 0 | El producto puede estar agotado; aún así se pueden editar otros campos |

Esto se refleja tanto en el atributo HTML (`min="1"` / `min="0"`) como en la validación PHP (`validar(true)` / `validar(false)`).

### Código único

No se permiten dos productos con el mismo `codigo`. El sistema verifica antes de insertar o actualizar y devuelve un error descriptivo si ya existe.

### Precio

El precio debe ser siempre mayor a `0.00`. No se aceptan precios negativos ni cero.
