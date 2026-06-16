<?php

// Ajusta esta ruta si en tu cPanel la carpeta config queda en otra ubicación.
// Este archivo asume esta estructura:
// /home/usuario/config/database.php
// /home/usuario/public_html/includes/config-path.php

if (!defined('CITAS_CSF_ENTRY')) {
    define('CITAS_CSF_ENTRY', true);
}

require_once __DIR__ . '/../config/database.php';
