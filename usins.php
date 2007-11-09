<?php
	

define('_IN_PUSTO', 1);


require_once("incfiles/db.php");
require_once("incfiles/func.php");
require_once("incfiles/data.php");
require_once("incfiles/stat.php");
require_once("incfiles/head.php");
require_once("incfiles/inc.php");







switch ($_GET['act']){
case "go":


$dir = opendir ("local/profil"); 
while ($file = readdir ($dir)) {
if (ereg (".prof$", "$file")){
$a[]=$file; }}

closedir ($dir);
$i=0;
foreach ($a as $us){
$text = @file("local/profil/$us");
$udc = explode(":||:",$text[0]);
if ($udc[15]=="M"){$sex="m";}else{$sex="zh";}

mysql_query("insert into `users` values(0,'".$udc[0]."','".md5($udc[1])."','','".$sex."','0','0','0','0','','".$realtime."','','".$udc[4]."','".$udc[19]."','".$udc[5]."','".$udc[3]."','".$udc[2]."','','0','','".$ipp."','".$agn."','20','20','','','','','','0','','','','','1','','','0','0','0','0','','','0','','','','0','','','','0','0','0','0','0','0','0','','0','','','','','','','','','','','','','','0','0','20','','0','','0','0','');");
$i++;}

echo "В БД занесено $i юзеров<br/>";
break;
default:
echo "<a href='usins.php?act=go'>Занести юзеров в БД</a><br/><br/>";
break;}


require ("incfiles/end.php");

?>