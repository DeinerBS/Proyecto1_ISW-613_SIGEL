<?php
const MAX_BYTES_IMAGEN    = 2 * 1024 * 1024; // 2 MB
const MAX_IMAGENES_EQUIPO = 5;
const EXTENSIONES_IMAGEN  = ['jpg', 'jpeg', 'png', 'webp'];

// MIME real permitido, extensión con la que se guarda
const TIPOS_IMAGEN = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// Las imágenes viven fuera de public
function rutaImagen($nombreArchivo)
{
    return __DIR__ . '/../storage/equipos/' . $nombreArchivo;
}