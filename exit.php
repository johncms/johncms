<?php



 

session_name("SESID");
session_start();
$_SESSION=array();
setcookie('cpide', '');
setcookie('ckode', '');
setcookie(session_name(), '');
session_destroy(); 
header ("Location: index.php");
?>