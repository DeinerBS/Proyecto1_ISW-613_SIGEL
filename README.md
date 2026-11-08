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
| Profesor | alli.rodriguez@example.com | `Profe123+` |
| Profesor | kay.garcia@example.com | `Profe123+` |

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

## 5. Decisiones e interpretaciones

El enunciado deja algunos puntos abiertos. Estas son las decisiones tomadas y su justificación.

### 5.1 Aclaraciones del docente
- **Roles:** admin, operador y profesor. El operador registra los préstamos y estos se asignan a usuarios con rol profesor. Se eliminaron `permisos` y `rol_permiso` del diagrama (lo "electivo" ya no aplica).
- **Bitácora:** se registran las acciones de **crear, editar y eliminar** (incluidos los cambios de estado de los préstamos). No se registran consultas ni búsquedas.
- **Intentos fallidos:** límite de 3 intentos.

### 5.2 Estructura y configuración
- La estructura sigue la sugerida, adaptada a la organización usada en los workshops del curso: `utils/` cumple la función de `includes/`.
- Credenciales en `config/config.php` (excluido de Git); `config/config.example.php` sirve de plantilla.
- Zona horaria de la aplicación: `America/Costa_Rica`.
- Errores no controlados: un manejador global los registra en el log y muestra un mensaje genérico.

### 5.3 Modelo de datos
- **Imágenes:** tabla `equipo_imagenes` (relación 1 a N) en lugar de la columna `imagen_ruta`, porque un equipo puede tener varias imágenes. Solo se guarda el nombre del archivo; la carpeta es fija.
- **Eliminación lógica** (`eliminado_en`) también en `usuarios` y `categorias`.
- `usuarios.bloqueado_hasta` para el bloqueo por intentos fallidos.
- Fechas de préstamo como `DATETIME`, porque un préstamo puede durar horas.
- Tabla `configuracion` de tipo clave-valor para el horario de la regla 5.
- El seed calcula las fechas de los préstamos a partir de `NOW()` para que siga siendo válido cualquier día.

### 5.4 Autenticación y sesión
- Inicio de sesión con correo y contraseña.
- **Bloqueo:** tras 3 intentos fallidos la cuenta se bloquea **2 minutos** y el contador se reinicia. El bloqueo se revisa antes que la contraseña. *Limitación conocida:* el mensaje de bloqueo solo aparece para correos existentes.
- Mensaje genérico para cualquier fallo de inicio de sesión, incluidas cuentas inactivas o eliminadas.
- Sesión expira tras **30 minutos** de inactividad.
- **"Recordarme":** 14 días; el token rota en cada uso. Si la sesión expira pero hay un token válido, el usuario sigue dentro.
- El rol y el estado del usuario se vuelven a leer de la base de datos en cada petición: un cambio de rol o una desactivación se aplica de inmediato.

### 5.5 Permisos por rol

| Sección | Admin | Operador | Profesor |
|---|---|---|---|
| Usuarios, categorías, reportes, configuración | Gestiona | — | — |
| Equipos | Gestiona | Consulta | — |
| Préstamos | — | Gestiona | Consulta los suyos |

### 5.6 CRUD
- **Registros por página:** 5, 10, 25 o 50 (10 por defecto). Una cookie por CRUD, válida por un año.
- **Búsqueda:** categorías por nombre; usuarios por nombre o correo; equipos por código o nombre.
- Los duplicados se controlan con restricciones `UNIQUE` en la base de datos. *Limitación conocida:* un registro eliminado lógicamente sigue ocupando su nombre, correo o código.
- **Bitácora:** nunca se guarda el hash de una contraseña (solo `password_cambiada: true`). Si falla el registro en la bitácora, la operación completa se revierte.

**Categorías**
- Días máximos de préstamo: de 1 a 90.
- No se puede eliminar una categoría con equipos vigentes.

**Usuarios**
- Contraseña de 8 a 72 caracteres (bcrypt solo usa los primeros 72 bytes).
- El admin no puede quitarse su rol, desactivarse ni eliminarse a sí mismo.
- Cambiar la contraseña revoca los tokens "Recordarme" del usuario.
- No se puede eliminar un usuario con préstamos activos.

**Equipos**
- Código: de 2 a 30 caracteres entre letras mayúsculas, números y guiones; se convierte a mayúsculas.
- **Bloqueo optimista** con la columna `version` para evitar que dos usuarios se sobrescriban los cambios.
- "Dado de baja" es un equipo real retirado (sigue visible); "eliminar" es para registros creados por error.
- No se puede eliminar un equipo que está en un préstamo activo.

**Imágenes**
- Formatos JPG, PNG y WEBP; máximo 2 MB; hasta 5 imágenes por equipo.
- Al eliminar una imagen se borran el registro y el archivo (la bitácora conserva el registro).
- Se sirven a través de `imagen.php` solo a admin y operador.

**Equipos recientes (8.3)**
- La cookie guarda solo los identificadores de los últimos 5 equipos consultados, durante 30 días.
- Se muestran en el listado de equipos y primero en el formulario de nuevo préstamo.

### 5.7 Préstamos
- **Préstamo activo:** estados `solicitado`, `aprobado` y `entregado`. Una solicitud aún no aprobada ya reserva el equipo.
- El operador registra el préstamo en estado `solicitado` a nombre de un profesor activo.
- Entre 1 y 10 equipos por préstamo; todos deben estar operativos. Si uno no cumple, se rechaza el préstamo completo.
- La fecha de inicio no puede estar en el pasado.
- **Días máximos:** la duración real (en horas) no puede superar los días máximos de la categoría de **ninguno** de los equipos (3 días = 72 horas).
- **Traslape:** se aplica la condición de la sección 5.3 con desigualdad estricta, por lo que un préstamo puede empezar exactamente cuando termina otro.
- **Concurrencia:** las filas de los equipos se bloquean con `SELECT ... FOR UPDATE` dentro de la transacción, en orden ascendente de id para evitar interbloqueos.

- **Transiciones de estado:** solicitado → aprobado → entregado → devuelto; se puede cancelar desde solicitado o aprobado. Devuelto y cancelado son estados finales. Un préstamo entregado no se cancela.
- Todas las transiciones las realiza el operador; el profesor solo consulta sus préstamos.
- Al entregar, todos los equipos deben seguir operativos.
- **Devolución completa:** se registran juntos todos los equipos del préstamo (con su fecha de devolución).
- El cambio de estado solo se aplica si el estado no cambió desde que se leyó (`UPDATE ... WHERE estado = :actual`).

### 5.8 Regla 5: horario de atención
- **Interpretación de "inicio y entrega":** deben estar de lunes a viernes dentro del horario:
  - la fecha de inicio y la fecha de fin (devolución) del préstamo;
  - el momento de marcar un préstamo como entregado y de registrar su devolución.

  Crear y aprobar solicitudes son trámites administrativos y se permiten a cualquier hora.
- **Límites inclusivos:** con horario 08:00–17:00 se acepta exactamente a las 08:00 y a las 17:00 (todo ese minuto); a las 17:01 ya no.
- Sábado y domingo siempre se rechazan.
- El horario se lee de la tabla `configuracion` en cada validación; el admin lo cambia desde **Configuración** (queda en la bitácora) y surte efecto sin tocar código. Valores iniciales: 08:00 a 17:00.