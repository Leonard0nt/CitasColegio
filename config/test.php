<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP OK<br>";

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=csfchill_CitasCsf;charset=utf8mb4",
        "csfchill_CitasUser",
        "Csanfernando.2026"
    );

    echo "DB OK";

} catch (Exception $e) {
    echo $e->getMessage();
}