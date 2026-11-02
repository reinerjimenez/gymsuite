<?php
// modules/productos/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
verificar_sesion();
verificar_rol(['admin']);
$gimnasio_id = (int)get_gimnasio_id();
if (!isset($_SESSION['productos_csrf'])) $_SESSION['productos_csrf'] = bin2hex(random_bytes(32));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['productos_csrf'], $_POST['csrf'])) {
        $error = 'La sesión del formulario venció. Recarga la página.';
    } else {
        $accion = $_POST['accion'] ?? '';
        try {
            if ($accion === 'categoria') {
                $id = (int)($_POST['id'] ?? 0);
                $nombre = trim($_POST['nombre'] ?? '');
                $activo = isset($_POST['activo']) ? 1 : 0;
                if ($nombre === '' || mb_strlen($nombre) > 100) throw new RuntimeException('Indica un nombre de categoría válido.');
                if ($id) {
                    $stmt = $pdo->prepare('UPDATE categorias_producto SET nombre=?, activo=? WHERE id=? AND gimnasio_id=?');
                    $stmt->execute([$nombre, $activo, $id, $gimnasio_id]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO categorias_producto (gimnasio_id,nombre) VALUES (?,?)');
                    $stmt->execute([$gimnasio_id, $nombre]);
                }
                mensaje_flash('success', 'Categoría guardada.');
            } elseif ($accion === 'producto') {
                $id = (int)($_POST['id'] ?? 0);
                $nombre = trim($_POST['nombre'] ?? '');
                $categoria_id = (int)($_POST['categoria_id'] ?? 0);
                $precio = trim($_POST['precio_venta'] ?? '');
                $stock_minimo = filter_var($_POST['stock_minimo'] ?? null, FILTER_VALIDATE_INT);
                $stock_inicial = filter_var($_POST['stock_inicial'] ?? 0, FILTER_VALIDATE_INT);
                $activo = isset($_POST['activo']) ? 1 : 0;
                if ($nombre === '' || mb_strlen($nombre) > 150 || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $precio) || $stock_minimo === false || $stock_minimo < 0 || $stock_inicial === false || $stock_inicial < 0) {
                    throw new RuntimeException('Revisa nombre, precio y stock.');
                }
                $stmt = $pdo->prepare('SELECT id FROM categorias_producto WHERE id=? AND gimnasio_id=?');
                $stmt->execute([$categoria_id, $gimnasio_id]);
                if (!$stmt->fetchColumn()) throw new RuntimeException('La categoría no pertenece a este gimnasio.');
                $imagen = null;
                if (!empty($_FILES['imagen']['name'])) {
                    $archivo = $_FILES['imagen'];
                    if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 2097152) throw new RuntimeException('La imagen debe pesar menos de 2 MB.');
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
                    $extensiones = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
                    if (!isset($extensiones[$mime]) || !getimagesize($archivo['tmp_name'])) throw new RuntimeException('Usa una imagen JPG, PNG o WebP.');
                    $carpeta = __DIR__ . '/../../uploads/productos';
                    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) throw new RuntimeException('No se pudo preparar la carpeta de imágenes.');
                    $imagen = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
                    if (!move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $imagen)) throw new RuntimeException('No se pudo guardar la imagen.');
                }
                if ($id) {
                    $sql = 'UPDATE productos SET categoria_id=?, nombre=?, precio_venta=?, stock_minimo=?, activo=?';
                    $params = [$categoria_id, $nombre, $precio, $stock_minimo, $activo];
                    if ($imagen !== null) { $sql .= ', imagen=?'; $params[] = $imagen; }
                    $sql .= ' WHERE id=? AND gimnasio_id=?';
                    $params[] = $id; $params[] = $gimnasio_id;
                    $pdo->prepare($sql)->execute($params);
                } else {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('INSERT INTO productos (gimnasio_id,categoria_id,nombre,precio_venta,stock_actual,stock_minimo,imagen,activo) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([$gimnasio_id,$categoria_id,$nombre,$precio,$stock_inicial,$stock_minimo,$imagen,$activo]);
                    $producto_id = (int)$pdo->lastInsertId();
                    if ($stock_inicial > 0) {
                        $pdo->prepare("INSERT INTO movimientos_stock (gimnasio_id,producto_id,tipo,cantidad,stock_anterior,stock_nuevo,registrado_por,fecha_hora,nota) VALUES (?,?,'ajuste',?,0,?,?,NOW(),'Stock inicial')")
                            ->execute([$gimnasio_id,$producto_id,$stock_inicial,$stock_inicial,$_SESSION['usuario_id']]);
                    }
                    $pdo->commit();
                }
                mensaje_flash('success', 'Producto guardado.');
            } elseif ($accion === 'ajuste') {
                $id = (int)($_POST['id'] ?? 0);
                $nuevo = filter_var($_POST['stock_nuevo'] ?? null, FILTER_VALIDATE_INT);
                $nota = trim($_POST['nota'] ?? '');
                if ($nuevo === false || $nuevo < 0 || $nota === '' || mb_strlen($nota) > 255) throw new RuntimeException('Indica un stock válido y el motivo del ajuste.');
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT stock_actual FROM productos WHERE id=? AND gimnasio_id=? FOR UPDATE');
                $stmt->execute([$id,$gimnasio_id]);
                $anterior = $stmt->fetchColumn();
                if ($anterior === false) throw new RuntimeException('Producto no encontrado.');
                $anterior = (int)$anterior;
                if ($nuevo === $anterior) throw new RuntimeException('El stock nuevo es igual al actual.');
                $pdo->prepare('UPDATE productos SET stock_actual=? WHERE id=? AND gimnasio_id=?')->execute([$nuevo,$id,$gimnasio_id]);
                $pdo->prepare("INSERT INTO movimientos_stock (gimnasio_id,producto_id,tipo,cantidad,stock_anterior,stock_nuevo,registrado_por,fecha_hora,nota) VALUES (?,?,'ajuste',?,?,?,?,NOW(),?)")
                    ->execute([$gimnasio_id,$id,$nuevo-$anterior,$anterior,$nuevo,$_SESSION['usuario_id'],$nota]);
                $pdo->commit();
                mensaje_flash('success', 'Stock actualizado.');
            } else {
                throw new RuntimeException('Acción no válida.');
            }
            header('Location: index.php'); exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e instanceof PDOException ? 'No se pudo guardar. Comprueba que el nombre no esté repetido.' : $e->getMessage();
        }
    }
}

$categorias_stmt = $pdo->prepare('SELECT * FROM categorias_producto WHERE gimnasio_id=? ORDER BY nombre');
$categorias_stmt->execute([$gimnasio_id]);
$categorias = $categorias_stmt->fetchAll();
$productos_stmt = $pdo->prepare('SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias_producto c ON c.id=p.categoria_id AND c.gimnasio_id=p.gimnasio_id WHERE p.gimnasio_id=? ORDER BY p.activo DESC,p.nombre');
$productos_stmt->execute([$gimnasio_id]);
$productos = $productos_stmt->fetchAll();
$mov_stmt = $pdo->prepare('SELECT m.*,p.nombre AS producto,u.nombre AS usuario FROM movimientos_stock m JOIN productos p ON p.id=m.producto_id AND p.gimnasio_id=m.gimnasio_id JOIN usuarios u ON u.id=m.registrado_por WHERE m.gimnasio_id=? ORDER BY m.id DESC LIMIT 50');
$mov_stmt->execute([$gimnasio_id]);
$movimientos = $mov_stmt->fetchAll();
$editar = null;
foreach ($productos as $producto) if ((int)$producto['id'] === (int)($_GET['editar'] ?? 0)) $editar = $producto;
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div>
  <div class="flex justify-between items-center mb-6 flex-wrap gap-4"><div><h1 class="text-h2">Productos</h1><p class="text-muted">Catálogo, categorías y stock del gimnasio.</p></div><a class="btn btn-primary" style="min-height:44px" href="../punto_venta/index.php">Abrir Punto de Venta</a></div>
  <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= h($error) ?></div><?php endif; ?>
  <div class="dashboard-grid cols-2 mb-6">
    <div class="card"><h2 class="text-h3 mb-4">Categorías</h2>
      <form method="post" class="flex gap-4 flex-wrap items-end mb-4"><input type="hidden" name="csrf" value="<?= h($_SESSION['productos_csrf']) ?>"><input type="hidden" name="accion" value="categoria"><div class="form-group" style="flex:1;min-width:180px"><label class="form-label" for="categoria_nombre">Nueva categoría</label><input class="form-control" id="categoria_nombre" name="nombre" maxlength="100" required></div><button class="btn btn-primary" style="min-height:44px">Agregar</button></form>
      <?php foreach ($categorias as $categoria): ?><form method="post" class="flex gap-4 items-center flex-wrap mb-2"><input type="hidden" name="csrf" value="<?= h($_SESSION['productos_csrf']) ?>"><input type="hidden" name="accion" value="categoria"><input type="hidden" name="id" value="<?= (int)$categoria['id'] ?>"><input class="form-control" style="flex:1;min-width:140px" aria-label="Nombre de categoría" name="nombre" value="<?= h($categoria['nombre']) ?>" required><label><input type="checkbox" name="activo" <?= $categoria['activo'] ? 'checked' : '' ?>> Activa</label><button class="btn btn-secondary" style="min-height:44px">Guardar</button></form><?php endforeach; ?>
    </div>
    <div class="card"><h2 class="text-h3 mb-4"><?= $editar ? 'Editar producto' : 'Nuevo producto' ?></h2>
      <form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= h($_SESSION['productos_csrf']) ?>"><input type="hidden" name="accion" value="producto"><input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
        <div class="form-group"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" maxlength="150" value="<?= h($editar['nombre'] ?? '') ?>" required></div>
        <div class="form-group"><label class="form-label" for="categoria_id">Categoría</label><select class="form-control" id="categoria_id" name="categoria_id" required><option value="">Seleccionar</option><?php foreach ($categorias as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($editar['categoria_id'] ?? 0)===(int)$c['id'] ? 'selected' : '' ?>><?= h($c['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="flex gap-4 flex-wrap"><div class="form-group" style="flex:1"><label class="form-label" for="precio_venta">Precio S/</label><input class="form-control" id="precio_venta" name="precio_venta" type="number" min="0" max="99999999.99" step="0.01" value="<?= h($editar['precio_venta'] ?? '') ?>" required></div><div class="form-group" style="flex:1"><label class="form-label" for="stock_minimo">Stock mínimo</label><input class="form-control" id="stock_minimo" name="stock_minimo" type="number" min="0" value="<?= h($editar['stock_minimo'] ?? '0') ?>" required></div></div>
        <?php if (!$editar): ?><div class="form-group"><label class="form-label" for="stock_inicial">Stock inicial</label><input class="form-control" id="stock_inicial" name="stock_inicial" type="number" min="0" value="0" required></div><?php endif; ?>
        <div class="form-group"><label class="form-label" for="imagen">Imagen opcional (JPG, PNG o WebP; máximo 2 MB)</label><input class="form-control" id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp"></div>
        <label class="form-label"><input type="checkbox" name="activo" <?= !$editar || $editar['activo'] ? 'checked' : '' ?>> Activo</label><div class="flex gap-4 mt-4"><button class="btn btn-primary" style="min-height:44px">Guardar producto</button><?php if ($editar): ?><a href="index.php" class="btn btn-secondary" style="min-height:44px">Cancelar</a><?php endif; ?></div>
      </form>
    </div>
  </div>
  <div class="card mb-6"><h2 class="text-h3 mb-4">Catálogo</h2><div class="table-responsive"><table class="table"><thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
  <?php foreach ($productos as $p): ?><tr><td><div class="flex items-center gap-4"><?php if ($p['imagen']): ?><img src="<?= BASE_URL ?>uploads/productos/<?= h($p['imagen']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:8px"><?php endif; ?><strong><?= h($p['nombre']) ?></strong></div></td><td><?= h($p['categoria']) ?></td><td><?= formatear_moneda($p['precio_venta']) ?></td><td><span class="badge" style="<?= (int)$p['stock_actual'] <= (int)$p['stock_minimo'] ? 'background:rgba(239,68,68,.15);color:#f87171' : '' ?>"><?= (int)$p['stock_actual'] ?><?= (int)$p['stock_actual'] <= (int)$p['stock_minimo'] ? ' · Bajo' : '' ?></span></td><td><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></td><td><a class="btn btn-secondary" style="min-height:44px" href="?editar=<?= (int)$p['id'] ?>">Editar</a><details><summary style="cursor:pointer;padding:12px">Ajustar stock</summary><form method="post" class="flex gap-4 flex-wrap items-end"><input type="hidden" name="csrf" value="<?= h($_SESSION['productos_csrf']) ?>"><input type="hidden" name="accion" value="ajuste"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><label>Nuevo stock <input class="form-control" name="stock_nuevo" type="number" min="0" value="<?= (int)$p['stock_actual'] ?>" required></label><label>Motivo <input class="form-control" name="nota" maxlength="255" required></label><button class="btn btn-primary" style="min-height:44px">Guardar</button></form></details></td></tr><?php endforeach; ?>
  <?php if (!$productos): ?><tr><td colspan="6">Aún no hay productos.</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="card"><h2 class="text-h3 mb-4">Últimos movimientos de stock</h2><div class="table-responsive"><table class="table"><thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cambio</th><th>Antes → Después</th><th>Responsable</th><th>Motivo</th></tr></thead><tbody><?php foreach ($movimientos as $m): ?><tr><td><?= h($m['fecha_hora']) ?></td><td><?= h($m['producto']) ?></td><td><?= h($m['tipo']) ?></td><td><?= (int)$m['cantidad'] ?></td><td><?= (int)$m['stock_anterior'] ?> → <?= (int)$m['stock_nuevo'] ?></td><td><?= h($m['usuario']) ?></td><td><?= h($m['nota'] ?? '') ?></td></tr><?php endforeach; ?><?php if (!$movimientos): ?><tr><td colspan="7">Sin movimientos.</td></tr><?php endif; ?></tbody></table></div></div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
