<?php
require_once __DIR__ . '/helpers.php';

const MENSAJES = [
    'error' => [
        'datos_invalidos'        => 'Debe completar todos los campos correctamente.',
        'credenciales_invalidas' => 'Correo o contraseña incorrectos.',
        'login_requerido'        => 'Debe iniciar sesión para acceder a esa página.',
        'sesion_expirada'        => 'Su sesión expiró por inactividad. Vuelva a iniciar sesión.',
        'error_servidor'         => 'Ocurrió un error. Intente de nuevo más tarde.',
        'cuenta_bloqueada' => 'Demasiados intentos fallidos. Espere unos minutos y vuelva a intentarlo.',
        'acceso_denegado' => 'No cuentas con permisos para acceder a esa sección.',
        'categoria_invalida'      => 'Ingrese un nombre y días entre 1 y 60.',
        'categoria_duplicada'     => 'Ya existe una categoría con ese nombre.',
        'categoria_no_encontrada' => 'La categoría no existe.',
        'categoria_con_equipos' => 'No se puede eliminar porque la categoría tiene equipos asignados.',
        'usuario_invalido'      => 'Revise los datos de nombre, correo y rol.',
        'password_invalida'     => 'La contraseña debe tener entre mínimo 8 caracteres.',
        'correo_duplicado'      => 'Ya existe un usuario con el mismo correo.',
        'usuario_no_encontrado' => 'El usuario no existe.',
        'no_modificar_propio'   => 'No es posible quitarse el rol de administrador ni desactivar su propia cuenta.',
        'no_eliminar_propio'    => 'No puede eliminar su propia cuenta.',
        'usuario_con_prestamos' => 'No se puede eliminar ya que el usuario cuenta con préstamos activos.',
        'equipo_invalido'      => 'Revise los datos, código: 2 a 30 letras con formato (ABC-123), nombre, categoría y estado.',
        'equipo_duplicado'     => 'Ya existe un equipo con ese código.',
        'equipo_no_encontrado' => 'El equipo no existe.',
        'equipo_conflicto'     => 'Otro usuario modificó este equipo mientras usted lo editaba. Revise los datos actuales e intente de nuevo.',
        'equipo_en_prestamo' => 'No se puede eliminar ya que el equipo está en un préstamo activo.',
        'imagen_invalida'      => 'Seleccione una imagen JPG, PNG o WEBP válida.',
        'imagen_grande'        => 'La imagen no puede superar 2 MB.',
        'imagen_limite'        => 'El equipo ya tiene el máximo de 5 imágenes.',
        'imagen_no_encontrada' => 'La imagen no existe.',
        ],
    'exito' => [
        'sesion_cerrada' => 'Sesión cerrada correctamente.',
        'categoria_creada'      => 'Categoría creada correctamente.',
        'categoria_actualizada' => 'Categoría actualizada correctamente.',
        'categoria_eliminada' => 'Categoría eliminada correctamente.',
        'usuario_creado'      => 'Usuario creado correctamente.',
        'usuario_actualizado' => 'Usuario actualizado correctamente.',
        'usuario_eliminado' => 'Usuario eliminado correctamente.',
        'equipo_creado'      => 'Equipo creado correctamente.',
        'equipo_actualizado' => 'Equipo actualizado correctamente.',
        'equipo_eliminado' => 'Equipo eliminado correctamente.',
        'imagen_subida'    => 'Imagen agregada correctamente.',
        'imagen_eliminada' => 'Imagen eliminada correctamente.',
    ],
];

// Muestra una alerta si en la URL viene ?error=codigo o ?exito=codigo
function mostrarAlerta()
{
    foreach (['error' => 'danger', 'exito' => 'success'] as $tipo => $estilo) {
        $codigo = $_GET[$tipo] ?? null;
        if (is_string($codigo) && isset(MENSAJES[$tipo][$codigo])) {
            echo '<div class="alert alert-' . $estilo . '">' . e(MENSAJES[$tipo][$codigo]) . '</div>';
        }
    }
}