PROMPT MAESTRO DEFINITIVO — GymSuite SaaS · R.DEV
Eres un desarrollador full-stack senior especializado en sistemas SaaS para pequeños negocios.
Tu tarea es construir desde cero un sistema de gestión para gimnasios y academias deportivas,
desarrollado en PHP puro + MySQL, compatible al 100% con InfinityFree (hosting gratuito).

Este sistema es un producto comercial desarrollado bajo la marca R.DEV por Reiner Jiménez,
Desarrollador Full-Stack de Tingo María, Perú. Debe estar listo para venderse a gimnasios
locales desde el primer deploy.

---

## NOMBRE DEL PRODUCTO
GymSuite · by R.DEV

---

## STACK TÉCNICO — RESTRICCIONES ESTRICTAS

- Backend: PHP puro (sin Laravel, sin Composer, sin namespaces complejos)
- Base de datos: MySQL (compatible con phpMyAdmin de InfinityFree)
- Frontend: HTML + CSS + JavaScript vanilla
- Sin: npm, node_modules, webpack, frameworks CSS (no Bootstrap, no Tailwind), frameworks JS
- Íconos: Heroicons via CDN — uno solo, consistente en todo el proyecto
- Fuentes: Google Fonts via CDN — elige fuentes profesionales y modernas, NO Inter, NO Roboto
- Sesiones PHP nativas para autenticación (sin JWT, sin cookies complejas)
- Sin cron jobs — las alertas de vencimiento se calculan en tiempo real al cargar las vistas
- QR: generación con librería JavaScript "qrcode.js" via CDN (sin dependencias PHP)
- Escaneo QR: API nativa del navegador getUserMedia() + librería "jsQR" via CDN

---

## IDENTIDAD VISUAL

- Paleta: Naranja energético (#F97316) como color primario de acción,
  Negro profundo (#0A0A0A) como fondo principal,
  Gris oscuro (#1A1A1A) para superficies de cards,
  Blanco (#FFFFFF) y Gris claro (#A3A3A3) para textos
- Estilo: Moderno, oscuro, deportivo — transmite energía y profesionalismo
- Sin emojis en ninguna parte del UI — usar íconos SVG de Heroicons
- Mobile-first obligatorio: el sistema se usará principalmente en móviles y tablets
- Área táctil mínima de 48px en todos los botones interactivos
- Logo: texto estilizado "GymSuite" con isotipo SVG minimalista deportivo
- Marca de autor: footer con "Desarrollado por Reiner Jiménez · R.DEV © 2026"
- Tipografía: elige una fuente de display para títulos y una fuente legible para cuerpo,
  ambas desde Google Fonts, que transmitan modernidad y energía

---

## ROLES DEL SISTEMA

### superadmin — Reiner Jiménez (propietario del SaaS)
- Accede a todos los gimnasios registrados en la plataforma
- Alta, baja y suspensión de gimnasios
- Vista global: total gyms activos, socios totales en la plataforma
- Gestión de su propio perfil

### admin — Dueño o gerente del gimnasio (cliente del SaaS)
- Gestión completa de su gimnasio (solo ve SUS datos, aislado de otros gyms)
- Acceso a todos los módulos excepto panel superadmin
- Configura el método de acceso de sus socios (DNI, clave o QR)

### recepcionista — Personal del gym
- Registra entradas de socios según el método configurado por el admin
- Ve estado de membresía del socio al identificarlo
- Registra pagos de renovación
- No puede modificar configuración del gym ni ver reportes financieros

### socio — Miembro del gimnasio
- Vista propia optimizada para móvil (sin instalación de app)
- Ve su membresía, historial de asistencia y rutina asignada
- Puede cambiar su clave de 4 dígitos desde su vista
- Puede mostrar su QR personal desde su vista

---

## SISTEMA DE ACCESO DE SOCIOS — NÚCLEO DEL PRODUCTO

Este es el diferenciador principal de GymSuite. Cada gimnasio configura UN solo método
de identificación para sus socios. El método se configura en el panel de Configuración
del gym y determina qué pantalla se muestra en recepción.

### Método 1 — Por DNI
FLUJO:
- El recepcionista abre la vista de accesos
- Escribe el DNI del socio en un campo de búsqueda grande y visible
- El sistema muestra instantáneamente (con búsqueda en tiempo real via fetch):
  foto del socio (si tiene), nombre completo, plan activo, días restantes,
  e indicador de estado con color (verde / amarillo / rojo)
- Si membresía vencida: mostrar bloqueo visual con mensaje "Membresía vencida —
  invitar a renovar" y botón de renovación rápida
- Si membresía activa: botón grande "Registrar Entrada" color naranja
- Al registrar: animación de confirmación + sonido opcional (beep via Web Audio API)

CASOS ESPECIALES:
- DNI no encontrado: mostrar mensaje "Socio no registrado" con opción de registrar nuevo
- Socio inactivo (baja lógica): mostrar mensaje "Socio inactivo — contactar al administrador"

### Método 2 — Por clave de 4 dígitos
FLUJO:
- El recepcionista abre la vista de accesos y deja la tablet/celular visible al socio
- La pantalla muestra SOLO un teclado numérico grande (tipo PIN pad) optimizado para touch,
  con los dígitos del 0 al 9 más botón de borrar y botón de confirmar
- El socio se acerca y teclea su clave de 4 dígitos él mismo (sin decirla en voz alta)
- Al completar los 4 dígitos y confirmar, el sistema busca al socio por su clave hasheada
- Muestra el mismo panel de estado que el Método 1 (foto, nombre, estado, botón registrar)
- La pantalla regresa al PIN pad automáticamente después de 4 segundos de mostrar el resultado

SEGURIDAD:
- Los dígitos se muestran como puntos (•) mientras se escriben, nunca en claro
- La clave se almacena hasheada con password_hash() en la BD
- Si clave incorrecta: mensaje "Clave incorrecta — intenta de nuevo", sin revelar si existe o no
- Después de 5 intentos fallidos consecutivos: bloqueo de 2 minutos con cuenta regresiva visible

ASIGNACIÓN DE CLAVE:
- El admin asigna la clave inicial al crear al socio (4 dígitos numéricos)
- El socio puede cambiarla desde su vista personal
- El admin puede resetearla desde el perfil del socio

### Método 3 — Por QR
FLUJO DEL SOCIO:
- Cada socio tiene un código QR único generado al momento de su registro
- El QR codifica un token único e irrepetible (no el ID ni el DNI directamente)
- El socio puede ver e imprimir su QR desde su vista personal en el celular
- También se puede imprimir el QR desde el perfil del socio en el panel admin
  (útil para entregar una tarjeta física al socio)

FLUJO DE RECEPCIÓN:
- El recepcionista abre la vista de escaneo en la tablet/celular de recepción
- La pantalla activa la cámara del dispositivo via getUserMedia()
- Muestra un visor de cámara en tiempo real con una guía de encuadre (marco animado)
- El socio acerca su celular o tarjeta con el QR al visor
- La librería jsQR detecta y decodifica el QR en tiempo real (sin presionar botón)
- Al leer el QR: el sistema valida el token, muestra el panel de estado del socio
  y registra la entrada automáticamente si la membresía está activa
- Confirmación visual con animación verde + nombre del socio durante 3 segundos,
  luego regresa automáticamente al visor de cámara

GENERACIÓN DEL QR:
- Usar la librería qrcode.js via CDN para generar el QR en el navegador como imagen PNG
- El token QR se genera en PHP con bin2hex(random_bytes(16)) al crear al socio
- Se almacena en la columna token_qr de la tabla socios
- El QR se puede regenerar (invalida el anterior) desde el perfil del socio

CASOS DE ERROR:
- QR no reconocido: "Código QR no válido"
- QR de otro gimnasio: "Este socio no pertenece a este gimnasio"
- Membresía vencida: bloqueo visual con opción de renovación rápida
- Cámara no disponible: mensaje de error claro con instrucción de permisos

CONFIGURACIÓN:
- El método se guarda en configuracion_gym.metodo_acceso con valores: 'dni', 'clave', 'qr'
- Al cambiar de método, el sistema conserva todos los datos anteriores
  (claves y QRs no se borran, solo cambia qué pantalla se usa en recepción)
- La vista de accesos detecta automáticamente el método configurado y carga la pantalla correcta

---

## MÓDULOS DEL SISTEMA

### MÓDULO 1 — Autenticación
- index.php: login unificado para todos los roles con diseño oscuro profesional
- Redirección automática al dashboard según rol tras login exitoso
- logout.php: destruye sesión y redirige al login
- Mensajes de error visuales inline (sin alert() del navegador)
- Campo de usuario y contraseña con toggle de visibilidad en contraseña

### MÓDULO 2 — Dashboards por rol

Admin:
- Métricas en cards: socios activos, socios vencidos, socios por vencer (próx. 7 días), ingresos del mes
- Alerta prominente si hay socios con membresía vencida o por vencer
- Tabla de últimas 5 entradas del día
- Acceso rápido a los módulos más usados

Recepcionista:
- Acceso directo a la vista de accesos (el módulo más usado por este rol)
- Resumen del día: total entradas registradas hoy
- Últimas 10 entradas del día

Socio:
- Estado de membresía con indicador de color dominante en pantalla
- Barra de progreso de días restantes
- Fecha de vencimiento destacada
- Últimas 5 asistencias
- Botón "Ver mi rutina" si tiene una asignada
- Botón "Ver mi QR" siempre visible (aunque el gym no use método QR,
  el socio puede tenerlo como respaldo)

Superadmin:
- Total de gyms registrados, activos y suspendidos
- Lista de gyms con nombre, admin, método de acceso configurado, fecha de registro y estado

### MÓDULO 3 — Gestión de Socios (admin)
- Listado con búsqueda por nombre o DNI y filtros: todos, activos, vencidos, por vencer
- Indicador de estado con color en cada fila de la tabla
- Alta de socio: nombre, apellido, DNI, teléfono, email (opcional),
  fecha de nacimiento, fecha de inscripción, foto (upload opcional),
  clave de 4 dígitos (si el gym usa método clave), estado activo
- Al guardar: se genera automáticamente el token_qr con bin2hex(random_bytes(16))
- Edición completa del perfil
- Baja lógica (campo activo = 0, no se elimina de BD)
- Vista de perfil del socio: datos + membresía actual + historial de membresías
  + historial de pagos + historial de accesos + rutina asignada + QR para imprimir

### MÓDULO 4 — Planes de Membresía (admin)
- CRUD de planes: nombre, duración en días, precio en soles (S/.)
- Datos semilla al instalar: Mensual (30 días, S/.80), Trimestral (90 días, S/.220),
  Semestral (180 días, S/.400), Anual (365 días, S/.750)
- Cada gym tiene sus propios planes, no comparte con otros gyms

### MÓDULO 5 — Membresías y Renovaciones (admin + recepcionista)
- Asignar plan a socio: seleccionar plan, fecha de inicio (hoy por defecto),
  el sistema calcula automáticamente la fecha de vencimiento
- Renovar: opción de iniciar desde hoy O desde la fecha de vencimiento anterior
- Al renovar: registrar pago automáticamente con monto del plan
- Historial completo de membresías por socio con estado de cada una

### MÓDULO 6 — Registro de Pagos (admin + recepcionista)
- Listado de pagos filtrable por socio, fecha y método de pago
- Registro manual de pago independiente
- Campos: socio, monto (S/.), método (efectivo / Yape / Plin / transferencia / otro),
  fecha, membresía asociada (opcional), notas
- Reporte de ingresos del mes con total destacado

### MÓDULO 7 — Control de Accesos
Ver sección "SISTEMA DE ACCESO DE SOCIOS" arriba para el detalle completo de los 3 métodos.

Adicionalmente:
- El admin puede ver el historial completo de accesos filtrado por socio, fecha o rango de fechas
- Registro manual de entrada por el admin (para correcciones)
- Cada acceso registra: socio_id, gimnasio_id, fecha_hora, método usado (dni/clave/qr),
  registrado_por (usuario_id del recepcionista o admin)

### MÓDULO 8 — Rutinas Personalizadas (admin)
- CRUD de plantillas de rutina: nombre, descripción, objetivo (fuerza/cardio/pérdida de peso/otro)
- Ejercicios dentro de cada rutina: nombre, series, repeticiones, descanso en segundos, notas
- Ordenables por drag-and-drop (usar HTML5 Drag and Drop API nativa)
- Asignar rutina a socio desde el perfil del socio o desde la lista de rutinas
- El socio ve su rutina asignada en su vista personal, formateada para móvil

### MÓDULO 9 — Gestión de Recepcionistas (admin)
- CRUD de usuarios recepcionistas del gym
- Campos: nombre completo, usuario (único), contraseña (hasheada), estado activo/inactivo
- El admin puede desactivar un recepcionista sin eliminarlo

### MÓDULO 10 — Reportes (admin)
- Socios por estado con totales
- Ingresos por mes: tabla mensual del año actual con total acumulado
- Asistencia por período: entradas por día en un rango de fechas seleccionable
- Socios con membresía por vencer en los próximos N días (configurable)
- Vista de impresión limpia para todos los reportes (@media print)

### MÓDULO 11 — Configuración del Gimnasio (admin)
- Datos del gym: nombre, RUC (opcional), dirección, teléfono, email
- Logo del gym (upload de imagen, se muestra en el header del sistema)
- Método de acceso de socios: selector entre DNI, Clave de 4 dígitos, o QR
  con descripción clara de cada opción y aviso de que el cambio es inmediato
- Zona horaria (por defecto: America/Lima)

### MÓDULO 12 — Panel Superadmin
- CRUD de gimnasios: nombre, dirección, plan contratado, estado (activo/suspendido)
- Al crear gym: se crea automáticamente usuario admin con contraseña temporal visible
  una sola vez en pantalla (mostrar en modal al crear)
- Vista de detalle de cada gym: métricas básicas (total socios, método de acceso, último acceso)
- Suspender/reactivar gym con un toggle (suspendido = admin no puede ingresar al sistema)

---

## BASE DE DATOS

### Convención de scripts SQL — REGLA OBLIGATORIA
Nunca modificar la BD directamente desde código PHP en producción.
Todos los cambios de esquema son scripts numerados en /sql/:
- 001_estructura_inicial.sql — todas las tablas
- 002_datos_semilla.sql — datos de prueba realistas
- 003_*.sql en adelante para migraciones futuras

### Esquema de tablas

```sql
-- Gimnasios registrados en el SaaS
CREATE TABLE gimnasios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  ruc VARCHAR(11),
  direccion VARCHAR(255),
  telefono VARCHAR(20),
  email VARCHAR(100),
  logo VARCHAR(255),
  estado ENUM('activo','suspendido') DEFAULT 'activo',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Configuración individual por gimnasio
CREATE TABLE configuracion_gym (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  metodo_acceso ENUM('dni','clave','qr') DEFAULT 'dni',
  zona_horaria VARCHAR(50) DEFAULT 'America/Lima',
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Todos los usuarios del sistema (todos los roles)
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NULL, -- NULL solo para superadmin
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol ENUM('superadmin','admin','recepcionista') NOT NULL,
  estado TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Socios del gimnasio
CREATE TABLE socios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  dni VARCHAR(15),
  telefono VARCHAR(20),
  email VARCHAR(100),
  fecha_nacimiento DATE,
  fecha_inscripcion DATE NOT NULL,
  foto VARCHAR(255),
  clave_acceso VARCHAR(255), -- 4 dígitos hasheados con password_hash()
  token_qr VARCHAR(64) UNIQUE, -- bin2hex(random_bytes(16))
  activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Planes de membresía por gimnasio
CREATE TABLE planes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  duracion_dias INT NOT NULL,
  precio DECIMAL(8,2) NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Membresías asignadas a socios
CREATE TABLE membresias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  gimnasio_id INT NOT NULL,
  plan_id INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  estado ENUM('activa','vencida','cancelada') DEFAULT 'activa',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (socio_id) REFERENCES socios(id),
  FOREIGN KEY (plan_id) REFERENCES planes(id),
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Pagos registrados
CREATE TABLE pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  gimnasio_id INT NOT NULL,
  membresia_id INT,
  monto DECIMAL(8,2) NOT NULL,
  metodo ENUM('efectivo','yape','plin','transferencia','otro') DEFAULT 'efectivo',
  fecha DATE NOT NULL,
  notas TEXT,
  registrado_por INT NOT NULL, -- usuario_id
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (socio_id) REFERENCES socios(id),
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id),
  FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
);

-- Log de accesos/entradas al gimnasio
CREATE TABLE accesos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  gimnasio_id INT NOT NULL,
  fecha_hora DATETIME NOT NULL,
  metodo_usado ENUM('dni','clave','qr','manual') NOT NULL,
  registrado_por INT, -- usuario_id del recepcionista/admin, NULL si fue automático por QR
  FOREIGN KEY (socio_id) REFERENCES socios(id),
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Plantillas de rutina
CREATE TABLE rutinas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gimnasio_id INT NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT,
  objetivo ENUM('fuerza','cardio','perdida_peso','tonificacion','otro') DEFAULT 'otro',
  activa TINYINT(1) DEFAULT 1,
  FOREIGN KEY (gimnasio_id) REFERENCES gimnasios(id)
);

-- Ejercicios de cada rutina
CREATE TABLE ejercicios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rutina_id INT NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  series INT,
  repeticiones VARCHAR(20), -- puede ser "12" o "12-15" o "Al fallo"
  descanso_segundos INT,
  notas TEXT,
  orden INT DEFAULT 0,
  FOREIGN KEY (rutina_id) REFERENCES rutinas(id)
);

-- Asignación de rutina a socio
CREATE TABLE socio_rutina (
  id INT AUTO_INCREMENT PRIMARY KEY,
  socio_id INT NOT NULL,
  rutina_id INT NOT NULL,
  fecha_asignacion DATE NOT NULL,
  activa TINYINT(1) DEFAULT 1,
  FOREIGN KEY (socio_id) REFERENCES socios(id),
  FOREIGN KEY (rutina_id) REFERENCES rutinas(id)
);
```

### Datos semilla (002_datos_semilla.sql)
Generar datos realistas para pruebas:
- 1 superadmin (usuario: superadmin, pass: Admin2026)
- 2 gimnasios: uno con método DNI, otro con método QR
- 1 admin por gym, 1 recepcionista por gym
- 8 socios por gym con estados variados (activos, vencidos, por vencer)
- Planes de membresía para cada gym
- Membresías y pagos de los últimos 3 meses
- Accesos de los últimos 15 días
- 2 rutinas con ejercicios completos

---

## ESTRUCTURA DE CARPETAS├── index.php                    ← login
├── logout.php
├── config/
│   └── db.php                   ← conexión PDO a MySQL
├── includes/
│   ├── header.php               ← navbar + verificación de sesión
│   ├── sidebar.php              ← menú lateral según rol
│   ├── footer.php
│   └── functions.php            ← helpers globales
├── modules/
│   ├── dashboard/
│   │   └── index.php            ← redirige al dashboard según rol
│   ├── socios/
│   │   ├── index.php            ← listado
│   │   ├── crear.php
│   │   ├── editar.php
│   │   └── perfil.php
│   ├── planes/
│   ├── membresias/
│   ├── pagos/
│   ├── accesos/
│   │   ├── index.php            ← detecta método y carga la vista correcta
│   │   ├── por_dni.php
│   │   ├── por_clave.php
│   │   └── por_qr.php
│   ├── rutinas/
│   ├── recepcionistas/
│   ├── reportes/
│   ├── configuracion/
│   ├── superadmin/
│   └── socio/                   ← vista exclusiva del rol socio
│       ├── dashboard.php
│       ├── mi_qr.php
│       └── cambiar_clave.php
├── ajax/
│   ├── buscar_socio.php         ← búsqueda en tiempo real por DNI
│   ├── validar_clave.php        ← validación de PIN con rate limiting
│   ├── validar_qr.php           ← validación de token QR
│   └── registrar_acceso.php     ← registro de entrada
├── assets/
│   ├── css/
│   │   ├── style.css            ← variables CSS y reset global
│   │   └── components.css       ← componentes reutilizables
│   └── js/
│       ├── main.js
│       ├── qr_scanner.js        ← lógica de escaneo con jsQR
│       └── qr_generator.js      ← generación de QR con qrcode.js
├── uploads/
│   └── fotos/
└── sql/
├── 001_estructura_inicial.sql
└── 002_datos_semilla.sql
---

## SEGURIDAD OBLIGATORIA

- password_hash() y password_verify() para contraseñas y claves de 4 dígitos
- PDO con prepared statements en el 100% de las queries — cero concatenación de variables en SQL
- Verificación de sesión activa y rol autorizado al inicio de CADA archivo PHP protegido
- Validación de gimnasio_id en cada operación — nunca confiar en datos del formulario para esto,
  siempre tomar el gimnasio_id de $_SESSION
- Sanitización con htmlspecialchars() en todos los outputs HTML
- Rate limiting para validación de clave: contador en sesión, bloqueo de 2 minutos tras 5 fallos
- Tokens QR no predecibles (bin2hex(random_bytes(16))) — nunca usar el ID del socio como QR
- Archivos de upload validados: solo imágenes (jpg, png, webp), máximo 2MB,
  renombrar con uniqid() antes de guardar

---

## HELPERS GLOBALES (includes/functions.php)

Crear al menos estas funciones:

```php
verificar_sesion()          // redirige al login si no hay sesión activa
verificar_rol($roles)       // acepta array de roles permitidos, redirige si no coincide
get_gimnasio_id()           // retorna gimnasio_id de la sesión, nunca del request
calcular_estado_membresia() // retorna 'activa', 'por_vencer' (<=7 días) o 'vencida'
dias_restantes($fecha_fin)  // retorna int con días hasta vencimiento (negativo si vencida)
formatear_fecha($fecha)     // retorna fecha en formato "15 de enero de 2026" en español
formatear_moneda($monto)    // retorna "S/. 80.00"
generar_token_qr()          // retorna bin2hex(random_bytes(16))
mensaje_flash($tipo, $texto) // guarda mensaje en sesión para mostrar en la siguiente carga
mostrar_flash()             // muestra y limpia el mensaje flash
```

---

## ESTÁNDARES DE CALIDAD

- Código comentado en español
- Sin dead code ni archivos vacíos o de prueba
- Mensajes de feedback visual en todas las acciones (sin alert() ni confirm() del navegador)
- Modales de confirmación propios para acciones destructivas (baja, cancelación)
- Botones de submit con estado de carga: deshabilitar + cambiar texto mientras procesa
- Tablas con empty state descriptivo cuando no hay datos
- Formularios con validación HTML5 + validación PHP server-side
- Paginación en todas las tablas con más de 20 registros
- Responsive verificado mentalmente en 375px (móvil pequeño) y 768px (tablet)
- Consistencia visual: mismo componente para el mismo patrón en todo el sistema

---

## PROCESO DE CONSTRUCCIÓN — ORDEN OBLIGATORIO

1. SQL: generar 001_estructura_inicial.sql y 002_datos_semilla.sql completos
2. Config y helpers: config/db.php e includes/functions.php
3. Layout base: header.php, sidebar.php, footer.php con sistema de diseño completo
   (variables CSS, tipografía, componentes base)
4. Autenticación: index.php (login) y logout.php
5. Dashboards: uno por cada rol con datos reales de la BD
6. Módulos en este orden:
   socios → planes → membresías → pagos →
   accesos (los 3 métodos) → rutinas →
   recepcionistas → reportes → configuración → superadmin → vista socio
7. Archivos AJAX: buscar_socio, validar_clave, validar_qr, registrar_acceso
8. Revisión de seguridad: verificar que cada archivo valide sesión, rol y gimnasio_id

Construye un módulo completo antes de pasar al siguiente.
Nunca dejes un archivo a medias para continuar en otro.
