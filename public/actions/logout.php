<?php
require_once __DIR__ . '/../../utils/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

logoutUsuario();
redirect('/index.php?exito=sesion_cerrada');