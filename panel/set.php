<?php


define('_IN_PUSTO', 1);
session_name("SESID");
session_start();
$textl='Настройки сайта';
require("../incfiles/db.php");
require("../incfiles/func.php");
require("../incfiles/data.php");
require("../incfiles/head.php");
require("../incfiles/inc.php");
if ($dostadm==1){
if (!empty($_GET['act'])){$act=check($_GET['act']);}
switch ($act){
case "set":
$nadm=check($_POST['nadm']); 
$nadm2=check($_POST['nadm2']); 
$madm=htmlspecialchars($_POST['madm']);
$sdv=check($_POST['sdvigclock']);
$cop=check($_POST['copyright']); 
$url=check($_POST['homeurl']); 
$ext=check($_POST['rashstr']); 
$gz=intval(check($_POST['gz'])); 
$gbk=intval(check($_POST['gbk']));
$admp=check($_POST['admp']); 
$fm=intval(check($_POST['fm']));
$rm=intval(check($_POST['rm']));
$fsz=intval(check($_POST['flsz']));    	  
mysql_query("update `settings` set  nickadmina2='".$nadm2."', nickadmina='".$nadm."', emailadmina='".$madm."', sdvigclock='".$sdv."',  copyright='".$cop."', homeurl='".$url."', rashstr='".$ext."', gzip='".$gz."' ,admp='".$admp."', fmod='".$fm."', flsz='".$fsz."',gb='".$gbk."', rmod='".$rm."' where id='1';");
header ("location: set.php?set");
break;
default:

if (isset($_GET[set])){
echo"<div style='color: red'>Сайт настроен</div>";}

echo"Настройка системы.<br/>";
echo "Время на сервере: ".date("H.i(d/m/Y)")."";
$setdata = array("sdvigclock"=>"Временной сдвиг:", "copyright"=>"Ваш копирайт:", "homeurl"=>"Главная сайта без слэша в конце:", "flsz"=>"Макс.допустимый размер файлов(кб.)", "admp"=>"Папка с админкой:", "rashstr"=>"Расширение страниц:");

echo"<form method='post' action='set.php?act=set'>";



if ($dostsadm==1){
echo"Ник админа:<br/>
     <input name='nadm' maxlength='50' value='".$nickadmina."'/><br/>";
 echo"Ник 2-го админа:<br/>
     <input name='nadm2' maxlength='50' value='".$nickadmina2."'/><br/>";    	
 
echo"е-mail админа:<br/>
     <input name='madm' maxlength='50' value='".$emailadmina."'/><br/>";}else{	
echo"<input name='nadm' type='hidden' value='".$nickadmina."'/>
     <input name='nadm2' type='hidden' value='".$nickadmina2."'/>
     <input name='madm' type='hidden' value='".$emailadmina."'/>";}

foreach($setdata as $key=>$value){
echo "$setdata[$key]<br/><input type='text' name='".$key."' value='".$set[$key]."'/><br/>";}





echo"Включить gzip сжатие:<br/>Да";
	if($gzip=="1"){echo"<input name='gz' type='radio' value='1' checked='checked'/>";} else {echo"<input name='gz' type='radio' value='1' />";} 	echo" &nbsp; &nbsp; ";
	if($gzip=="0"){echo"<input name='gz' type='radio' value='0' checked='checked' />";} else {echo"<input name='gz' type='radio' value='0'/>";}
	echo"Нет<br/>";
echo"Включить подтверждение регистрации:<br/>Да";
	if($rmod=="1"){echo"<input name='rm' type='radio' value='1' checked='checked'/>";} else {echo"<input name='rm' type='radio' value='1' />";} 	echo" &nbsp; &nbsp; ";
	if($rmod=="0"){echo"<input name='rm' type='radio' value='0' checked='checked' />";} else {echo"<input name='rm' type='radio' value='0'/>";}
	echo"Нет<br/>";
echo"Включить премодерацию форума:<br/>Да";
	if($fmod=="1"){echo"<input name='fm' type='radio' value='1' checked='checked'/>";} else {echo"<input name='fm' type='radio' value='1' />";} 	echo" &nbsp; &nbsp; ";
	if($fmod=="0"){echo"<input name='fm' type='radio' value='0' checked='checked' />";} else {echo"<input name='fm' type='radio' value='0'/>";}
	echo"Нет<br/>";
echo"Открыть гостевую для добавления постов гостями:<br/>Да";
	if($gb=="1"){echo"<input name='gbk' type='radio' value='1' checked='checked'/>";} else {echo"<input name='gbk' type='radio' value='1' />";} 	echo" &nbsp; &nbsp; ";
	if($gb=="0"){echo"<input name='gbk' type='radio' value='0' checked='checked' />";} else {echo"<input name='gbk' type='radio' value='0'/>";}
	echo"Нет<br/>";
	
echo"<br/><input value='Ok!' type='submit'/></form>";
echo "<a href='main.php'>В админку</a><br/>";
break;}}else{header ("Location: ../index.php?err");}
include ("../incfiles/end.php");

?>