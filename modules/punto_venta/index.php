<?php
// modules/punto_venta/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
verificar_sesion();
verificar_rol(['admin', 'recepcionista']);
$gimnasio_id = (int)get_gimnasio_id();
if (!isset($_SESSION['venta_csrf'])) $_SESSION['venta_csrf'] = bin2hex(random_bytes(32));
$stmt = $pdo->prepare('SELECT p.id,p.nombre,p.precio_venta,p.stock_actual,p.stock_minimo,p.imagen,p.categoria_id FROM productos p JOIN categorias_producto c ON c.id=p.categoria_id AND c.gimnasio_id=p.gimnasio_id WHERE p.gimnasio_id=? AND p.activo=1 AND c.activo=1 ORDER BY c.nombre,p.nombre');
$stmt->execute([$gimnasio_id]);
$productos = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT id,nombre FROM categorias_producto WHERE gimnasio_id=? AND activo=1 ORDER BY nombre');
$stmt->execute([$gimnasio_id]);
$categorias = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT id,nombre,apellido,dni FROM socios WHERE gimnasio_id=? AND activo=1 ORDER BY nombre,apellido');
$stmt->execute([$gimnasio_id]);
$socios = $stmt->fetchAll();
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<style>
/* Reutiliza los componentes globales y amplía su área táctil en caja. */
.pos button,.pos select,.pos input {min-height:48px}
.pos-layout {display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:1rem;align-items:start}
.pos-grid {display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.75rem}
.pos-product {width:100%;min-height:150px;text-align:left;display:flex;flex-direction:column;align-items:stretch;white-space:normal;padding:0;overflow:hidden}
.pos-product[hidden] {display:none}
.pos-product img {width:100%;height:85px;object-fit:cover}
.pos-product-body {padding:.75rem;display:flex;flex-direction:column;gap:.25rem}
.pos-cart {position:sticky;top:1rem}
.pos-row {display:grid;grid-template-columns:1fr auto;gap:.5rem;border-bottom:1px solid var(--color-border);padding:.65rem 0}
.pos-qty {display:flex;align-items:center;gap:.35rem}
.pos-qty button {min-width:48px}
@media(max-width:900px){.pos-layout{grid-template-columns:1fr}.pos-cart{position:static}.pos-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}}
</style>
<div class="pos">
  <div class="flex justify-between items-center mb-6 flex-wrap gap-4"><div><h1 class="text-h2">Punto de Venta</h1><p class="text-muted">Selecciona productos y confirma el cobro.</p></div><?php if ($_SESSION['rol']==='admin'): ?><a class="btn btn-secondary" style="min-height:48px" href="../productos/index.php">Administrar productos</a><?php endif; ?></div>
  <div class="pos-layout">
    <section class="card" aria-label="Catálogo"><div class="flex gap-4 flex-wrap mb-4"><label style="flex:2;min-width:180px">Buscar producto<input id="buscar" class="form-control" type="search" placeholder="Nombre del producto"></label><label style="flex:1;min-width:150px">Categoría<select id="categoria" class="form-control"><option value="">Todas</option><?php foreach ($categorias as $c): ?><option value="<?= (int)$c['id'] ?>"><?= h($c['nombre']) ?></option><?php endforeach; ?></select></label></div>
      <div class="pos-grid" id="productos"><?php foreach ($productos as $p): ?><button type="button" class="card pos-product" data-id="<?= (int)$p['id'] ?>" data-nombre="<?= h(mb_strtolower($p['nombre'])) ?>" data-categoria="<?= (int)$p['categoria_id'] ?>" <?= (int)$p['stock_actual'] === 0 ? 'disabled' : '' ?>><?php if ($p['imagen']): ?><img src="<?= BASE_URL ?>uploads/productos/<?= h($p['imagen']) ?>" alt=""><?php endif; ?><span class="pos-product-body"><strong><?= h($p['nombre']) ?></strong><span><?= formatear_moneda($p['precio_venta']) ?></span><span class="badge" style="<?= (int)$p['stock_actual'] <= (int)$p['stock_minimo'] ? 'background:rgba(239,68,68,.15);color:#f87171' : '' ?>">Stock: <?= (int)$p['stock_actual'] ?><?= (int)$p['stock_actual'] <= (int)$p['stock_minimo'] ? ' · Bajo' : '' ?></span></span></button><?php endforeach; ?></div>
      <?php if (!$productos): ?><p class="text-muted">No hay productos activos disponibles.</p><?php endif; ?>
    </section>
    <section class="card pos-cart" aria-label="Venta actual"><h2 class="text-h3 mb-4">Venta actual</h2><div id="carrito" aria-live="polite"><p class="text-muted">Selecciona un producto.</p></div><div class="flex justify-between items-center mt-4 mb-4"><strong>Total</strong><strong class="text-h3" id="total">S/ 0.00</strong></div>
      <label class="form-label" for="socio">Socio (opcional)</label><select id="socio" class="form-control mb-4"><option value="">Público general</option><?php foreach ($socios as $s): ?><option value="<?= (int)$s['id'] ?>"><?= h($s['nombre'].' '.$s['apellido'].' · '.$s['dni']) ?></option><?php endforeach; ?></select>
      <label class="form-label" for="metodo">Método de pago</label><select id="metodo" class="form-control mb-4"><option value="efectivo">Efectivo</option><option value="yape">Yape</option><option value="plin">Plin</option><option value="transferencia">Transferencia</option></select>
      <button id="confirmar" type="button" class="btn btn-primary" style="width:100%;min-height:54px" disabled>Confirmar venta</button><p id="resultado" role="status" class="mt-4"></p>
    </section>
  </div>
</div>
<script>
const catalogo = <?= json_encode($productos, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const csrf = <?= json_encode($_SESSION['venta_csrf']) ?>;
const porId = Object.fromEntries(catalogo.map(p => [Number(p.id), p]));
const carrito = new Map();
const moneda = centimos => 'S/ ' + (centimos / 100).toFixed(2);
const precio = p => Math.round(Number(p.precio_venta) * 100);
const escapeHtml = valor => String(valor).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function pintar() {
  const contenedor = document.getElementById('carrito');
  contenedor.innerHTML = carrito.size ? [...carrito].map(([id,cantidad]) => {
    const p=porId[id];
    return `<div class="pos-row"><div><strong>${escapeHtml(p.nombre)}</strong><div>${moneda(precio(p))} c/u</div></div><div><div class="pos-qty"><button type="button" class="btn btn-secondary" data-accion="restar" data-id="${id}" aria-label="Quitar uno de ${escapeHtml(p.nombre)}">−</button><strong>${cantidad}</strong><button type="button" class="btn btn-secondary" data-accion="sumar" data-id="${id}" aria-label="Agregar uno de ${escapeHtml(p.nombre)}">+</button></div><strong>${moneda(precio(p)*cantidad)}</strong></div></div>`;
  }).join('') : '<p class="text-muted">Selecciona un producto.</p>';
  const total=[...carrito].reduce((s,[id,c])=>s+precio(porId[id])*c,0);
  document.getElementById('total').textContent=moneda(total);
  document.getElementById('confirmar').disabled=!carrito.size;
}
document.getElementById('productos').addEventListener('click', e => {
  const boton=e.target.closest('[data-id]'); if (!boton || boton.disabled) return;
  const id=Number(boton.dataset.id), actual=carrito.get(id)||0;
  if (actual < Number(porId[id].stock_actual)) carrito.set(id,actual+1);
  pintar();
});
document.getElementById('carrito').addEventListener('click', e => {
  const boton=e.target.closest('[data-accion]'); if (!boton) return;
  const id=Number(boton.dataset.id), actual=carrito.get(id)||0;
  const nuevo=actual+(boton.dataset.accion==='sumar'?1:-1);
  if (nuevo<=0) carrito.delete(id); else if (nuevo<=Number(porId[id].stock_actual)) carrito.set(id,nuevo);
  pintar();
});
function filtrar() {
  const texto=document.getElementById('buscar').value.trim().toLocaleLowerCase();
  const categoria=document.getElementById('categoria').value;
  document.querySelectorAll('.pos-product').forEach(b => b.hidden=!(b.dataset.nombre.includes(texto) && (!categoria || b.dataset.categoria===categoria)));
}
document.getElementById('buscar').addEventListener('input',filtrar);
document.getElementById('categoria').addEventListener('change',filtrar);
document.getElementById('confirmar').addEventListener('click', async () => {
  const boton=document.getElementById('confirmar'), salida=document.getElementById('resultado');
  boton.disabled=true; salida.textContent='Registrando venta...';
  try {
    const respuesta=await fetch('confirmar.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,metodo:document.getElementById('metodo').value,socio_id:document.getElementById('socio').value||null,items:[...carrito].map(([id,cantidad])=>({id,cantidad}))})});
    const datos=await respuesta.json();
    salida.textContent=datos.message;
    if (datos.success) { carrito.clear(); pintar(); setTimeout(()=>location.reload(),1200); }
  } catch (e) { salida.textContent='No se pudo conectar. Comprueba si la venta quedó registrada antes de reintentar.'; }
  if (carrito.size) boton.disabled=false;
});
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
