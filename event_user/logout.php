<?php
//выход
session_start();
unset($_SESSION['user']);

header("Location:../index.php?page=sign-in");
exit();
?>