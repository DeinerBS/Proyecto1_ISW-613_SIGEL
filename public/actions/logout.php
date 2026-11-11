<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';

requirePost('/index.php');

verificarCsrf();

revocarRecordarme($conn);
logoutUsuario();
redirect('/index.php?exito=sesion_cerrada');