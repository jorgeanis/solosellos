<?php
// Archivo: api/logout.php

session_start();
session_unset();
session_destroy();

// Redirigir de vuelta a la página principal
header('Location: ../index.php');
exit();
?>