# 🚗 Vecta Mobility

Sistema de gestión de transporte. 

## 🛠️ Tecnologías Utilizadas (Stack TALL/VILT)

* **Backend:** Laravel 10 (PHP 8.2+)
* **Frontend:** Vue.js 3 (Composition API)
* **Estilos:** Tailwind CSS
* **Comunicación:** Inertia.js
* **Base de Datos:** MySQL / MariaDB

---

## 📋 Requisitos Previos

Antes de instalar, asegúrate de tener en tu computadora:
1.  **PHP** >= 8.2
2.  **Composer** (Gestor de paquetes de PHP)
3.  **Node.js** y **NPM** (Para el Frontend)
4.  **MySQL** (XAMPP, Laragon o similar)

---

## 🚀 Guía de Instalación (Paso a Paso)

Sigue estos comandos en tu terminal para levantar el proyecto desde cero:

### 1. Clonar el repositorio
Descarga el código fuente en tu máquina:

```bash
git clone https://github.com/zagenesis19/vecta-mobility.git
cd vecta-mobility
```

### 2. Instalar Dependencias del Backend (Laravel)
Descarga todas las librerías de PHP necesarias:

```bash
composer install
```

### 3. Instalar Dependencias del Frontend (Vue/Tailwind)
Descarga las librerías de JavaScript y estilos:

```bash
npm install
```

### 4. Configurar el Entorno (.env)
Laravel necesita un archivo de configuración con tus claves. Hacemos una copia de la plantilla:

```bash
cp .env.example .env
```

### 5. Generar la Clave de Aplicación
Esto encripta las sesiones y datos seguros:

```bash
php artisan key:generate
```

### 6. Configurar la Base de Datos
1.  Abre tu gestor de base de datos (phpMyAdmin, TablePlus, HeidiSQL).
2.  **Crea una base de datos vacía** con el nombre: `vecta_mobility`.
3.  Abre el archivo `.env` en tu editor, busca estas líneas y edítalas con tus datos:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vecta_mobility
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Ejecutar Migraciones
Crea las tablas (usuarios, viajes, vehículos) en la base de datos:

```bash
php artisan migrate
```

---

## ▶️ Cómo Iniciar el Servidor

Para trabajar, necesitas **dos terminales abiertas**:

**Terminal 1 (Backend):**
```bash
php artisan serve
```

**Terminal 2 (Frontend):**
```bash
npm run dev
```

Ahora entra a: `http://127.0.0.1:8000`

---

## 👤 Usuarios de Prueba (Opcional)

Para crear un Administrador rápidamente:

1. Ejecuta: `php artisan tinker`
2. Pega este código y dale Enter:

```php
\App\Models\User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@vecta.com',
    'role' => 'admin',
    'password' => bcrypt('password'),
]);
```

3. Escribe `exit` para salir.