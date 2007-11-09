<?php


define('_IN_PUSTO', 1);
session_name('SESID');
session_start();
$headmod='ignor';
$textl='Игнор-лист';
require("../incfiles/db.php");
require("../incfiles/func.php");
require("../incfiles/data.php");
require("../incfiles/head.php");
require("../incfiles/inc.php");
if (!empty($_SESSION['pid'])){
if (!empty($_GET['act'])){$act=$_GET['act'];}
switch ($act){

case "add":
echo   "<form action='ignor.php?act=edit&amp;add=1' method='post'>
	 Введите ник<br/>";
echo   "<input type='text' name='nik' value='' /><br/>
 <input type='submit' value='Добавить' />  
  </form>";
echo "<a href='?'>В список</a><br/>";
break;
case "edit":
if (!empty($_POST['nik'])){
$nik=check($_POST['nik']);}elseif (!empty($_GET['nik'])){
$nik=check($_GET['nik']);}else{
if (empty($_GET['id'])){
echo "Ошибка!<br/><a href='ignor.php'>В список</a><br/>";
require ("../incfiles/end.php");exit;}

$id = intval(check(trim($_GET['id'])));
$nk = mysql_query("select * from `users` where id='".$id."';");
$nk1=mysql_fetch_array($nk);
$nik=$nk1[name];}
if (!empty($_GET['add'])){  $add=intval($_GET['add']);}
$adс = mysql_query("select * from `privat` where me='".$login."' and ignor='".$nik."';");

	$adc1 = mysql_num_rows($adс);
$addc = mysql_query("select * from `users` where name='".$nik."';");
$addc2=mysql_fetch_array($addc);
	$addc1 = mysql_num_rows($addc);
if ($add==1){

if ($addc2[rights]>=1||$nik==$nickadmina||$nik==$nickadmina){
echo "Администрацию нельзя в игнор!!!<br/><a href='ignor.php'>В список</a><br/>";
require ("../incfiles/end.php");exit;}



if  ($adc1==0){
if  ($addc1==1){
		mysql_query("insert into `privat` values(0,'".$foruser."','','".$realtime."','','','','','0','".$login."','','".$nik."','');");
echo "Юзер добавлен в игнор<br/>";}else{echo "Данный логин отсутствует в базе данных<br/>";}}else{echo "Данный логин уже есть в Вашем игноре<br/>";}}else{  
if  ($adc1==1){
if  ($addc1==1){

mysql_query("delete from `privat` where me='".$login."' and ignor='".$nik."';");
echo "Юзер удалён из игнора<br/>";}else{echo "Данный логин отсутствует в базе данных<br/>";}}else{echo "Этого логина нет в Вашем игноре<br/>";}}
echo "<a href='?'>В список</a><br />";

break;




default:
$ig = mysql_query("select * from `privat` where me='".$login."' and ignor!='';");
$colig = mysql_num_rows($ig);
while($mass = mysql_fetch_array($ig)){
$uz = mysql_query("select * from `users` where name='$mass[ignor]';");
$mass1 = mysql_fetch_array($uz);
echo "$mass[ignor] <a href='ignor.php?act=edit&amp;id=".$mass1[id]."'>[Удалить]</a>";
$ontime=$mass1[pvrem];
$ontime2=$ontime+300;
if ($realtime>$ontime2){echo" [Off]<br/>";}else{echo" [ON]<br/>";}}

echo "<hr /><a href='?act=add'>Добавить юзера в игнор</a><br />";
break;}
}
echo "<a href='privat.php?'>В приват</a><br />";
require ("../incfiles/end.php");
?>