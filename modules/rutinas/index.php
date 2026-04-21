<?php
// modules/rutinas/index.php — Rutinas Universales (Hombres y Mujeres)
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']);

$gimnasio_id = get_gimnasio_id();

// ── Auto-crear rutinas universales si no existen para este gimnasio ──
$rutinasBase = [
    ['nombre' => 'Rutina Universal — Hombres', 'objetivo' => 'fuerza',    'genero' => 'hombres'],
    ['nombre' => 'Rutina Universal — Mujeres', 'objetivo' => 'tonificacion', 'genero' => 'mujeres'],
];

$ejerciciosBase = [
    'hombres' => [
        ['nombre' => 'Sentadilla con barra',     'series' => 4, 'repeticiones' => '10-12', 'descanso_segundos' => 90,  'notas' => 'Mantén la espalda recta y rodillas alineadas.', 'orden' => 1],
        ['nombre' => 'Press de banca',           'series' => 4, 'repeticiones' => '10-12', 'descanso_segundos' => 90,  'notas' => 'Agarre medio, baja la barra hasta el pecho.', 'orden' => 2],
        ['nombre' => 'Peso muerto convencional', 'series' => 3, 'repeticiones' => '8',     'descanso_segundos' => 120, 'notas' => 'Caderas hacia atrás, espalda neutra.', 'orden' => 3],
        ['nombre' => 'Jalón al pecho',           'series' => 3, 'repeticiones' => '12',    'descanso_segundos' => 60,  'notas' => 'Codos hacia abajo en el movimiento.', 'orden' => 4],
        ['nombre' => 'Press militar',            'series' => 3, 'repeticiones' => '10',    'descanso_segundos' => 75,  'notas' => 'Core apretado, sin arquear la espalda.', 'orden' => 5],
        ['nombre' => 'Curl de bíceps',           'series' => 3, 'repeticiones' => '12-15', 'descanso_segundos' => 60,  'notas' => 'Codos fijos a los costados.', 'orden' => 6],
        ['nombre' => 'Extensiones de tríceps',   'series' => 3, 'repeticiones' => '12-15', 'descanso_segundos' => 60,  'notas' => 'Polea alta o mancuerna.', 'orden' => 7],
        ['nombre' => 'Plancha abdominal',        'series' => 3, 'repeticiones' => '45 seg','descanso_segundos' => 45,  'notas' => 'Cuerpo alineado de cabeza a talones.', 'orden' => 8],
    ],
    'mujeres' => [
        ['nombre' => 'Sentadilla con mancuernas',  'series' => 4, 'repeticiones' => '15',    'descanso_segundos' => 60,  'notas' => 'Pies a la altura de los hombros.', 'orden' => 1],
        ['nombre' => 'Hip Thrust',                 'series' => 4, 'repeticiones' => '15-20', 'descanso_segundos' => 60,  'notas' => 'Aprieta el glúteo en la parte alta.', 'orden' => 2],
        ['nombre' => 'Peso muerto rumano',         'series' => 3, 'repeticiones' => '12',    'descanso_segundos' => 75,  'notas' => 'Enfocado en isquiotibiales y glúteos.', 'orden' => 3],
        ['nombre' => 'Prensa de piernas',          'series' => 3, 'repeticiones' => '15',    'descanso_segundos' => 60,  'notas' => 'Pies en posición media, no bloquees las rodillas.', 'orden' => 4],
        ['nombre' => 'Remo con mancuerna',         'series' => 3, 'repeticiones' => '12',    'descanso_segundos' => 60,  'notas' => 'Espalda recta, codo cerca del cuerpo.', 'orden' => 5],
        ['nombre' => 'Press de hombros mancuerna', 'series' => 3, 'repeticiones' => '12',    'descanso_segundos' => 60,  'notas' => 'Peso moderado para control.', 'orden' => 6],
        ['nombre' => 'Abducción lateral con banda','series' => 3, 'repeticiones' => '20',    'descanso_segundos' => 45,  'notas' => 'Banda a la altura de tobillos.', 'orden' => 7],
        ['nombre' => 'Plancha lateral',            'series' => 3, 'repeticiones' => '30 seg','descanso_segundos' => 45,  'notas' => 'Cada lado. Cadera elevada.', 'orden' => 8],
    ],
];

foreach ($rutinasBase as $rb) {
    $stmtCheck = $pdo->prepare("SELECT id FROM rutinas WHERE gimnasio_id = ? AND nombre = ? LIMIT 1");
    $stmtCheck->execute([$gimnasio_id, $rb['nombre']]);
    $existente = $stmtCheck->fetch();

    if (!$existente) {
        // Crear la rutina
        $stmtIns = $pdo->prepare("INSERT INTO rutinas (gimnasio_id, nombre, descripcion, objetivo, activa) VALUES (?, ?, ?, ?, 1)");
        $desc = ($rb['genero'] === 'hombres')
            ? 'Rutina de entrenamiento completa orientada a hombres. Enfoque en fuerza funcional y desarrollo muscular general.'
            : 'Rutina de entrenamiento completa orientada a mujeres. Enfoque en tonificación, glúteos y resistencia.';
        $stmtIns->execute([$gimnasio_id, $rb['nombre'], $desc, $rb['objetivo']]);
        $rutina_id = $pdo->lastInsertId();

        // Insertar ejercicios base
        $stmtEj = $pdo->prepare("INSERT INTO ejercicios (rutina_id, nombre, series, repeticiones, descanso_segundos, notas, orden) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($ejerciciosBase[$rb['genero']] as $ej) {
            $stmtEj->execute([$rutina_id, $ej['nombre'], $ej['series'], $ej['repeticiones'], $ej['descanso_segundos'], $ej['notas'], $ej['orden']]);
        }
    }
}

// ── Cargar las dos rutinas universales del gym ──
$stmtR = $pdo->prepare("
    SELECT r.id, r.nombre, r.descripcion, r.objetivo, r.activa,
           (SELECT COUNT(*) FROM ejercicios e WHERE e.rutina_id = r.id) as total_ejercicios
    FROM rutinas r
    WHERE r.gimnasio_id = ?
    ORDER BY r.id ASC
");
$stmtR->execute([$gimnasio_id]);
$rutinas = $stmtR->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';

$objetivos = [
    'fuerza'       => ['label' => 'Fuerza',         'color' => '#F97316', 'bg' => 'rgba(249,115,22,0.1)'],
    'cardio'       => ['label' => 'Cardio',          'color' => '#EF4444', 'bg' => 'rgba(239,68,68,0.1)'],
    'perdida_peso' => ['label' => 'Pérdida de peso', 'color' => '#EAB308', 'bg' => 'rgba(234,179,8,0.1)'],
    'tonificacion' => ['label' => 'Tonificación',    'color' => '#22C55E', 'bg' => 'rgba(34,197,94,0.1)'],
    'otro'         => ['label' => 'General',          'color' => '#A3A3A3', 'bg' => 'rgba(163,163,163,0.08)'],
];

$iconos_genero = [
    'Hombres' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    'Mujeres' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
];
?>

<style>
    .rutina-card {
        background: var(--color-surface);
        border: 1px solid #262626;
        border-radius: 1rem;
        overflow: hidden;
        transition: all 0.25s ease;
    }
    .rutina-card:hover {
        border-color: var(--color-primary);
        box-shadow: 0 8px 30px rgba(249,115,22,0.1);
        transform: translateY(-2px);
    }
    .rutina-header {
        padding: 1.5rem;
        border-bottom: 1px solid #262626;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    .rutina-icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .rutina-body { padding: 1.25rem 1.5rem; }
    .rutina-stat {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: var(--color-text-muted);
        margin-bottom: 0.4rem;
    }
    .rutina-stat strong { color: white; }
    .rutina-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #262626;
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: wrap;
    }
</style>

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 class="text-h2" style="margin-bottom:0.25rem;">Rutinas de Entrenamiento</h1>
            <p class="text-muted">Plantillas universales editables. Se asignan directamente desde el perfil del socio.</p>
        </div>
    </div>

    <?php mostrar_flash(); ?>

    <?php if (empty($rutinas)): ?>
    <div style="text-align:center;padding:4rem 1rem;color:var(--color-text-muted);">
        <p>No hay rutinas disponibles.</p>
    </div>
    <?php else: ?>

    <div style="display:grid;gap:1.25rem;grid-template-columns:1fr;" id="rutinasGrid">
        <?php
        // Responsivo: 2 cols en pantallas md+
        if (count($rutinas) >= 2) echo '<style>@media(min-width:768px){#rutinasGrid{grid-template-columns:1fr 1fr;}}</style>';
        foreach ($rutinas as $r):
            $obj = $objetivos[$r['objetivo']] ?? $objetivos['otro'];
            // Detectar género por nombre
            $genero = str_contains($r['nombre'], 'Mujeres') ? 'Mujeres' : 'Hombres';
            $icono_genero = ($genero === 'Mujeres')
                ? 'M12 4c-1.93 0-3.5 1.57-3.5 3.5S10.07 11 12 11s3.5-1.57 3.5-3.5S13.93 4 12 4zm0 9c-3.87 0-7 1.57-7 3.5V18h14v-1.5c0-1.93-3.13-3.5-7-3.5z'
                : 'M12 4c-1.93 0-3.5 1.57-3.5 3.5S10.07 11 12 11s3.5-1.57 3.5-3.5S13.93 4 12 4zm0 7c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z';
        ?>
        <div class="rutina-card">
            <div class="rutina-header">
                <div class="rutina-icon" style="background:<?= $obj['bg'] ?>;">
                    <svg style="width:1.75rem;height:1.75rem;color:<?= $obj['color'] ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:0.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($r['nombre']) ?></h3>
                    <span style="display:inline-block;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;background:<?= $obj['bg'] ?>;color:<?= $obj['color'] ?>;"><?= $obj['label'] ?></span>
                </div>
            </div>

            <div class="rutina-body">
                <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:1rem;line-height:1.5;"><?= h($r['descripcion'] ?? '—') ?></p>
                <div class="rutina-stat">
                    <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <strong><?= (int)$r['total_ejercicios'] ?></strong> ejercicios registrados
                </div>
                <div class="rutina-stat">
                    <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                    Para: <strong><?= $genero ?></strong>
                </div>
            </div>

            <div class="rutina-footer">
                <a href="editar.php?id=<?= $r['id'] ?>" class="btn btn-primary" style="flex:1;min-width:0;font-size:0.875rem;min-height:42px;padding:0.6rem;">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Editar Ejercicios
                </a>
                <a href="imprimir.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-secondary" style="font-size:0.875rem;min-height:42px;padding:0.6rem 0.875rem;" title="Ver / Imprimir PDF">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    PDF
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:1.75rem;padding:1.25rem 1.5rem;background:rgba(249,115,22,0.05);border:1px solid rgba(249,115,22,0.15);border-radius:0.75rem;display:flex;align-items:flex-start;gap:0.75rem;">
        <svg style="width:1.25rem;height:1.25rem;color:var(--color-primary);flex-shrink:0;margin-top:0.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p style="font-weight:700;color:var(--color-primary);margin-bottom:0.2rem;font-size:0.9rem;">¿Cómo asignar una rutina a un socio?</p>
            <p style="font-size:0.8rem;color:var(--color-text-muted);">Ve al perfil del socio desde <a href="<?= BASE_URL ?>modules/socios/index.php" style="color:var(--color-primary);">Socios</a> y busca la sección "Rutina Asignada". Desde ahí podrás asignar cualquiera de estas plantillas y enviarla por WhatsApp.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
