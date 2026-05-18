<div align="center">
  <img src="https://img.shields.io/badge/GYM-SUITE-8A2BE2?style=for-the-badge&logo=electron&logoColor=white" alt="GymSuite Logo"/>
  <h1>🌌 GymSuite Nexus</h1>
  <p><b>El Sistema SaaS de Gestión de Gimnasios del Futuro</b></p>
  
  <p>
    <img src="https://img.shields.io/badge/Version-1.0.0-blue?style=flat-square" alt="Version"/>
    <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP"/>
    <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL"/>
    <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License"/>
  </p>
</div>

---

## 🚀 Sobre el Proyecto

**GymSuite** es una plataforma SaaS (Software as a Service) de vanguardia diseñada para centralizar, automatizar y potenciar la administración de múltiples sedes de gimnasios desde un único núcleo digital. Con una interfaz minimalista, oscura y de alta tecnología (estilo *UI/UX Pro Max*), ofrece un control absoluto sobre membresías, accesos biométricos/QR, rutinas y finanzas.

## ✨ Funcionalidades Principales

### 🏢 Arquitectura Multi-Tenant (SaaS)
- **Gestión Multi-Sede:** Un solo sistema para controlar múltiples gimnasios independientes.
- **Roles de Acceso Inteligentes:**
  - 👑 **Superadmin:** Control total del sistema SaaS, gestión de franquicias/gimnasios y configuraciones globales.
  - 🛡️ **Admin (Gerente):** Administración total de un gimnasio específico (reportes, finanzas, planes).
  - 🧑‍💻 **Recepcionista:** Operaciones diarias (control de acceso, registro de pagos, atención al cliente).

### ⚡ Control de Acceso Holográfico
- **Múltiples Métodos de Ingreso:** 
  - Escaneo de Código QR dinámico.
  - Ingreso por DNI.
  - Clave de acceso personal de 4 dígitos.
- **Log de Entradas en Tiempo Real:** Monitoreo en vivo de quién entra, a qué hora y con qué método.

### 💳 Gestión de Membresías y Finanzas
- **Planes Personalizables:** Creación de planes a la medida (mensual, anual, pases de un día) con control de precios y duración.
- **Alertas de Vencimiento:** Indicadores visuales de estados de membresía (Activa, Por Vencer, Vencida).
- **Pasarela de Pagos Multicanal:** Registro de cobros en Efectivo, Yape, Plin, Transferencias y Tarjetas.
- **Dashboard Financiero:** Gráficos y reportes de ingresos diarios/mensuales.

### 🏋️ Inteligencia Deportiva (Rutinas)
- **Base de Datos de Ejercicios:** Creación de plantillas de rutinas según objetivos (Fuerza, Cardio, Pérdida de Peso, Tonificación).
- **Asignación a Socios:** Planes de entrenamiento personalizados vinculados directamente al perfil del socio.

---

## 🛠️ Stack Tecnológico

El proyecto está construido sobre una arquitectura robusta, rápida y optimizada para producción:

- **Backend:** PHP 8+ (Vanilla/Procedural estructurado y seguro).
- **Base de Datos:** MySQL / MariaDB (con índices optimizados para prevenir *Full Table Scans* en Big Data).
- **Frontend:** HTML5 Semántico, CSS3 Vanilla (Estilos Futuristas, Glassmorphism, Dark Mode) y JavaScript (Vanilla/ES6+).
- **Seguridad:** 
  - Prevención XSS (`htmlspecialchars`).
  - Hashing avanzado de contraseñas (`password_hash`).
  - Variables de entorno y arquitectura segura para control de sesiones.

---

## 📦 Estructura del Proyecto

```text
gymsuite/
├── assets/          # Estilos CSS, JavaScript y recursos estáticos
├── config/          # Archivos de conexión a BD y configuraciones globales
├── includes/        # Componentes reutilizables (Headers, Footers, Navbars)
├── modules/         # Módulos del sistema (Accesos, Socios, Membresías, Pagos)
├── sql/             # Scripts de base de datos unificados
└── uploads/         # Archivos subidos por los usuarios (Fotos, Logos)
```

---

## ⚙️ Instalación y Despliegue Local

Sigue estos pasos para levantar el entorno de desarrollo en tu máquina (recomendado usar XAMPP, Laragon o Docker).

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/tu-usuario/gymsuite.git
   cd gymsuite
   ```

2. **Configurar la Base de Datos:**
   - Abre tu cliente de base de datos (Ej. phpMyAdmin).
   - Crea una base de datos llamada `gymsuite_db`.
   - Importa el archivo `sql/gymsuite_db_completo.sql`. Esto creará todas las tablas, índices y el usuario Superadmin por defecto.

3. **Configurar las credenciales:**
   - Duplica o edita el archivo en `config/db.php` (o configura tus variables de entorno si aplica) y ajusta los parámetros de conexión (Host, Usuario, Contraseña, BD).

4. **Acceso al Sistema:**
   - Ingresa desde tu navegador a `http://localhost/gymsuite`.
   - **Credenciales por defecto:**
     - Usuario: `superadmin`
     - Contraseña: `Admin2026`

---

## 🛡️ Seguridad y Buenas Prácticas
- **Ignorar Archivos Sensibles:** Se ha configurado `.gitignore` para omitir la carpeta de `uploads/` y archivos sensibles del sistema operativo.
- **Optimización SQL:** Las tablas cuentan con índices estratégicos para búsquedas rápidas de DNI y fechas de vencimiento.

---

<div align="center">
  <i>Diseñado con precisión algorítmica para el máximo rendimiento.</i><br>
  <b>© 2026 GymSuite SaaS.</b>
</div>
