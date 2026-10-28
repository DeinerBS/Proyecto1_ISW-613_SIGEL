<?php
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

verificarCsrf();

logoutUsuario();
redirect('/index.php?exito=sesion_cerrada');