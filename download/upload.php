<?php


define('_IN_PUSTO', 1);
session_name('SESID');
session_start();
$headmod='upload';
$textl='Обменник';
require("../incfiles/db.php");
require("../incfiles/func.php");
require("../incfiles/data.php");
require("../incfiles/head.php");
require("../incfiles/inc.php");
require("../incfiles/mp3.php");
include('../incfiles/pclzip.php');
include('../incfiles/char.php');
$filesroot="../download";
$screenroot="$filesroot/screen";
$loadroot="$filesroot/upl";


###########################

if (!empty($_GET['act'])){$act=check($_GET['act']);}
switch ($act){

########################
case "rat":
if ($_GET['id']==""){
echo "Ошибка<br/><a href='upload.php?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(trim($_GET['id']));
$typ = mysql_query("select * from `upload` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="file"){
echo "Ошибка<br/><a href='upload.php?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
if ($_SESSION['rat']==$id){
echo "Вы уже оценивали этот файл!<br/><a href='upload.php?act=view&amp;file=".$id."'>К файлу</a><br/>";
require ('../incfiles/end.php');exit;}
$rat=intval(check($_POST['rat']));
if (!empty($ms[soft])){
$rt=explode(",",$ms[soft]);
$rt1=$rt[0]+$rat;$rt2=$rt[1]+1;
$rat1="$rt1,$rt2";}else{
$rat1="$rat,1";}
$_SESSION['rat']=$id;
mysql_query("update `upload` set soft = '".$rat1."' where id = '".$id."';");
echo "Спасибо, Ваша оценка принята!<br/><a href='upload.php?act=view&amp;file=".$id."'>К файлу</a><br/>";
break;


#########################
case "delmes":
if ($dostdmod==1){
if ($_GET['id']==""){
echo "Ошибка<br/><a href='upload.php?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(trim($_GET['id']));
$typ = mysql_query("select * from `upload` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="komm"){
echo "Ошибка<br/><a href='upload.php?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
mysql_query("delete from `upload` where `id`='".$id."';");
header ("location: upload.php?act=komm&id=$ms[refid]");}else{ echo "Нет доступа!<br/><a href='upload.php?'>К категориям</a><br/>";}



break;
########################
case "screendel":
if ($dostdmod==1){
if ($_GET['file']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$file=intval(trim($_GET['file']));
$file1=mysql_query("select * from `upload` where type = 'file' and id = '".$file."';");
$file2 = mysql_num_rows($file1);
$adrfile=mysql_fetch_array($file1);
if(($file1==0)||(!is_file("$adrfile[adres]/$adrfile[name]"))){
echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
unlink("$screenroot/$adrfile[screen]");
mysql_query("update `upload` set screen='' where id='".$file."';");
echo "Скриншот удалён!<br/>";}else{ echo "Нет доступа!<br/>";}
echo "<a href='?act=view&amp;file=".$file."'>К файлу</a><br/>";
break;
###################
case "search":
if (!empty($_GET['srh'])){
$srh=check(trim($_GET['srh']));}else{
if ($_POST['srh']==""){
echo "Вы не ввели условие поиска!<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$srh=check(trim($_POST['srh']));}
if (!empty($_GET['srh'])){
$srh=check(trim($_GET['srh']));}
$psk=mysql_query("select * from `upload` where  type='file' and moder='1';");

while ($array=mysql_fetch_array($psk)){ 
if (stristr($array[name],$srh)){
$res[]="Найдено по названию:<br/><a href='?act=view&amp;file=".$array[id]."'>$array[name]</a><br/>";
}
if (stristr($array[text],$srh)){
$res[]="Найдено по описанию:<br/><a href='?act=view&amp;file=".$array[id]."'>$array[name]</a><br/>$array[text]<br/>";
}}
$g=count($res);
if ($g==0){echo "<br/>По вашему запросу ничего не найдено<br/>";}else{
echo "Результаты поиска<br/>";}
if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*10-10;
if ($g < $start + 10){ $end = $g; }
else {$end = $start + 10; }
for ($i = $start; $i < $end; $i++){ 
$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='c'>";}else{$div="<div class='b'>";}
echo "$div $res[$i]</div>";}

###
if ($g>10){
echo "<hr/>";



$ba=ceil($g/10);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}
$asd=$start-10;
$asd2=$start+20;

if ($start != 0) {echo '<a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($page-1).'">&lt;&lt;</a> ';}
if ($offpg!=1){
if($asd<$g && $asd>0){echo ' <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page=1&amp;">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.$paa.'">'.$paa.'</a> <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.$pa.'">'.$pa.'</a> <a href="?id='.$id.'&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$g && $i>=0){
$ii=floor(1+$i/10);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+10;}
if ($page2>12){
echo ' .. <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.$paa2.'">'.$paa2.'</a> <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($paa3).'">'.($paa3).'</a> <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.$pa2.'">'.$pa2.'</a> <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$g){echo ' .. <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.$ba.'">'.$ba.'</a>';}
}else{
echo "<b>[$page]</b>";}
if ($g > $start + 10) {echo ' <a href="upload.php?act=search&amp;srh='.$srh.'&amp;page='.($page+1).'">&gt;&gt;</a>';}
echo "<form action='upload.php'>Перейти к странице:<br/><input type='hidden' name='act' value='search'/><input type='hidden' name='srh' value='".$srh."'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}
##########

if ($g!=0){echo "<br/>Найдено совпадений: $g";}
echo'<br/><a href="?">К категориям</a><br/>'; 
break;





##########################
case "addkomm":
if (!empty($_SESSION['pid'])){
if ($_GET['id']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(check(trim($_GET['id'])));
if (isset($_POST['submit'])){
$flt=$realtime-30;
$af = mysql_query("select * from `upload` where type='komm' and time>'".$flt."' and avtor= '".$login."';");
$af1=mysql_num_rows($af);
if ($af1!=0){
echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='upload.php?act=komm&amp;id=".$id."'>К комментариям</a><br/>";
require ("../incfiles/end.php");exit;}
if ($_POST['msg']==""){
echo "Вы не ввели сообщение!<br/><a href='upload.php?act=komm&amp;id=".$id."'>К комментариям</a><br/>";
require ('../incfiles/end.php');exit;}
$msg = check(trim($_POST['msg']));
if ($_POST[msgtrans]==1){
$msg=trans($msg);}
$msg=utfwin($msg);
$msg=substr($msg,0,500);
$msg=winutf($msg);
$agn=strtok($agn,' ');
mysql_query("insert into `upload` values(0,'".$id."','','".$realtime."','','komm','".$login."','".$ipp."','".$agn."','".$msg."','','');");

if(empty($datauser[komm])){$fpst=1;}else{
$fpst=$datauser[komm]+1;}
mysql_query("update `users` set  komm='".$fpst."' where id='".intval($_SESSION['pid'])."';");

header ("Location: upload.php?act=komm&id=$id");
}else{
echo "Напишите комментарий<br/><br/><form action='?act=addkomm&amp;id=".$id."' method='post'>
Cообщение(max. 500)<br/>
<textarea rows='3' name='msg'></textarea><br/><br/>
<input type='checkbox' name='msgtrans' value='1' /> Транслит<br/>
<input type='submit' name='submit' value='добавить' />  
  </form><br/>";
 echo "<a href='upload.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";  
	}}else{
echo "Вы не авторизованы!<br/>";}
echo'<br/><br/><a href="?act=komm&amp;id='.$id.'">К комментариям</a><br/><a href="?act=view&amp;file='.$id.'">К файлу</a><br/>'; break;


################################
case "trans":
include("../pages/trans.$ras_pages");
echo'<br/><br/><a href="'.htmlspecialchars(getenv("HTTP_REFERER")).'">Назад</a><br/>';
break;
############################
case "komm":
if ($_GET['id']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(check(trim($_GET['id'])));
$mess = mysql_query("select * from `upload` where type='komm' and refid='".$id."' order by time desc ;");
$countm = mysql_num_rows($mess);
$fayl = mysql_query("select * from `upload` where type='file' and id='".$id."';");
$fayl1=mysql_fetch_array($fayl);
echo "Комментируем файл <font color='".$clink."'>$fayl1[name]</font><br/>";
if (!empty($_SESSION['pid'])){
echo "<a href='?act=addkomm&amp;id=".$id."'>Написать</a><br/>";}
if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*$kmess-$kmess;
if ($countm < $start + $kmess){ $end = $countm; }
else {$end = $start + $kmess; }

while($mass = mysql_fetch_array($mess)) {
if($i>=$start&&$i < $end){ 
	$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='c'>";}else{$div="<div class='b'>";}
$uz= @mysql_query("select * from `users` where name='".check($mass[avtor])."';");
$mass1 = @mysql_fetch_array($uz);
echo "$div";
if ((!empty($_SESSION['pid']))&&($_SESSION['pid']!=$mass1[id])){
echo "<a href='anketa.php?user=".$mass1[id]."'>$mass[avtor]</a>";}else{
echo "$mass[avtor]";}
$vr=$mass[time]+$sdvig*3600;
$vr1=date("d.m.Y / H:i",$vr);
switch ($mass1[rights]){
case 7 :
echo ' Adm ';
break;
case 6 :
echo ' Smd ';
break;
case 4 :
echo ' Mod ';
break;
case 1 :
echo ' Kil ';
break;}
$ontime=$mass1[lastdate];
$ontime2=$ontime+300;
if ($realtime>$ontime2){echo" [Off]";}else{echo" [ON]";}
echo "($vr1)<br/>";
$mass[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $mass[text]);
$mass[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $mass[text]);
$mass[text]=eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $mass[text]);

if (stristr($mass[text],"<a href=")){
$mass[text]=eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)</a>", "<a href='\\1\\3'>\\3</a>" ,$mass[text]);}else{
$mass[text]=eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)", "<a href='\\1\\3'>\\3</a>" ,$mass[text]);}
if ($offsm!=1&&$offgr!=1){
$tekst=smiles($mass[text]);
$tekst=smilescat($tekst);

if ($mass[from]==nickadmina || $mass[from]==nickadmina2 || $mass1[rights]>=1){
$tekst=smilesadm($tekst);}}else{$tekst=$mass[text];}
echo "$tekst<br/>";
if ($dostdmod==1){
echo "$mass[ip] - $mass[soft]<br/><a href='upload.php?act=delmes&amp;id=".$mass[id]."'>(Удалить)</a><br/>";}
echo "</div>";}
 ++$i;}
#######
if ($countm>$kmess){
echo "<hr/>";



$ba=ceil($countm/$kmess);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}
$asd=$start-($kmess);
$asd2=$start+($kmess*2);

if ($start != 0) {echo '<a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.($page-1).'">&lt;&lt;</a> ';}
if ($offpg!=1){
if($asd<$countm && $asd>0){echo ' <a href="upload.php?act=komm&amp;id='.$id.'&amp;page=1&amp;">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.$paa.'">'.$paa.'</a> <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="?id='.$id.'&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="?id='.$id.'&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.$pa.'">'.$pa.'</a> <a href="?id='.$id.'&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$countm && $i>=0){
$ii=floor(1+$i/$kmess);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+$kmess;}
if ($page2>12){
echo ' .. <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.$paa2.'">'.$paa2.'</a> <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.($paa3).'">'.($paa3).'</a> <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.$pa2.'">'.$pa2.'</a> <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$countm){echo ' .. <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.$ba.'">'.$ba.'</a>';}}else{
echo "<b>[$page]</b>";}


if ($countm > $start + $kmess) {echo ' <a href="upload.php?act=komm&amp;id='.$id.'&amp;page='.($page+1).'">&gt;&gt;</a>';}
echo "<form action='upload.php'>Перейти к странице:<br/><input type='hidden' name='id' value='".$id."'/><input type='hidden' name='act' value='komm'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}

###########
echo "<br/>Всего комментариев: $countm";
echo'<br/><a href="?act=view&amp;file='.$id.'">К файлу</a><br/>'; 
break;

###########################
case "moder":
if ($dostdmod==1){
echo "<br/>Модерация файлов<br/>";
if ((!empty($_GET['id']))&&(isset($_GET['yes']))){
$id=intval(trim($_GET['id']));
mysql_query("update `upload` set moder='1' , time='".$realtime."' where id='".$id."';");
$fn=mysql_query("select * from `upload` where id='".$id."';");
$fn1=mysql_fetch_array($fn);
echo "Файл $fn1[name] добавлен в базу<br/>";}
if (isset($_GET['all'])){
$mod=mysql_query("select * from `upload` where type='file' and moder='0' ;");
while ($modadd=mysql_fetch_array($mod)){
mysql_query("update `upload` set moder='1' , time='".$realtime."' where id='".$modadd[id]."';");}
echo "Все файлы добавлены в базу<br/>";}

$md=mysql_query("select * from `upload` where type='file' and moder='0' ;");
$md1 = mysql_num_rows($md);
if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*10-10;
if ($md1 < $start + 10){ $end = $md1; }
else {$end = $start + 10; }

if ($md1!=0){
while ($md2=mysql_fetch_array($md)){
if($i>=$start&&$i < $end){ 
	$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='c'>";}else{$div="<div class='b'>";}
$fsz=filesize("$md2[adres]/$md2[name]");
$fsz= round($fsz/1024,2);
$ft=format("$md2[adres]/$md2[name]");
switch ($ft){
case "mp3":
$imt="mp3.png";
break;
case "zip":
$imt="rar.png";
break;
case "jar":
$imt="jar.png";
break;
case "gif":
$imt="gif.png";
break;
case "jpg":
$imt="jpg.png";
break;
case "png":
$imt="png.png";
break;
default :
$imt="file.gif";
break;}

echo "$div<img src='".$filesroot."/img/".$imt."' alt=''/><a href='?act=view&amp;file=".$md2[id]."'>$md2[name]</a> ($fsz кб) <br/>";
$nadir=$md2[refid];
$pat="";
while($nadir!=""){
$dnew=mysql_query("select * from `upload` where type = 'cat' and id = '".$nadir."';");
$dnew1=mysql_fetch_array($dnew);
$pat="$dnew1[text]/$pat";
$nadir=$dnew1[refid];}
$l=strlen($pat);
$pat1=substr($pat,0,$l-1);
echo "<a href='?act=moder&amp;id=".$md2[id]."&amp;yes'> Принять</a></div>";
}
 ++$i;}
############
if ($md1>10){
echo "<hr/>";



$ba=ceil($md1/10);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}
$asd=$start-(10);
$asd2=$start+(10*2);

if ($start != 0) {echo '<a href="upload.php?act=moder&amp;page='.($page-1).'">&lt;&lt;</a> ';}
if ($offpg!=1){
if($asd<$md1 && $asd>0){echo ' <a href="upload.php?act=moder&amp;page=1&amp;">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="upload.php?act=moder&amp;page='.$paa.'">'.$paa.'</a> <a href="upload.php?act=moder&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="upload.php?act=moder&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="upload.php?act=moder&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="upload.php?act=moder&amp;page='.$pa.'">'.$pa.'</a> <a href="upload.php?act=moder&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$md1 && $i>=0){
$ii=floor(1+$i/10);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="upload.php?act=moder&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+10;}
if ($page2>12){
echo ' .. <a href="upload.php?act=moder&amp;page='.$paa2.'">'.$paa2.'</a> <a href="upload.php?act=moder&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="upload.php?act=moder&amp;page='.($paa3).'">'.($paa3).'</a> <a href="upload.php?act=moder&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="upload.php?act=moder&amp;page='.$pa2.'">'.$pa2.'</a> <a href="upload.php?act=moder&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$md1){echo ' .. <a href="upload.php?act=moder&amp;page='.$ba.'">'.$ba.'</a>';}}else{
echo "<b>[$page]</b>";}


if ($md1 > $start + 10) {echo ' <a href="upload.php?act=moder&amp;page='.($page+1).'">&gt;&gt;</a>';}
echo "<form action='upload.php'>Перейти к странице:<br/><input type='hidden' name='act' value='moder'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}


if ($md1>=1){
echo "<br/>Всего: $md1";} 
echo "<br/><a href='?act=moder&amp;all'>Принять все!</a><br/>";


}}else{ echo "Нет доступа!<br/>";}
echo "<a href='?'>К категориям</a><br/>";
break;

########################
case "new":
echo "Новые файлы<br/>";
$old=$realtime-(3*24*3600);

$newfile=mysql_query("select * from `upload` where time > '".$old."' and type='file' and moder='1' order by time desc;");
$totalnew = mysql_num_rows($newfile);
if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*10-10;
if ($totalnew < $start + 10){ $end = $totalnew; }
else {$end = $start + 10; }

if ($totalnew!=0){
while ($newf=mysql_fetch_array($newfile)){
if($i>=$start&&$i < $end){ 
	$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='c'>";}else{$div="<div class='b'>";}
$fsz=filesize("$newf[adres]/$newf[name]");
$fsz= round($fsz/1024,2);
$ft=format("$newf[adres]/$newf[name]");
switch ($ft){
case "mp3":
$imt="mp3.png";
break;
case "zip":
$imt="rar.png";
break;
case "jar":
$imt="jar.png";
break;
case "gif":
$imt="gif.png";
break;
case "jpg":
$imt="jpg.png";
break;
case "png":
$imt="png.png";
break;
default :
$imt="file.gif";
break;}
if ($newf[text]!=""){
$tx=$newf[text];
$tx=utfwin($tx);
if (strlen($tx)>100){
$tx=substr($tx,0,90);

$tx="<br/>$tx...";}else{$tx="<br/>$tx";}
$tx=winutf($tx);
}else{$tx="";}
echo "$div<img src='".$filesroot."/img/".$imt."' alt=''/><a href='?act=view&amp;file=".$newf[id]."'>$newf[name]</a> ($fsz кб)$tx <br/>";
$nadir=$newf[refid];
$pat="";
while($nadir!=""){
$dnew=mysql_query("select * from `upload` where type = 'cat' and id = '".$nadir."';");
$dnew1=mysql_fetch_array($dnew);
$pat="$dnew1[text]/$pat";
$nadir=$dnew1[refid];}
$l=strlen($pat);
$pat1=substr($pat,0,$l-1);
echo "[$pat1]</div>";
}
 ++$i;

}
###
if ($totalnew>10){
echo "<hr/>";



$ba=ceil($totalnew/10);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}

if ($start != 0) {echo '<a href="upload.php?act=new&amp;page='.($page - 1).'">&lt;&lt;</a> ';}

$asd=$start-10;
$asd2=$start+20;
if ($offpg!=1){
if($asd<$totalnew && $asd>0){echo ' <a href="upload.php?act=new&amp;page=1">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="upload.php?act=new&amp;page='.$paa.'">'.$paa.'</a> <a href="upload.php?act=new&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="upload.php?act=new&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="upload.php?act=new&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="upload.php?act=new&amp;page='.$pa.'">'.$pa.'</a> <a href="upload.php?act=new&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$totalnew && $i>=0){
$ii=floor(1+$i/10);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="upload.php?act=new&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+10;}
if ($page2>12){
echo ' .. <a href="upload.php?act=new&amp;page='.$paa2.'">'.$paa2.'</a> <a href="upload.php?act=new&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="upload.php?act=new&amp;page='.($paa3).'">'.($paa3).'</a> <a href="upload.php?act=new&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="upload.php?act=new&amp;page='.$pa2.'">'.$pa2.'</a> <a href="?act=new&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$totalnew){echo ' .. <a href="upload.php?act=new&amp;page='.$ba.'">'.$ba.'</a>';}
}else{
echo "<b>[$page]</b>";}
if ($totalnew > $start + 10) {echo ' <a href="upload.php?act=new&amp;page='.($page + 1).'">&gt;&gt;</a>';}
echo "<form action='upload.php'>Перейти к странице:<br/><input type='hidden' name='act' value='new'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}

#####


if ($totalnew>=1){
echo "<br/>Всего новых файлов за 3 дня: $totalnew";} 
}else{echo "За три дня новых файлов не было<br/>";}
echo "<br/><a href='upload.php?'>К категориям</a><br/>";

break;

#####################
case "zip":
$delarc=opendir("$filesroot/arctemp");
while ($zp=readdir($delarc)){
if ($zp!="." && $zp!=".." && $zp!="index.php"){
$mp[]=$zp;}}
closedir($delarc);
$totalmp = count($mp);
for ($imp = 0; $imp < $totalmp; $imp++){
$filtime[$imp]=filemtime ("$filesroot/arctemp/$mp[$imp]");
$tim=time();
$ftime1=$tim-300;
if ($filtime[$imp] < $ftime1){
unlink ("$filesroot/arctemp/$mp[$imp]");}}



if ($_GET['file']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$file=intval(trim($_GET['file']));
$file1=mysql_query("select * from `upload` where type = 'file' and id = '".$file."';");
$file2 = mysql_num_rows($file1);
$adrfile=mysql_fetch_array($file1);
if(($file1==0)||(!is_file("$adrfile[adres]/$adrfile[name]"))){
echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$zip=new PclZip("$adrfile[adres]/$adrfile[name]");

     if (($list = $zip->listContent()) == 0)
      {
        die("Ошибка: ".$zip->errorInfo(true));
      }
     

     for ($i=0; $i<sizeof($list); $i++)
      {
        for(reset($list[$i]);
         $key = key($list[$i]);
          next($list[$i])) 
{
     
   $zfilesize = strstr($listcontent,"--size"); 
   $zfilesize = ereg_replace("--size:","",$zfilesize);
   $zfilesize =@ereg_replace("$zfilesize","$zfilesize|",$zfilesize); 
$sizelist .="$zfilesize"; 
  
          	  
   $listcontent = "[$i]--$key:".$list[$i][$key]."";
   $zfile = strstr($listcontent,"--filename");
      $zfile =ereg_replace("--filename:","",$zfile);
             $zfile =@ereg_replace("$zfile","$zfile|",$zfile);
			$savelist .="$zfile";
   		
        
     }
}
$sizefiles2 = explode("|",$sizelist);
$sizelist2=array_sum($sizefiles2);
$obkb=round($sizelist2/1024,2);

$preview="$savelist";
$preview = explode("|",$preview);

$count = count($preview)-1;
echo "<b>$arch</b><br/>Всего файлов: $count<br/>Вес распакованного архива: $obkb кб<br/>Вы можете скачать отдельные файлы из этого архива<hr/>";
if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*10-10;
if ($count < $start + 10){ $end = $count; }
else {$end = $start + 10; }

for ($i = $start; $i < $end; $i++){
	
            $sizefiles = explode("|",$sizelist); 
            $selectfile = explode("|",$savelist);
            $path = $selectfile[$i];
$fname = ereg_replace(".*[\\/]","",$path);
$zdir = ereg_replace("[\\/]?[^\\/]*$","",$path);

$tfl=strtolower(format($fname));
$df=array("asp","aspx","shtml","htd","php","php3","php4","php5","phtml","htt","cfm","tpl","dtd","hta","pl","js","jsp");
if (in_array($tfl,$df)){
echo "$zdir/$fname";}else{


echo $zdir.'/<a href="'.$PHP_SELF.'?act=arc&amp;file='.$file.'&amp;f='.$i.'&amp;start='.$start.'">'.$fname.'</a>';}
   	     if($sizefiles[$i]!="0")
   	     {$sizekb=round($sizefiles[$i]/1024,2);
   	     echo " ($sizekb кб)";}

echo'<br/>';

}
###

if ($count>10){
echo "<hr/>";



$ba=ceil($count/10);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}

if ($start != 0) {echo '<a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($page - 1).'">&lt;&lt;</a> ';}

$asd=$start-10;
$asd2=$start+20;
if ($offpg!=1){
if($asd<$count && $asd>0){echo ' <a href="upload.php?act=zip&amp;file='.$file.'&amp;page=1">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.$paa.'">'.$paa.'</a> <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.$pa.'">'.$pa.'</a> <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$count && $i>=0){
$ii=floor(1+$i/10);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+10;}
if ($page2>12){
echo ' .. <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.$paa2.'">'.$paa2.'</a> <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($paa3).'">'.($paa3).'</a> <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.$pa2.'">'.$pa2.'</a> <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$count){echo ' .. <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.$ba.'">'.$ba.'</a>';}}else{
echo "<b>[$page]</b>";}
if ($count > $start + 10) {echo ' <a href="upload.php?act=zip&amp;file='.$file.'&amp;page='.($page + 1).'">&gt;&gt;</a>';}
echo "<form action='upload.php'>Перейти к странице:<br/><input type='hidden' name='act' value='zip'/><input type='hidden' name='file' value='".$file."'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}



echo'<br/><br/><a href="?act=view&amp;file='.$file.'">К файлу</a><br/>'; 
break;
#######################
case "arc":
if ($_GET['file']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
if ($_GET['f']==""){
echo "Не выбран файл из архива<br/><a href='?act=zip&amp;file=".$file."'>В архив</a><br/>";
require ('../incfiles/end.php');exit;}
$file=intval(trim($_GET['file']));
$file1=mysql_query("select * from `upload` where type = 'file' and id = '".$file."';");
$file2 = mysql_num_rows($file1);
$adrfile=mysql_fetch_array($file1);
if(($file1==0)||(!is_file("$adrfile[adres]/$adrfile[name]"))){
echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$zip=new PclZip("$adrfile[adres]/$adrfile[name]");

     if (($list = $zip->listContent()) == 0)
      {
        die("Ошибка: ".$zip->errorInfo(true));
      }
     

     for ($i=0; $i<sizeof($list); $i++)
      {
        for(reset($list[$i]);
         $key = key($list[$i]);
          next($list[$i])) 
{
     
   $zfilesize = strstr($listcontent,"--size"); 
   $zfilesize = ereg_replace("--size:","",$zfilesize);
   $zfilesize =@ereg_replace("$zfilesize","$zfilesize|",$zfilesize); 
$sizelist .="$zfilesize"; 
  
          	  
   $listcontent = "[$i]--$key:".$list[$i][$key]."";
   $zfile = strstr($listcontent,"--filename");
      $zfile =ereg_replace("--filename:","",$zfile);
             $zfile =@ereg_replace("$zfile","$zfile|",$zfile);
			$savelist .="$zfile";
   		
        
     }
}
$sizefiles2 = explode("|",$sizelist);
$sizelist2=array_sum($sizefiles2);
$obkb=round($sizelist2/1024,2);

$preview="$savelist";
$preview = explode("|",$preview);
            $sizefiles = explode("|",$sizelist); 
            $selectfile = explode("|",$savelist);
$f=$_GET['f'];
            $path = $selectfile[$f];
$fname = ereg_replace(".*[\\/]","",$path);
$zdir = ereg_replace("[\\/]?[^\\/]*$","",$path);

$tfl=strtolower(format($fname));
$df=array("asp","aspx","shtml","htd","php","php3","php4","php5","phtml","htt","cfm","tpl","dtd","hta","pl","js" , "jsp");
if (!in_array($tfl,$df)){
$content = $zip->extract(PCLZIP_OPT_BY_NAME, $path,
                         PCLZIP_OPT_EXTRACT_AS_STRING);
    	 	 $content1 = $zip->extract(PCLZIP_OPT_BY_NAME, $open,
                         PCLZIP_OPT_EXTRACT_IN_OUTPUT);



$content = $content[0]['content'];
$FileName = "$filesroot/arctemp/$fname";	 
$fid = @fopen($FileName, "wb");
if($fid){
		if(flock($fid, LOCK_EX)){
			fwrite($fid, $content);
			flock($fid, LOCK_UN);
		}
		fclose($fid);
	}
if (is_file("$filesroot/arctemp/$fname")){
header ("location: $filesroot/arctemp/$fname");}}
break;
###################
case"down":
if (empty($_SESSION['upl'])){
header("location: upload.php");exit;}
$id=intval($_GET['id']);
$fil = mysql_query("select * from `upload` where id='$id';");
$mas = mysql_fetch_array($fil);	 

if (!empty($mas[name])){	 
if(file_exists("$mas[adres]/$mas[name]")){

$sc=$mas[ip]+1;

mysql_query("update `upload` set ip = '".$sc."' where id = '".$id."';");
$_SESSION['upl']="";
header("location: $mas[adres]/$mas[name]");}}
else{echo "Ошибка!<br/>&#187;<a href='?'>К категориям</a><br/>";}	
break;

#################
case "dfile":
if ($dostdmod==1){
if ($_GET['file']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$file=intval(trim($_GET['file']));
$file1=mysql_query("select * from `upload` where type = 'file' and id = '".$file."';");
$file2 = mysql_num_rows($file1);
$adrfile=mysql_fetch_array($file1);
if(($file1==0)||(!is_file("$adrfile[adres]/$adrfile[name]"))){
echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$refd=mysql_query("select * from `upload` where type = 'cat' and id = '".$adrfile[refid]."';");
$refd1=mysql_fetch_array($refd);
if (isset($_GET['yes'])){
unlink("$adrfile[adres]/$adrfile[name]");
mysql_query("delete from `upload` where id='".$adrfile[id]."' LIMIT 1;");
echo"Файл удалён<br/>";}else{echo "Вы уверены,что хотите удалить файл $adrfile[name]?<br/><a href='?act=dfile&amp;file=".$file."&amp;yes'>Да</a><br/>";}}else{ echo "Нет доступа!";}
echo "&#187;<a href='?cat=".$refd1[id]."'>В папку</a><br/>";
break;
####################
case "opis":
if ($dostdmod==1){
if ($_GET['file']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$file=intval(trim($_GET['file']));
$file1=mysql_query("select * from `upload` where type = 'file' and id = '".$file."';");
$file2 = mysql_num_rows($file1);
$adrfile=mysql_fetch_array($file1);
if(($file1==0)||(!is_file("$adrfile[adres]/$adrfile[name]"))){
echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}

if (isset($_POST['submit'])){
$newt=check(trim($_POST['newt']));





mysql_query("update `upload` set text='".$newt."' where id='".$file."';");
echo "Описание изменено <br/>";}else{
 echo"<form action='?act=opis&amp;file=".$file."' method='post'>";
echo"Описание: <br/><input type='text' name='newt' value='".$adrfile[text]."'/><br/>";

echo"<input type='submit' name='submit' value='Изменить'/></form><br/>";}}
else{ echo "Нет доступа!";}
echo "&#187;<a href='?act=view&amp;file=".$file."'>К файлу</a><br/>";
break;

##################
case "renf":
if ($dostdmod==1){
if ($_GET['file']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$file=intval(trim($_GET['file']));
$file1=mysql_query("select * from `upload` where type = 'file' and id = '".$file."';");
$file2 = mysql_num_rows($file1);
$adrfile=mysql_fetch_array($file1);
if(($file1==0)||(!is_file("$adrfile[adres]/$adrfile[name]"))){
echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$tf=format($adrfile[name]);
$stn=str_replace(".$tf","",$adrfile[name]);
if (isset($_POST['submit'])){
if (!empty($_POST['newf'])){$newf=check(trim($_POST['newf']));}else{
$newf=$stn;}
if(eregi("[^a-z0-9.()+_-]",$newf)){echo"В новом названии файла <b>$newn</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=renf&amp;file=".$file."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
$rn=rename("$adrfile[adres]/$adrfile[name]","$adrfile[adres]/$newf.$tf");
if($rn==true){
$ch="$newf.$tf";
echo "Файл переименован <br/>";
mysql_query("update `upload` set name='".$ch."' where id='".$file."';");}}else{

 echo"<form action='?act=renf&amp;file=".$file."' method='post'>";
echo"Название(без расширения): <br/><input type='text' name='newf' value='".$stn."'/><br/>";

echo"<input type='submit' name='submit' value='Изменить'/></form><br/>";}}
else{ echo "Нет доступа!";}
echo "&#187;<a href='?act=view&amp;file=".$file."'>К файлу</a><br/>";
break;
########
case "ren":
if ($dostdmod==1){
if (empty($_GET['cat'])){
echo"Ошибка!<br /><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}

$cat=intval(trim($_GET['cat']));
provupl($cat);
$cat1=mysql_query("select * from `upload` where type = 'cat' and id = '".$cat."';");
$adrdir=mysql_fetch_array($cat1);
$namedir="$adrdir[adres]/$adrdir[name]";
if (isset($_POST['submit'])){

if (!empty($_POST['newrus'])){
$newrus=check(trim($_POST['newrus']));}else{
$newrus="$adrdir[text]";}

if (mysql_query("update `upload` set text='".$newrus."' where id='".$cat."';")){echo "Название для отображения изменено<br/>";}}else{
 echo"<form action='?act=ren&amp;cat=".$cat."' method='post'>";
echo"Отображать каталог как: <br/><input type='text' name='newrus' value='".$adrdir[text]."'/><br/>";

echo"<input type='submit' name='submit' value='Изменить'/></form><br/>";}}
else{ echo "Нет доступа!";}
echo "&#187;<a href='?cat=".$cat."'>В папку</a><br/>";
break;

###############
case "typ":
if ($dostdmod==1){
if (empty($_GET['cat'])){
echo"Ошибка!<br /><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}

$cat=intval(trim($_GET['cat']));
provupl($cat);
$cat1=mysql_query("select * from `upload` where type = 'cat' and id = '".$cat."';");
$adrdir=mysql_fetch_array($cat1);
$namedir="$adrdir[adres]/$adrdir[name]";
if (isset($_POST['submit'])){
if (!empty($_POST['clos'])){
$clos=intval($_POST['clos']);}else{$clos=0;}
if (!empty($_POST['newtyp'])){
$newtyp=check(trim($_POST['newtyp']));}
if (mysql_query("update `upload` set soft='".$newtyp."',ip='".$clos."' where id='".$cat."';")){echo "Типы файлов назначены<br/>";}}else{
 echo"<form action='?act=typ&amp;cat=".$cat."' method='post'>";
if (empty($adrdir[soft])){
echo "На данный момент выгрузка файлов в эту директорию запрещена<br/>";}
echo"Типы файлов: <br/><input type='text' name='newtyp' value='".$adrdir[soft]."'/><br/>";
if ($adrdir[ip]==1){
echo "Разрешить выгрузку файлов<br/><input type='checkbox' name='clos' value='1' checked='checked' /><br/>";}else{
echo "Разрешить выгрузку файлов<br/><input type='checkbox' name='clos' value='1'/><br/>";}
echo"<input type='submit' name='submit' value='Изменить'/></form><br/>";}}
else{ echo "Нет доступа!";}
echo "&#187;<a href='?cat=".$cat."'>В папку</a><br/>";
break;

###############
case "cut":

$delmp3=opendir("$filesroot/mp3temp");
while ($muzd=readdir($delmp3)){
if ($muzd!="." && $muzd!=".." && $muzd!="index.php"){
$mp[]=$muzd;}}
closedir($delmp3);
$totalmp = count($mp);
for ($imp = 0; $imp < $totalmp; $imp++){
$filtime[$imp]=filemtime ("$filesroot/mp3temp/$mp[$imp]");
$tim=time();
$ftime1=$tim-300;
if ($filtime[$imp] < $ftime1){
unlink ("$filesroot/mp3temp/$mp[$imp]");}}



$rand=rand(1,999);
if (!empty($_POST['fid'])){$fid=intval($_POST['fid']);}
if (!empty($_GET['id'])){$id=intval($_GET['id']); 
$muz=mysql_query("select * from `upload` where type = 'file' and id = '".$id."';");
$muz1=mysql_fetch_array($muz);
$mp3="$muz1[adres]/$muz1[name]";
$mp3=str_replace("../","",$mp3);
$mp3="$home/$mp3";}
if(!isset($_POST['a'])||empty($_POST['a'])){
$_SESSION['rand']=$rand;
print "<form action='?act=cut' method='post'>
";
$id3 = new MP3_Id(); 
$result = $id3->read("$muz1[adres]/$muz1[name]"); 
$result = $id3->study(); 
if (!empty($mp3)){
echo $mp3;
echo "Нарезка файла <font color = '".$clink."'>$muz1[name]</font><br/><input type='hidden' name='url' value='".$mp3."'/>";}
else{
echo "Ссылка на MP3:<br/><input type='text' name='url' value='http://'/><br/>";
echo "<input type='submit' name='a' value='Инфо'/><br/>";}
if (!empty($mp3)&&$id3->getTag('bitrate')=="0"){
echo "Не удалось распознать кодек<br/>Нарезка только по размеру<br/>";}
echo "<input type='hidden' name='fid' value='".$id."'/>Способ нарезки:<br/>
<select name='way'>
<option value='size'>по размеру</option>";
if ($id3->getTag('bitrate')!=0){
echo "<option value='time'>по времени</option>";
}
echo "</select><br/>
Начать с (кб или сек.):<br/>
<input type='text' name='s'/><br/>
<input type='hidden' name='rnd' value='".$rand."'/>
Закончить по (кб или сек.):<br/>
<input type='text' name='p'/><br/>
<input type='submit' name='a' value='Резать'/>
</form>";

if (!empty($id)){echo "<a href='?act=view&amp;file=".$id."'>К файлу</a><br/>";}

}else{
$url=$_POST['url'];
$a=check(trim($_POST['a']));
$s=intval(trim($_POST['s']));
$p=intval(trim($_POST['p']));
$way=check(trim($_POST['way']));
$error = 0;
if(!eregi("^(http://)([a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z;]{2,3}))|(([0-9]{1,3}\.){3}([0-9]{1,3}))((/|\?)[a-z0-9~#%&'_\+=:;\?\.-])(.mp3)\$",$url))
{print "Это не MP3!<br/>"; $error = 1;}
if($a!="Инфо"){
if(!isset($s)||empty($s)){print "Вы не ввели число начала!<br/> "; $error = 2;}
if(!isset($p)||empty($p)){print "Вы не ввели число конца!<br/> "; $error = 2;}
if ($error == 2){
echo "<a href='?act=cut&amp;id=".$fid."'>Исправить!</a><br/>";}}
if($error==0){

$randint = rand(10000000,99999999);
$randintval = "$randint.mp3";
$randintval = "$filesroot/mp3temp/$randintval";
if(copy($url,$randintval)){
if($a=="Инфо"){
if (!empty($_POST['fid'])){$fid=intval($_POST['fid']);}
$id3 = new MP3_Id(); 
$result = $id3->read($randintval); 
$result = $id3->study(); 
print $id3->getTag('mode')."<br/>
<u>Размер:</u> ".round($id3->getTag('filesize')/1024)." Кб<br/>
<u>Битрейт:</u> ".$id3->getTag('bitrate')." кбит/сек<br/>
<u>Длительность:</u> ".$id3->getTag('length')."<br/>
<u>Частота дискретизации:</u> ".$id3->getTag('frequency')." Гц<br/>
<a href='?act=cut&amp;id=".$fid."'>Назад</a><br/>";}else{
$fp = fopen($randintval, "rb");
$raz = filesize($randintval);

if($way=="size"){
$s = $s*1024;
$p = $p*1024;
if($s>$raz||$s<0){$s = 0;}
if($p>$raz||$p<$s){$p = $raz;}}
else{
$id3 = new MP3_Id(); 
$result = $id3->read($randintval); 
$result = $id3->study(); 
$byterate = $id3->getTag('bitrate')/8;
$secbit = $raz/1024/$byterate;
if($s>$secbit||$s<0){$s = 0;}
if($p>$secbit||$p<$s){$p = $secbit;}
$s = $s*$byterate*1024;
$p = $p*$byterate*1024;}
$p = $p-$s;
fseek($fp, $s);
$filefp = fread($fp, $p);
fclose($fp);
unlink($randintval);
if(!empty($_SESSION['rand'])){
$fp = fopen($randintval, "xb");
if(!fwrite($fp, $filefp) === FALSE){
print "Файл успешно нарезан!<br/>Ссылка активна 5 минут<br/>
<a href='?act=mp3&amp;r=".$randint."'>Скачать</a><br/>";


echo "<a href='?act=cut'>Еще!</a><br/>";
unset($_SESSION['rand']);
}}else{print "Ошибка!<br/> <a href='?act=cut'>Назад</a><br/>";}
fclose($fp);
}}else{print "Не удалось считать файл! <a href='?act=cut'>Назад</a><br/>";}
}}
echo "<a href='?'>К категориям</a><br/>";
break;
######################
case "mp3":
$r=intval($_GET['r']);
	 
if(is_file("$filesroot/mp3temp/$r.mp3")){
header("location: $filesroot/mp3temp/$r.mp3");}
else{echo "Ошибка!<br/>&#187;<a href='?'>К категориям</a><br/>";}
break;
####################
case "preview":
if (isset($_POST['submit'])){
if (!empty($_POST['razmer'])){$razmer=intval($_POST['razmer']);}
$_SESSION['razm']=$razmer;
echo "На время текущей сессии <br/>принят максимальный размер изображений <br/>при просмотре $razmer*$razmer px<br/>";  
}else{
echo "<form action='?act=preview' method='post'>
	Выберите размеры просмотра картинок:<br/><select name='razmer'>";
if (!empty($_SESSION['razm'])){
$realr=$_SESSION['razm'];
echo "<option value='".$realr."'>".$realr."*".$realr."</option>";}
echo "<option value='32'>32*32</option>
<option value='50'>50*50</option>
<option value='64'>64*64</option>
<option value='80'>80*80</option>
<option value='100'>100*100</option>
<option value='120'>120*120</option>
<option value='160'>160*160</option>
<option value='200'>200*200</option>
	</select><br/>
<input type='submit' name='submit' value='ok'/></form>";}
echo "&#187;<a href='?'>К категориям</a><br/>";
break;
#################

case "refresh":
if ($dostdmod==1){
$dropen=opendir("$loadroot");
while(($file1 = readdir($dropen))){
if ($file1!="." && $file1!=".." && $file1!="index.php"){
$ob=mysql_query("select * from `upload` where type = 'cat' and refid = ''  ;");
while ($ob1=mysql_fetch_array($ob)){
$drt[]=$ob1[name];}

if (!in_array($file1,$drt)){
if (is_dir("$loadroot/$file1")){

mysql_query("insert into `upload` values(0,'','".$loadroot."','".$realtime."','".$file1."','cat','','','','".$file1."','','1');");}}}}




$obn=mysql_query("select * from `upload` where type = 'cat' ;");
while ($obn1=mysql_fetch_array($obn)){
$dirop="$obn1[adres]/$obn1[name]";
if (is_dir("$dirop")){
$diropen=opendir("$dirop");

while(($file = readdir($diropen))){
if ($file!="." && $file!=".." && $file!="index.php"){



$pap="$obn1[adres]/$obn1[name]";
$obn2=mysql_query("select * from `upload` where name = '".check(trim($file))."' and adres = '".$pap."' ;");

while ($obndir=mysql_fetch_array($obn2)){
$fod[]=$obndir[name];}
if (!in_array($file,$fod)){
if(is_dir("$dirop/$file")){

mysql_query("insert into `upload` values(0,'".$obn1[id]."','".$pap."','".$realtime."','".$file."','cat','','','','".$file."','','1');");}
if(is_file("$dirop/$file")){

mysql_query("insert into `upload` values(0,'".$obn1[id]."','".$pap."','".$realtime."','".$file."','file','".check(trim($login))."','','','','','0');");}}}}}



}
$dres=mysql_query("select * from `upload` where type = 'cat' and time = '".$realtime."' ;");
$totald = mysql_num_rows($dres);
$fres=mysql_query("select * from `upload` where type = 'file' and time = '".$realtime."' ;");
$totalf = mysql_num_rows($fres);

$deld=mysql_query("select * from `upload` where type = 'cat' ;");
$idd=0;
while($deld1=mysql_fetch_array($deld)){
if(!is_dir("$deld1[adres]/$deld1[name]")){
mysql_query("delete from `upload` where id='".$deld1[id]."' LIMIT 1;");$idd=$idd+1;}
}
$delf=mysql_query("select * from `upload` where type = 'file' ;");
$idf=0;
while($delf1=mysql_fetch_array($delf)){
if(!is_file("$delf1[adres]/$delf1[name]")){
mysql_query("delete from `upload` where id='".$delf1[id]."' LIMIT 1;");$idf=$idf+1;}
}


echo "База обновлена<br/>Добавлено $totald папок и $totalf файлов<br/>
Удалено $idd папок и $idf файлов<br/>";
if ($totald!=0 || $totalf!=0){
echo "<a href='?act=refresh'>Продолжить цикл</a><br/>";}}else{ echo "Нет доступа!<br/>";}
echo "&#187;<a href='?'>К категориям</a><br/>";
break;
################
case "delcat":
if (($dostdmod==1)&&(!empty($_GET['cat']))){$cat=$_GET['cat'];
$cat1=mysql_query("select * from `upload` where type = 'cat' and id = '".$cat."';");
$adrdir=mysql_fetch_array($cat1);
$cat2=mysql_query("select * from `upload` where type = 'cat' and id = '".$adrdir[refid]."';");
$adrdir2=mysql_fetch_array($cat2);
if (isset($_GET['yes'])){
$delcat=mysql_query("select * from `upload` where type = 'cat' and refid = '".$cat."';");
$delcat1=mysql_num_rows($delcat);
if ($delcat1==0){
provupl($cat);
deletcat("$adrdir[adres]/$adrdir[name]");
mysql_query("delete from `upload` where id='".$cat."' LIMIT 1;");
echo "Каталог удалён<br/><a href='?cat=".$adrdir2[id]."'>На уровень вверх</a><br/>";}
}else{echo "Вы уверены,что хотите удалить папку $adrdir[text]?<br/><a href='?act=delcat&amp;cat=".$cat."&amp;yes'>Да</a>|<a href='?cat=".$cat."'>Нет</a><br/>"; }
}else{ echo "Нет доступа!<br/>";}
break;




#######################
case "makdir":
if ($dostdmod==1){
if (!empty($_GET['cat'])){$cat=$_GET['cat'];}
if (isset($_POST['submit'])){
if (empty($cat)){$droot=$loadroot;}else{
$cat=intval(trim($cat));
provupl($cat);
$cat1=mysql_query("select * from `upload` where type = 'cat' and id = '".$cat."';");
$adrdir=mysql_fetch_array($cat1);
$droot="$adrdir[adres]/$adrdir[name]";}
$clos=intval(check(trim($_POST['clos'])));
$drn=check(trim($_POST['drn']));
$rusn=check(trim($_POST['rusn']));
$types=check(trim($_POST['types']));
$mk=mkdir("$droot/$drn",0777);
if($mk==true){
chmod("$droot/$drn", 0777);
echo "Папка создана<br/>";
mysql_query("insert into `upload` values(0,'".$cat."','".$droot."','".$realtime."','".$drn."','cat','','".$clos."','".$types."','".$rusn."','','1');");
$categ=mysql_query("select * from `upload` where type = 'cat' and name='$drn' and refid = '".$cat."';");
$newcat=mysql_fetch_array($categ);
echo "&#187;<a href='?cat=".$newcat[id]."'>В папку</a><br/>";
}else{
echo"Ошибка<br/>";}

}else{
echo"<form action='?act=makdir&amp;cat=".$_GET['cat']."' method='post'>
         Название папки:<br/>
         <input type='text' name='drn'/><br/>
         Название для отображения:<br/>
         <input type='text' name='rusn'/><br/>Разрешить выгрузку файлов<br/><input type='checkbox' name='clos' value='1'/><br/>
Для выгрузки файлов укажите их типы через запятую:<br/>
         <input type='text' name='types'/><br/>
         <input type='submit' name='submit' value='Создать'/><br/>
         </form>";}


}else{ echo "Нет доступа!<br/>";}
echo "&#187;<a href='?'>К категориям</a><br/>";
break;
#######################
case "select":
if (!empty($_GET['cat'])){
$cat=$_GET['cat'];
provupl($cat);
$tipy=mysql_query("select * from `upload` where id = '".$cat."';");
$tipy1=mysql_fetch_array($tipy);
$dostip=$tipy1[soft];
if ((empty($tipy1[ip]))||($tipy1[ip]==0)){
echo"В эту папку запрещена выгрузка файлов!<br/>&#187;<a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
if (!empty($_SESSION['pid'])){
echo"<br/>Разрешённые типы файлов для выгрузки в эту папку: <font color='orange'>$dostip</font><br/><form action='?act=upl' method='post' enctype='multipart/form-data'>
         Выберите файл:<br/>
         <input type='file' name='fail'/><br/>
         Скриншот:<br/>
         <input type='file' name='screens'/><hr/>
Для Opera Mini:<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a>
<br/><input name='screens1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать рисунок</a><hr/>
Описание:<br/>
       <textarea name='opis'></textarea><br/>
         
         
<input type='hidden' name='cat' value='".$cat."'/>
         <input type='submit' value='Загрузить'/><br/>
         </form>";}else{echo "Вы не авторизованы!<br/>";}
echo "&#187;<a href='?cat=".$cat."'>Вернуться</a><br/>";
}else{echo "Ошибка:не выбрана категория<br/>";} 
break;
#########################
case "upl":
if (!empty($_SESSION['pid'])){
if (empty($_POST['cat'])){
$loaddir=$loadroot;}else{
$cat=intval(trim($_POST['cat']));
provupl($cat);
$cat1=mysql_query("select * from `upload` where type = 'cat' and id = '".$cat."';");
$adrdir=mysql_fetch_array($cat1);
$loaddir="$adrdir[adres]/$adrdir[name]";}
$opis=check(trim($_POST['opis']));
$fname=$_FILES['fail']['name'];
$fsize=$_FILES['fail']['size'];
$scrname=$_FILES['screens']['name'];
$scrsize=$_FILES['screens']['size'];
$scsize = GetImageSize($_FILES['screens']['tmp_name']); 
$scwidth = $scsize[0]; 
$scheight = $scsize[1];
$ffail=strtolower($fname);
$tipy=mysql_query("select * from `upload` where id = '".$cat."';");
$tipy1=mysql_fetch_array($tipy);
$dostip=$tipy1[soft];
$dosff=explode(",",$dostip);
$ffot=strtolower($scrname);
$dopras = array("gif","jpg","png");
if ($fname!=""){
$formfail=format($ffail);
if (!in_array($formfail, $dosff)) {echo "Тип файла запрещён для выгрузку в эту папку.<br/><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if ($scrname!=""){
$formfot=format($ffot);
if (!in_array($formfot, $dopras)) {echo "Ошибка при загрузке скриншота.<br/><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}}
if($scwidth>320 || $scheight>320) {echo"Размер картинки не должен превышать разрешения 320*320 px<br/><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if(eregi("[^a-z0-9.()+_-]",$scrname)){echo"В названии изображения $scrname присутствуют недопустимые символы<br/><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;} 


if ($fsize >= 1024*$flsz){echo "Вес файла превышает $flsz кб<br/>
<a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if(eregi("[^a-z0-9.()+_-]",$fname)){echo"В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}

if((preg_match("/php/i",$fname)) or (preg_match("/.pl/i",$fname)) or ($fname==".htaccess")){
echo "Попытка отправить файл запрещенного типа.<br/><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if((move_uploaded_file($_FILES["screens"]["tmp_name"], "$screenroot/$fname.$formfot"))==true){
$ch1="$fname.$formfot";
@chmod("$ch1", 0777);
@chmod("$screenroot/$ch1", 0777);
	echo"Скриншот загружен!<br/>";}
      		if((move_uploaded_file($_FILES["fail"]["tmp_name"], "$loaddir/$fname"))==true){
$ch=$fname;
@chmod("$ch", 0777);
@chmod("$loaddir/$ch", 0777);
	echo"Спасибо,Ваш файл загружен!После прохождения модерации он будет добавлен в базу<br/>";	
mysql_query("insert into `upload` values(0,'".$cat."','".$loaddir."','".$realtime."','".$ch."','file','".check(trim($login))."','','','".$opis."','".$ch1."','0');");}else{echo"Ошибка при загрузке файла<br/>";}}


if (!empty($_POST['fail1'])){

$uploadedfile = $_POST['fail1'];
	if (strlen($uploadedfile)>0) {
       $array = explode('file=', $uploadedfile);
	   $tmp_name = $array[0];
	   $filebase64 = $array[1];
}
$ftip=format($tmp_name);
if (!empty($_POST['screens1'])){
$uploaddir1="$screenroot";
	$uploadedfile1 = $_POST['screens1'];
	if (strlen($uploadedfile1)>0) {
       $array1 = explode('file=', $uploadedfile1);
	   $tmp_name1 = $array1[0];
	   $filebas64 = $array1[1];
}
if(eregi("[^a-z0-9.()+_-]",$tmp_name1)){echo"В названии файла <b>$tmp_name1</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=select&amp;cat=".$cat."'>Повторить</a></div>";
require ('../incfiles/end.php');exit;}
if (strlen(base64_decode($filebase64)) >= 1024*$flsz){echo "Вес файла превышает $flsz кб<br/>
<a href='upload.php?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
$ffot=strtolower($tmp_name1);
$dopras = array("gif","jpg","png");

$formfot=format($ffot);
if (!in_array($formfot, $dopras)) {echo "Ошибка при загрузке скриншота.<br/><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if (strlen($filebas64)>0) {
	 $FileName1 = "$uploaddir/$tmp_name.$formfot";
	 $filedata1 = base64_decode($filebas64);
	 $fid1 = @fopen($FileName1, "wb");

	if($fid1){
		if(flock($fid1, LOCK_EX)){
			fwrite($fid1, $filedata1);
			flock($fid1, LOCK_UN);
		}
		fclose($fid1);
	}
    if (file_exists($FileName1) && filesize($FileName1) == strlen($filedata1)) {
$sizsf = GetImageSize("$FileName1"); 
$widthf = $sizsf[0]; 
$heightf = $sizsf[1];


if($widthf>320 || $heightf>320) {echo"Размер картинки не должен превышать разрешения 320*320 px<br/><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";unlink("$FileName1");
require ('../incfiles/end.php');exit;}
		echo 'Скриншот загружен!<br/>';

		$ch1="$tmp_name.$formfot";

	} else {
		echo 'Ошибка при загрузке скриншота<br/>';}
 	}}


##
$uploaddir="$loaddir";
	
if(eregi("[^a-z0-9.()+_-]",$tmp_name)){echo"В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}

if((preg_match("/php/i",$tmp_name)) or (preg_match("/.pl/i",$tmp_name)) or ($tmp_name==".htaccess")){
echo "Попытка отправить файл запрещенного типа.<br/><a href='?act=select&amp;cat=".$cat."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}

if (strlen($filebase64)>0) {
	 $FileName = "$uploaddir/$tmp_name";
	 $filedata = base64_decode($filebase64);
	 $fid = @fopen($FileName, "wb");

	if($fid){
		if(flock($fid, LOCK_EX)){
			fwrite($fid, $filedata);
			flock($fid, LOCK_UN);
		}
		fclose($fid);
	}
    if (file_exists($FileName) && filesize($FileName) == strlen($filedata)) {

		echo 'Спасибо,Ваш файл загружен!После прохождения модерации он будет добавлен в базу<br/>';

		$ch="$tmp_name";
mysql_query("insert into `upload` values(0,'".$cat."','".$loaddir."','".$realtime."','".$ch."','file','".check(trim($login))."','','','".$opis."','".$ch1."','0');");
	} else {
		echo 'Ошибка при загрузке файла<br/>';}
 	}}


}else{ echo "Вы не авторизованы!";}
echo "&#187;<a href='?cat=".$cat."'>В папку</a><br/>";	
break;
#################
case "view":
$delimag=opendir("$filesroot/graftemp");
while ($imd=readdir($delimag)){
if ($imd!="." && $imd!=".." && $imd!="index.php"){
$im[]=$imd;}}
closedir($delimag);
$totalim = count($im);
for ($imi = 0; $imi < $totalim; $imi++){
$filtime[$imi]=filemtime ("$filesroot/graftemp/$im[$imi]");
$tim=time();
$ftime1=$tim-10;
if ($filtime[$imi] < $ftime1){
unlink ("$filesroot/graftemp/$im[$imi]");}}


if ($_GET['file']==""){
echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$file=intval(trim($_GET['file']));
$file1=mysql_query("select * from `upload` where type = 'file' and id = '".$file."';");
$file2 = mysql_num_rows($file1);
$adrfile=mysql_fetch_array($file1);
if (($adrfile[moder]==1)||($dostdmod==1)){

if ($adrfile[moder]==0){
echo "Файл ожидает модерации<br/>";}


if(($file1==0)||(!is_file("$adrfile[adres]/$adrfile[name]"))){
echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
require ('../incfiles/end.php');exit;}
$_SESSION['upl']=rand(1000,9999);
$siz= filesize("$adrfile[adres]/$adrfile[name]"); 
$siz= round($siz/1024,2);
$filtime=filemtime ("$adrfile[adres]/$adrfile[name]");
$filtime=date("d.m.Y",$filtime);
echo "Файл: $adrfile[name]<br/>Вес:$siz кб<br/>Загружен:".date("d.m.Y",$adrfile[time])."<br/>Загрузил:$adrfile[avtor]<br/>" ;
$graf = array("gif","jpg","png");
$prg=strtolower(format($adrfile[name]));
if (in_array($prg,$graf)){
$sizsf = GetImageSize("$adrfile[adres]/$adrfile[name]"); 
$widthf = $sizsf[0]; 
$heightf = $sizsf[1];
echo "Размеры $widthf*$heightf px<br/>";
#  !предпросмотр!
$namefile=$adrfile[name];
$infile="$adrfile[adres]/$namefile";

if (!empty($_SESSION['razm'])){
$razm=$_SESSION['razm'];}else{$razm=50;}
$sizs = GetImageSize($infile); 
$width = $sizs[0]; 
$height = $sizs[1];
$quality=100;
$x_ratio = $razm / $width; 
$y_ratio = $razm / $height; 
if ( ($width <= $razm) && ($height <= $razm) ) { 
  $tn_width = $width; 
  $tn_height = $height; 
} 
else if (($x_ratio * $height) < $razm) { 
  $tn_height = ceil($x_ratio * $height); 
  $tn_width = $razm; 
} 
else { 
  $tn_width = ceil($y_ratio * $width); 
  $tn_height = $razm; 
}


switch($prg){ 
case "gif": $im = ImageCreateFromGIF( $infile ); break; 
case "jpg": $im = ImageCreateFromJPEG( $infile ); break; 
case "jpeg": $im = ImageCreateFromJPEG( $infile ); break; 
case "png": $im = ImageCreateFromPNG( $infile ); break; 
}

$im1=ImageCreateTrueColor($tn_width,$tn_height);


imagecopyresized($im1,$im,0,0,0,0,$tn_width,$tn_height,$width,$height);
$path="$filesroot/graftemp";
switch($prg){
case "gif":
$imagnam="$path/$namefile.temp.gif"; ImageGif($im1,$imagnam,$quality ); echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpg": 
$imagnam="$path/$namefile.temp.jpg"; imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpeg":
$imagnam="$path/$namefile.temp.jpg";imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";

 break;
case "png":
$imagnam="$path/$namefile.temp.png"; imagePng($im1,$imagnam,$quality); echo "<img src='".$imagnam."' alt=''/><br/>";

break; }

imagedestroy($im);
imagedestroy($im1);

}


if ($prg=="mp3"){
$id3 = new MP3_Id(); 
$result = $id3->read("$adrfile[adres]/$adrfile[name]"); 
$result = $id3->study(); 
 
echo "Каналы:".$id3->getTag('mode')."<br/>";
if ($id3->getTag('bitrate')!=0){
echo "Битрейт: ".$id3->getTag('bitrate')." кбит/сек<br/>
Длительность: ".$id3->getTag('length')."<br/>";}else{
echo "Не удалось распознать кодек<br/>";}
echo "Частота дискретизации: ".$id3->getTag('frequency')." Гц<br/>";}


if (empty($adrfile[text])){echo "Описание отсутствует<br/>";}else{
echo "Описание:<br/>$adrfile[text]<br/>";}
if (!empty($adrfile[ip])){
echo "Скачиваний: $adrfile[ip]<br/>";}

if (!empty($adrfile[soft])){
$rating=explode(",",$adrfile[soft]);

$rat=$rating[0]/$rating[1];
$rat=round($rat,2);
echo "Средний рейтинг: $rat<br/>Всего оценило: $rating[1] человек<br/>";}

echo "Оценить:<br/><form action='upload.php?act=rat&amp;id=".$file."' method='post'><select name='rat'>";
for ($i=10;$i>=1;--$i){
echo "<option>$i</option>";}
echo "</select><input type='submit' value='Ok!'/></form><br/>";



if((!in_array($prg,$graf))&&($prg!="mp3")){
if (empty($adrfile[screen])){echo "Скриншот отсутствует<br/>";}else{
echo "Скриншот<br/>";
$infile="$screenroot/$adrfile[screen]";

if (!empty($_SESSION['razm'])){
$razm=$_SESSION['razm'];}else{$razm=50;}
$sizs = GetImageSize($infile); 
$width = $sizs[0]; 
$height = $sizs[1];
$quality=100;

$angle=0;
$fontsiz=20;
$tekst=$copyright;
$x_ratio = $razm / $width; 
$y_ratio = $razm / $height; 
if ( ($width <= $razm) && ($height <= $razm) ) { 
  $tn_width = $width; 
  $tn_height = $height; 
} 
else if (($x_ratio * $height) < $razm) { 
  $tn_height = ceil($x_ratio * $height); 
  $tn_width = $razm; 
} 
else { 
  $tn_width = ceil($y_ratio * $width); 
  $tn_height = $razm; 
}
$format=format($infile);

switch($format){ 
case "gif": $im = ImageCreateFromGIF( $infile ); break; 
case "jpg": $im = ImageCreateFromJPEG( $infile ); break; 
case "jpeg": $im = ImageCreateFromJPEG( $infile ); break; 
case "png": $im = ImageCreateFromPNG( $infile ); break; 
}
$color=imagecolorallocate($im,55,255,255);
$fontdir=opendir("$filesroot/fonts");
while($ttf=readdir($fontdir)){
if ($ttf!="." && $ttf!=".." && $ttf!="index.php"){
$arr[]=$ttf;}}

$it=count($arr);
$ii=rand(0,$it-1);
$fontus="$filesroot/fonts/$arr[$ii]";
$font_size=ceil(($width+$height)/15);
imagettftext($im,$font_size,$angle,'10',$height-10,$color,$fontus,$tekst);

$im1=imagecreatetruecolor($tn_width,$tn_height);
$namefile="$adrfile[name]";

imagecopyresized($im1,$im,0,0,0,0,$tn_width,$tn_height,$width,$height);
$path="$filesroot/graftemp";
switch($format){
case "gif":
$imagnam="$path/$namefile.temp.gif"; ImageGif($im1,$imagnam,$quality ); echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpg": 
$imagnam="$path/$namefile.temp.jpg"; imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpeg":
$imagnam="$path/$namefile.temp.jpg";imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";

 break;
case "png":
$imagnam="$path/$namefile.temp.png"; imagePng($im1,$imagnam,$quality); echo "<img src='".$imagnam."' alt=''/><br/>";

break; }
imagedestroy($im);
imagedestroy($im1);

}}

if (($dostdmod==1)&&(!empty($_GET['file']))){
echo "<hr/>";
if((!in_array($prg,$graf))&&($prg!="mp3")){
if (!empty($adrfile[screen])){
echo "<a href='?act=screendel&amp;file=".$file."'>Удалить скриншот</a><br/>";}}

echo "<a href='?act=opis&amp;file=".$file."'>Описание</a><br/>";
echo "<a href='?act=renf&amp;file=".$file."'>Переименовать файл</a><br/>";
echo "<a href='?act=dfile&amp;file=".$file."'>Удалить файл</a><hr/>";}

$comm=mysql_query("select * from `upload` where type = 'komm' and refid = '$file';");
$totalkomm=mysql_num_rows($comm);
if ($prg=="mp3"){echo "<a href='?act=cut&amp;id=".$file."'>Нарезать</a><br/>";}
if ($prg=="zip"){echo "<a href='?act=zip&amp;file=".$file."'>Открыть архив</a><br/>";}
echo "<a href='?act=down&amp;id=".$file."'>Скачать</a><br/><a href='?act=komm&amp;id=".$file."'>Комментарии ($totalkomm)</a><br/>";}else{
echo "Ошибка!<br/>";}


$dnam=mysql_query("select * from `upload` where type = 'cat' and id = '".$adrfile[refid]."';");
$dnam1=mysql_fetch_array($dnam);
$dirname="$dnam1[text]";
$dirid="$dnam1[id]";

$nadir=$adrfile[refid];
while($nadir!=""){
echo "&#187;<a href='?cat=".$nadir."'>$dirname</a><br/>";
$dnamm=mysql_query("select * from `upload` where type = 'cat' and id = '".$nadir."';");
$dnamm1=mysql_fetch_array($dnamm);
$dnamm2=mysql_query("select * from `upload` where type = 'cat' and id = '".$dnamm1[refid]."';");
$dnamm3=mysql_fetch_array($dnamm2);
$nadir=$dnamm1[refid];
$dirname=$dnamm3[text];}
echo "&#187;<a href='?'>К категориям</a><br/>";
break;

###################
default:
if ($dostdmod==1){
$mod=mysql_query("select * from `upload` where type = 'file' and moder = '0';");
$mod1 = mysql_num_rows($mod);
if ($mod1>0){
echo"<br/>Модерации ожидают <a href='?act=moder'>$mod1</a> файлов<br/>";}}
if (empty($_GET['cat'])){
echo "Категории<br/>";
echo "<img src='".$filesroot."/img/new.gif' alt=''/><a href='?act=new'>Новые файлы</a><br/>";
$loaddir=$loadroot;}else{
$cat=intval(trim($_GET['cat']));
provupl($cat);
$cat1=mysql_query("select * from `upload` where type = 'cat' and id = '".$cat."';");
$adrdir=mysql_fetch_array($cat1);
$loaddir="$loadroot/$cat3[adres]";
echo "Категория:  <font color='".$clink."'>$adrdir[text]</font><br/>";
}

$zap=mysql_query("select * from `upload` where refid = '$cat' and moder='1' order by time desc ;");
$total = mysql_num_rows($zap);
$zapcat=mysql_query("select * from `upload` where refid = '$cat' and type='cat' ;");
$totalcat = mysql_num_rows($zapcat);
$zapfile=mysql_query("select * from `upload` where refid = '$cat' and type='file' and moder='1' ;");
$totalfile = mysql_num_rows($zapfile);


if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*10-10;
if ($total < $start + 10){ $end = $total; }
else {$end = $start + 10; }
if ($total!=0){
while($zap2 = mysql_fetch_array($zap)){

if($i>=$start&&$i < $end){
	$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='c'>";}else{$div="<div class='b'>";}
echo "$div";
switch($zap2[type]){
case "cat":
echo "<img src='".$filesroot."/img/dir.gif' alt=''/><a href='?cat=".$zap2[id]."'>$zap2[text]</a>";
$g=0;$g1=0;
$kf=mysql_query("select * from `upload` where type='file' and moder='1';");
while($kf1 = mysql_fetch_array($kf)){
if (stristr($kf1[adres],"$zap2[adres]/$zap2[name]")){
$g=$g+1;}}
$old=$realtime-(3*24*3600);
$kf2=mysql_query("select * from `upload` where time>'".$old."' and type='file' and moder='1';");
while($kf3 = mysql_fetch_array($kf2)){
if (stristr($kf3[adres],"$zap2[adres]/$zap2[name]")){
$g1=$g1+1;}}
echo "($g";
if ($g1!=0){echo "/+$g1)</div>";}else{echo ")</div>";}
break; 
case "file":
$ft=format($zap2[name]);
switch ($ft){
case "mp3":
$imt="mp3.png";
break;
case "zip":
$imt="rar.png";
break;
case "jar":
$imt="jar.png";
break;
case "gif":
$imt="gif.png";
break;
case "jpg":
$imt="jpg.png";
break;
case "png":
$imt="png.png";
break;
default :
$imt="file.gif";
break;}
if ($zap2[text]!=""){
$tx=$zap2[text];
$tx=utfwin($tx);
if (strlen($tx)>100){
$tx=substr($tx,0,90);

$tx="<br/>$tx...";}else{$tx="<br/>$tx";}
$tx=winutf($tx);
}else{$tx="";}
echo "<img src='".$filesroot."/img/".$imt."' alt=''/><a href='?act=view&amp;file=".$zap2[id]."'>$zap2[name]</a>$tx</div>";

break;}}

 ++$i;
}
###
if ($total>10){
echo "<hr/>";



$ba=ceil($total/10);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}

if ($start != 0) {echo '<a href="upload.php?cat='.$cat.'&amp;page='.($page - 1).'">&lt;&lt;</a> ';}

$asd=$start-10;
$asd2=$start+20;
if ($offpg!=1){
if($asd<$total && $asd>0){echo ' <a href="upload.php?cat='.$cat.'&amp;page=1">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="upload.php?cat='.$cat.'&amp;page='.$paa.'">'.$paa.'</a> <a href="upload.php?cat='.$cat.'&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="upload.php?cat='.$cat.'&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="upload.php?cat='.$cat.'&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="upload.php?cat='.$cat.'&amp;page='.$pa.'">'.$pa.'</a> <a href="upload.php?cat='.$cat.'&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$total && $i>=0){
$ii=floor(1+$i/10);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="upload.php?cat='.$cat.'&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+10;}
if ($page2>12){
echo ' .. <a href="upload.php?cat='.$cat.'&amp;page='.$paa2.'">'.$paa2.'</a> <a href="upload.php?cat='.$cat.'&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="upload.php?cat='.$cat.'&amp;page='.($paa3).'">'.($paa3).'</a> <a href="upload.php?cat='.$cat.'&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="upload.php?cat='.$cat.'&amp;page='.$pa2.'">'.$pa2.'</a> <a href="upload.php?cat='.$cat.'&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$totalnew){echo ' .. <a href="upload.php?cat='.$cat.'&amp;page='.$ba.'">'.$ba.'</a>';}
}else{
echo "<b>[$page]</b>";}

if ($total > $start + 10) {echo ' <a href="upload.php?cat='.$cat.'&amp;page='.($page + 1).'">&gt;&gt;</a>';}
echo "<form action='upload.php'>Перейти к странице:<br/><input type='hidden' name='cat' value='".$cat."'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}



echo "<br/>";
if ($totalcat>=1){
 echo 'Всего папок: '.$totalcat.'<br/>'; }
if ($totalfile>=1){
echo 'Всего файлов: '.$totalfile.'<br/>';} 
}else{echo'В данной категории нет файлов!<br/>';
}
if ((!empty($_SESSION['pid']))&&(!empty($_GET['cat']))){
if ((!empty($adrdir[soft]))&&($adrdir[ip]==1)){
echo "<a href='?act=select&amp;cat=".$cat."'>Выгрузить файл</a><br/>";}
if ((!empty($adrdir[soft]))&&($adrdir[ip]==0||$adrdir[ip]=="")){
echo "Выгрузка файлов в эту папку временно приостановлена<br/>";}
}
if ($dostdmod==1){
echo "<hr/><a href='?act=makdir&amp;cat=".$cat."'>Создать папку</a><br/>";}
if (($dostdmod==1)&&(!empty($_GET['cat']))){
$delcat=mysql_query("select * from `upload` where type = 'cat' and refid = '".$cat."';");
$delcat1=mysql_num_rows($delcat);
if ($delcat1==0){
echo "<a href='?act=delcat&amp;cat=".$cat."'>Удалить каталог</a><br/>";}
echo "<a href='?act=ren&amp;cat=".$cat."'>Переименовать каталог</a><br/>";
echo "<a href='?act=typ&amp;cat=".$cat."'>Типы файлов</a><br/>";
}
if ($dostdmod==1){
echo "<a href='?act=refresh'>Обновить</a><hr/>";}
if (!empty($cat)){
$dnam=mysql_query("select * from `upload` where type = 'cat' and id = '".$cat."';");
$dnam1=mysql_fetch_array($dnam);
$dnam2=mysql_query("select * from `upload` where type = 'cat' and id = '".$dnam1[refid]."';");
$dnam3=mysql_fetch_array($dnam2);
$dirname="$dnam3[text]";
$dirid="$dnam1[id]";

$nadir=$dnam1[refid];
while($nadir!=""){
echo "&#187;<a href='?cat=".$nadir."'>$dirname</a><br/>";
$dnamm=mysql_query("select * from `upload` where type = 'cat' and id = '".$nadir."';");
$dnamm1=mysql_fetch_array($dnamm);
$dnamm2=mysql_query("select * from `upload` where type = 'cat' and id = '".$dnamm1[refid]."';");
$dnamm3=mysql_fetch_array($dnamm2);
$nadir=$dnamm1[refid];
$dirname=$dnamm3[text];}
echo "&#187;<a href='?'>К категориям</a><br/>";
}

echo "<a href='?act=preview'>Размеры изображений</a><br/>";
echo "<a href='?act=cut'>Нарезка mp3</a><br/>";
if (empty($cat)){
echo"<form action='?act=search' method='post'>";
echo"Поиск файла: <br/><input type='text' name='srh' value=''/><br/>";

echo"<input type='submit' value='Найти!'/></form><br/>";}
break;
}


 require ('../incfiles/end.php'); 
?>