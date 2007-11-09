<?php




define('_IN_PUSTO', 1);
 
$textl='Управление';
require("../incfiles/db.php");
require("../incfiles/func.php");
require("../incfiles/data.php");

if ($dostmod==1){

if (!empty($_GET['act'])){$act=check($_GET['act']);}
switch ($act){
case "users":
if ($dostadm==1){
if (empty($_POST['nik'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Вы не ввели логин!<br/><a href='main.php>Назад</a><br/>";require ("../incfiles/end.php");exit;}
$nik=check($_POST['nik']);
$q= mysql_query("select * from `users` where name='".$nik."';");
$q2=mysql_num_rows($q);
if ($q2==0){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Нет такого юзера!<br/><a href='main.php'>Назад</a><br/>";require ("../incfiles/end.php");exit;}
$q1=mysql_fetch_array($q);

header ("location: editusers.php?act=edit&user=$q1[id]");
}else{
header ("location: main.php");}
break;
default:
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo"<a href='moderka.php'>Комната общения</a><br/>";

if ($dostadm==1){
echo"<a href='set.php'>Настройки системы</a><br/>";
echo"<a href='news.php'>Управление новостями</a><br/>";
} 
if ($dostsmod==1){
echo"<a href='forum.php'>Управление форумом</a><br/>";
echo"<a href='chat.php'>Управление чатом</a><br/>";
}
if ($dostkmod==1){

}
echo"<a href='banned.php'>Кто в бане</a><br/>";
if ($dostadm==1){
echo "<hr/>Работа с юзерами.<br/>Введите логин юзера:<br/><form action='main.php?act=users' method='post'><input type='text' name='nik'/><br/><input type='submit' value='Ok!'/></form><br/>";
}
break;}
  } else{ header ("Location: ../index.php?err");}
require ("../incfiles/end.php"); 
?>