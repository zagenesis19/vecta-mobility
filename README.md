# 🚗 Vecta Mobility (Rama: Cambios)

Sistema de gestión de transporte con **Panel Administrativo**, **Seguridad Reforzada** y **Simulación de Redes Sociales**.

## ✨ Nuevas Funcionalidades (Rama `cambios`)

1.  **Panel de Administración**: Gestión completa de Pasajeros y Conductores (`/admin/users`).
2.  **Sistema de Sanciones**: Bloqueo de usuarios con razón específica y expulsión inmediata de sesiones activas.
3.  **Mensajería Interna**: Envío de mensajes directos desde el Admin a los Usuarios.
4.  **Perfil de Instagram**: Simulación de feed con diseño publicitario (`/instagram`).

---

## 🚀 Guía de Instalación Rápida

Sigue estos pasos para instalar esta versión específica:

### 1. Descargar el Código (Rama Correcta)
```bash
git clone https://github.com/zagenesis19/vecta-mobility.git
cd vecta-mobility
git checkout cambios
git pull origin cambios
```

### 2. Instalar Dependencias
```bash
composer install
npm install
```

### 3. Configurar Entorno
Haz una copia del archivo `.env.example` y renómbralo a `.env`. Luego configura tu base de datos:

```ini
DB_DATABASE=vecta_mobility
DB_USERNAME=root
DB_PASSWORD=
```

Genera la clave de aplicación:
```bash
php artisan key:generate
```

### 4. Base de Datos y Usuarios de Prueba
Ejecuta las migraciones y los seeders para tener el **Usuario Admin** listo:

```bash
php artisan migrate:fresh --seed
```

---

## 🔑 Credenciales de Acceso

El sistema ya viene con cuentas pre-creadas para que pruebes todo:

| Rol | Email | Contraseña |
| :--- | :--- | :--- |
| **Administrador** | `admin@vecta.com` | `password` |
| **Conductor** | `conductor1@test.com` | `password` |
| **Pasajero** | `cliente1@test.com` | `password` |

---

## ▶️ Iniciar el Proyecto

Necesitas dos terminales:

**Terminal 1 (Backend):**
```bash
php artisan serve
```

**Terminal 2 (Frontend):**
```bash
npm run dev
```

Ahora entra a: [http://127.0.0.1:8000](http://127.0.0.1:8000)