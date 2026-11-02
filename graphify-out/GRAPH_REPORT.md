# Graph Report - gymsuite  (2026-11-02)

## Corpus Check
- 53 files · ~35,550 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 5 file(s) not represented in the graph (top: (none) 3, .css 2)

## Summary
- 109 nodes · 91 edges · 48 communities (5 shown, 43 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 6 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `23cd3445`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- jsQR.min.js
- functions.php
- por_qr.php
- README.md
- por_clave.php
- por_dni.php
- AGENTS.md

## God Nodes (most connected - your core abstractions)
1. `d()` - 6 edges
2. `procesarToken()` - 6 edges
3. `n()` - 5 edges
4. `c()` - 5 edges
5. `k()` - 5 edges
6. `✨ Funcionalidades Principales` - 5 edges
7. `B()` - 4 edges
8. `i()` - 4 edges
9. `a()` - 4 edges
10. `renderSocio()` - 4 edges

## Surprising Connections (you probably didn't know these)
- None detected - all connections are within the same source files.

## Import Cycles
- None detected.

## Communities (48 total, 43 thin omitted)

### Community 0 - "jsQR.min.js"
Cohesion: 0.38
Nodes (9): a(), B(), c(), d(), i(), k(), l(), n() (+1 more)

### Community 2 - "por_qr.php"
Cohesion: 0.30
Nodes (11): beepError(), beepOK(), elegirCamaraFisica(), esVirtual(), iniciarCamara(), arrancarScanner(), mostrarError(), mostrarErrorCamara() (+3 more)

### Community 3 - "README.md"
Cohesion: 0.18
Nodes (10): 🏢 Arquitectura Multi-Tenant (SaaS), ⚡ Control de Acceso Holográfico, 📦 Estructura del Proyecto, ✨ Funcionalidades Principales, 💳 Gestión de Membresías y Finanzas, ⚙️ Instalación y Despliegue Local, 🏋️ Inteligencia Deportiva (Rutinas), 🛡️ Seguridad y Buenas Prácticas (+2 more)

### Community 4 - "por_clave.php"
Cohesion: 0.32
Nodes (3): resetPin(), startLockout(), updateDisplay()

### Community 5 - "por_dni.php"
Cohesion: 0.39
Nodes (5): buscarSocio(), getAvatarHTML(), getEstadoLabel(), renderNoEncontrado(), renderSocio()

## Knowledge Gaps
- **43 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Are the 4 inferred relationships involving `c()` (e.g. with `B()` and `k()`) actually correct?**
  _`c()` has 4 INFERRED edges - model-reasoned connections that need verification._