# SIGEL · Sistema de Gestión de Préstamo de Equipos de Laboratorio

Proyecto 1 · ISW-613 Programación en Ambiente Web I · Universidad Técnica Nacional

Aplicación web en PHP y MySQL para administrar el préstamo de equipos de laboratorio:
usuarios, categorías, equipos, préstamos, bitácora de auditoría y reportes.

---

## 1. Requisitos

- XAMPP con PHP 8 y MySQL/MariaDB
- Git
- Navegador web

---

## 2. Instalación y despliegue local

### 2.1 Clonar el repositorio

```bash
mkdir -p /c/proyectos
cd /c/proyectos
git clone https://github.com/DeinerBS/Proyecto1_ISW-613_SIGEL.git
```

### 2.2 Configurar la conexión

Copiar la plantilla de configuración y completar los datos de la base de datos:

```bash
cd Proyecto1_ISW-613_SIGEL
cp config/config.example.php config/config.php
```

> `config/config.php` contiene credenciales y no se incluye en el repositorio.

### 2.3 Crear la base de datos

En phpMyAdmin, importar en este orden:

1. `sql/schema.sql` — crea la base de datos `sigel` y sus tablas.
2. `sql/seed.sql` — carga los datos de prueba.

> ⚠️ `schema.sql` elimina y recrea la base de datos completa.

### 2.4 Configurar el virtual host

En `C:\xampp\apache\conf\extra\httpd-vhosts.conf` agregar:

```apache
<VirtualHost *:80>
    ServerName sigel.local
    DocumentRoot "C:/proyectos/Proyecto1_ISW-613_SIGEL/public"
    <Directory "C:/proyectos/Proyecto1_ISW-613_SIGEL/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

En `C:\Windows\System32\drivers\etc\hosts` (editar como administrador) agregar:

```
127.0.0.1 sigel.local
```

Reiniciar Apache desde el panel de XAMPP.

### 2.5 Abrir la aplicación

http://sigel.local

---

## 3. Credenciales de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@example.com | `Admin123+` |
| Operador | operador@example.com | `Opera123+` |
| Profesor | alli@example.com | `Profe123+` |
| Profesor | kay@example.com | `Profe123+` |

---

## 4. Estructura del proyecto

```
Proyecto1_ISW-613_SIGEL/
├── config/          Configuración y credenciales (config.php fuera del repositorio)
├── utils/           Funciones reutilizables: conexión a BD, autenticación, validaciones
├── storage/
│   └── equipos/     Imágenes subidas (sin acceso directo desde la web)
├── sql/             schema.sql y seed.sql
├── public/          Único directorio expuesto por Apache (DocumentRoot)
│   ├── index.php
│   ├── actions/     Procesan los formularios (POST)
│   └── pages/       Vistas del sistema
│       └── common/  Encabezado, navegación y pie de página
├── .gitignore
└── README.md
```

Solo `public/` es accesible desde el navegador. Configuración, funciones, imágenes
y scripts SQL quedan fuera del alcance web.