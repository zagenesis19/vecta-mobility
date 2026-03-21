# AGENTS.md

> This file is mirrored across CLAUDE.md, AGENTS.md, and GEMINI.md so the same instructions load in any AI environment.

## Visión General

**Vecta Mobility** es una plataforma de movilidad construida con una arquitectura monolítica modular.

-   **Propósito**: Gestión de transporte y movilidad.
-   **Rol del Agente**: Actuar como Desarrollador Fullstack Senior. Se espera código robusto, seguro y bien documentado.
-   **Arquitectura**: Laravel 10 (Backend) + Inertia.js (Glue) + Vue 3 (Frontend).

## Configuración / Build & Test

Este proyecto utiliza **Laravel 10**, **Vue 3** y **TailwindCSS**.

### Comandos de Instalación
```bash
# Instalar dependencias de backend
composer install

# Instalar dependencias de frontend
npm install

# Configuración inicial (si es necesario)
if [ ! -f .env ]; then cp .env.example .env; fi
php artisan key:generate
php artisan migrate --seed
```

### Comandos de Ejecución
```bash
# Compilar assets en desarrollo (mantener corriendo)
npm run dev

# Ejecutar servidor local
php artisan serve
```

## Estilo de Código / Convenciones

### Backend (Laravel/PHP)
-   **Estándar**: PSR-12.
-   **Tipado**: Estricto (`declare(strict_types=1);` donde sea posible).
-   **Principios**: SOLID, Repository Pattern (si aplica), Fat Models/Skinny Controllers o Services para lógica compleja.

### Frontend (Vue/JS)
-   **Sintaxis**: Composition API con `<script setup>`.
-   **Estilo**: TailwindCSS. Evitar CSS custom si existen utilidades de Tailwind.
-   **Componentes**: Reutilizables, nombres PascalCase (e.g., `UserProfile.vue`).

## Testing

El proyecto utiliza PHPUnit.

```bash
# Ejecutar todas las pruebas
php artisan test
```

## Flujo de Trabajo (Git/PR)

-   **Ramas**:
    -   `main`: Producción.
    -   `develop`: Desarrollo principal.
    -   `feature/nombre-feature`: Nuevas funcionalidades.
-   **Commits**: Seguir **Conventional Commits**.
    -   Ejemplo: `feat(auth): implementar login con 2FA`
    -   Ejemplo: `fix(map): corregir renderizado de polígonos`

## Seguridad

-   **Credenciales**: Nunca commitear claves API o secretos. Usar `.env`.
-   **Validación**: Validar siempre los datos de entrada (FormRequests en Laravel).

## Filosofía de Trabajo del Agente

Operamos bajo una arquitectura mental de 3 capas para maximizar la fiabilidad:

1.  **Directiva (Qué hacer)**: Entender el objetivo y los requisitos antes de actuar.
2.  **Orquestación (Toma de decisiones)**: Planificar los pasos (`implementation_plan.md`), decidir qué herramientas usar y cómo verificar.
3.  **Ejecución (Hacer el trabajo)**: Escribir código determinista, ejecutar comandos y verificar resultados.

**Principios Operativos:**
-   **Verificar primero**: Antes de asumir, leer el código existente.
-   **Auto-corrección**: Si un comando falla, analizar el error, corregir y reintentar. Documentar el aprendizaje.
-   **Mejora continua**: Actualizar la documentación y los planes a medida que se descubre nueva información.
