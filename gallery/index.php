<?php


define('_IN_PUSTO', 1);

$headmod='gallery';
$textl='Галерея сайта';
require("../incfiles/db.php");
require("../incfiles/func.php");
require("../incfiles/data.php");
require("../incfiles/head.php");
require("../incfiles/inc.php");
require("../incfiles/mp3.php");
include('../incfiles/pclzip.php');
include('../incfiles/char.php');

if (!empty($_GET['act'])){$act=check($_GET['act']);}
switch ($act){
################
case "new":
echo "Новые фотографии<br/>";
$old=$realtime-(3*24*3600);

$newfile=mysql_query("select * from `gallery` where time > '".$old."' and type='ft' order by time desc;");
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



echo "$div<br/>&nbsp;<a href='index.php?id=".$newf[id]."'>";

#########
$infile="foto/$newf[name]";

if (!empty($_SESSION['frazm'])){
$razm=$_SESSION['frazm'];}else{$razm=50;}
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
$format=format($infile);

switch($format){ 
case "gif": $im = ImageCreateFromGIF( $infile ); break; 
case "jpg": $im = ImageCreateFromJPEG( $infile ); break; 
case "jpeg": $im = ImageCreateFromJPEG( $infile ); break; 
case "png": $im = ImageCreateFromPNG( $infile ); break; 
}


$im1=imagecreatetruecolor($tn_width,$tn_height);
$namefile="$newf[name]";

imagecopyresized($im1,$im,0,0,0,0,$tn_width,$tn_height,$width,$height);

switch($format){
case "gif":
$imagnam="temp/$namefile.temp.gif"; ImageGif($im1,$imagnam,$quality ); echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpg": 
$imagnam="temp/$namefile.temp.jpg"; imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpeg":
$imagnam="temp/$namefile.temp.jpg";imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";

 break;
case "png":
$imagnam="temp/$namefile.temp.png"; imagePng($im1,$imagnam,$quality); echo "<img src='".$imagnam."' alt=''/><br/>";

break; }
imagedestroy($im);
imagedestroy($im1);



############
$vrf=$newf[time]+$sdvig*3600;
$vrf1=date("d.m.y / H:i",$vrf);
$kom = mysql_query("select * from `gallery` where type='km' and refid='".$newf[id]."';");
$kom1=mysql_num_rows($kom);
echo "</a><br/>Добавлено: $vrf1<br/>Подпись: $newf[text]<br/><a href='index.php?act=komm&amp;id=".$newf[id]."'>Комментарии</a> ($kom1)<br/>";

$al=mysql_query("select * from `gallery` where type = 'al' and id = '".$newf[refid]."';");
$al1=mysql_fetch_array($al);
$rz=mysql_query("select * from `gallery` where type = 'rz' and id = '".$al1[refid]."';");
$rz1=mysql_fetch_array($rz);
echo "$rz1[text]/[$al1[text]]</div>";
}
 ++$i;

}
###
if ($totalnew>10){
echo "<hr/>";



$ba=ceil($totalnew/10);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}

if ($start != 0) {echo '<a href="index.php?act=new&amp;page='.($page - 1).'">&lt;&lt;</a> ';}

$asd=$start-10;
$asd2=$start+20;
if ($offpg!=1){
if($asd<$totalnew && $asd>0){echo ' <a href="index.php?act=new&amp;page=1">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="index.php?act=new&amp;page='.$paa.'">'.$paa.'</a> <a href="index.php?act=new&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="index.php?act=new&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="index.php?act=new&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="index.php?act=new&amp;page='.$pa.'">'.$pa.'</a> <a href="index.php?act=new&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$totalnew && $i>=0){
$ii=floor(1+$i/10);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="index.php?act=new&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+10;}
if ($page2>12){
echo ' .. <a href="index.php?act=new&amp;page='.$paa2.'">'.$paa2.'</a> <a href="index.php?act=new&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="index.php?act=new&amp;page='.($paa3).'">'.($paa3).'</a> <a href="index.php?act=new&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="index.php?act=new&amp;page='.$pa2.'">'.$pa2.'</a> <a href="?act=new&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$totalnew){echo ' .. <a href="index.php?act=new&amp;page='.$ba.'">'.$ba.'</a>';}
}else{
echo "<b>[$page]</b>";}
if ($totalnew > $start + 10) {echo ' <a href="index.php?act=new&amp;page='.($page + 1).'">&gt;&gt;</a>';}
echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='new'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}

#####



echo "<br/>Всего новых фотографий за 3 дня: $totalnew";}else{ 
echo "<br/>Нет новых фотографий за 3 дня";}
echo "<br/><a href='index.php?'>В галерею</a><br/>";


break;


############
case "edf":

if ($dostsmod==1){
if ($_GET['id']==""){
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(trim($_GET['id']));
$typ = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="ft"){
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
if (isset($_POST['submit'])){
$text=check($_POST['text']);
mysql_query("update `gallery` set text='".$text."' where id='".$id."';");
header ("location: index.php?id=$ms[refid]");}else{
echo "Редактирование подписи<br/><form action='index.php?act=edf&amp;id=".$id."' method='post'><input type='text' name='text' value='".$ms[text]."'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=".$ms[refid]."'>Назад</a><br/>";}
}else{header ("location: index.php");}

break;



###################
case "delf":

if ($dostsmod==1){
if ($_GET['id']==""){
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(trim($_GET['id']));
$typ = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="ft"){
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
if (isset($_GET['yes'])){
$km = mysql_query("select * from `gallery` where type='km' and refid='".$id."';");
while ($km1=mysql_fetch_array($km)){
mysql_query("delete from `gallery` where `id`='".$km1[id]."';");}
unlink ("foto/$ms[name]");
mysql_query("delete from `gallery` where `id`='".$id."';");
header ("location: index.php?id=$ms[refid]");}else{
echo "Вы уверены?<br/>";
echo "<a href='index.php?act=delf&amp;id=".$id."&amp;yes'>Да</a> | <a href='index.php?id=".$ms[refid]."'>Нет</a><br/>";}}else{header ("location: index.php");}
break;
#########################
case "edit":
if ($dostsmod==1){
if ($_GET['id']==""){
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(trim($_GET['id']));
$typ = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($typ);

switch ($ms[type]){
case "al":
if (isset($_POST['submit'])){
$text=check($_POST['text']);
mysql_query("update `gallery` set text='".$text."' where id='".$id."';");
header ("location: index.php?id=$id");}else{
echo "Редактирование альбома<br/><form action='index.php?act=edit&amp;id=".$id."' method='post'><input type='text' name='text' value='".$ms[text]."'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=".$id."'>Назад</a><br/>";}
break;
case "rz":
if (isset($_POST['submit'])){
$text=check($_POST['text']);
if (!empty($_POST['user'])){
$user=intval(check($_POST['user']));}else{$user=0;}
mysql_query("update `gallery` set text='".$text."', user='".$user."' where id='".$id."';");
header ("location: index.php?id=$id");}else{
echo "Редактирование раздела<br/><form action='index.php?act=edit&amp;id=".$id."' method='post'><input type='text' name='text' value='".$ms[text]."'/><br/>";
if ($ms[user]==1){
echo "<input type='checkbox' name='user' value='1' checked='checked'/>Для альбомов юзеров<br/>";}else{
echo "<input type='checkbox' name='user' value='1'/>Для альбомов юзеров<br/>";}
echo "<input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=".$id."'>Назад</a><br/>";}
break;
default:
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;
break;}
}else{header ("location: index.php");}

break;
######################
case "del":
if ($dostsmod==1){
if ($_GET['id']==""){
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(trim($_GET['id']));
$typ = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($typ);

if (isset($_GET['yes'])){
switch ($ms[type]){
case "al":
$ft = mysql_query("select * from `gallery` where type='ft' and refid='".$id."';");
while ($ft1=mysql_fetch_array($ft)){
$km = mysql_query("select * from `gallery` where type='km' and refid='".$ft1[id]."';");
while ($km1=mysql_fetch_array($km)){
mysql_query("delete from `gallery` where `id`='".$km1[id]."';");}
unlink ("foto/$ft1[name]");
mysql_query("delete from `gallery` where `id`='".$ft1[id]."';");}
mysql_query("delete from `gallery` where `id`='".$id."';");
header ("location: index.php?id=$ms[refid]");
break;
case "rz":
$al = mysql_query("select * from `gallery` where type='al' and refid='".$id."';");
while ($al1=mysql_fetch_array($al)){
$ft = mysql_query("select * from `gallery` where type='ft' and refid='".$al1[id]."';");
while ($ft1=mysql_fetch_array($ft)){
$km = mysql_query("select * from `gallery` where type='km' and refid='".$ft1[id]."';");
while ($km1=mysql_fetch_array($km)){
mysql_query("delete from `gallery` where `id`='".$km1[id]."';");}
unlink ("foto/$ft1[name]");
mysql_query("delete from `gallery` where `id`='".$ft1[id]."';");}
mysql_query("delete from `gallery` where `id`='".$al1[id]."';");}
mysql_query("delete from `gallery` where `id`='".$id."';");
header ("location: index.php");
break;
default:
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;
break;}}else{

switch ($ms[type]){
case "al":
echo "Вы уверены в удалении альбома $ms[text]?<br/>";
break;
case "rz":
echo "Вы уверены в удалении раздела $ms[text]?<br/>";
break;
default:
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;
break;}
echo "<a href='index.php?act=del&amp;id=".$id."&amp;yes'>Да</a> | <a href='index.php?id=".$id."'>Нет</a><br/>";}}else{header ("location: index.php");}

break;





#########################
case "delmes":
if ($dostsmod==1){
if ($_GET['id']==""){
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(trim($_GET['id']));
$typ = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="km"){
echo "Ошибка<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
mysql_query("delete from `gallery` where `id`='".$id."';");
header ("location: index.php?act=komm&id=$ms[refid]");}else{ echo "Нет доступа!<br/><a href='index.php'>В галерею</a><br/>";}



break;
########################
case "addkomm":
if (!empty($_SESSION['pid'])){
if ($_GET['id']==""){
echo "Не выбрано фото<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(check(trim($_GET['id'])));
if (isset($_POST['submit'])){
$flt=$realtime-30;
$af = mysql_query("select * from `gallery` where type='km' and time>'".$flt."' and avtor= '".$login."';");
$af1=mysql_num_rows($af);
if ($af1!=0){
echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='index.php?act=komm&amp;id=".$id."'>К комментариям</a><br/>";
require ("../incfiles/end.php");exit;}
if ($_POST['msg']==""){
echo "Вы не ввели сообщение!<br/><a href='index.php?act=komm&amp;id=".$id."'>К комментариям</a><br/>";
require ('../incfiles/end.php');exit;}
$msg = check(trim($_POST['msg']));
if ($_POST[msgtrans]==1){
$msg=trans($msg);}
$msg=utfwin($msg);
$msg=substr($msg,0,500);
$msg=winutf($msg);
$agn=strtok($agn,' ');
mysql_query("insert into `gallery` values(0,'".$id."','".$realtime."','km','".$login."','".$msg."','','','".$ipp."','".$agn."');");
if(empty($datauser[komm])){$fpst=1;}else{
$fpst=$datauser[komm]+1;}
mysql_query("update `users` set  komm='".$fpst."' where id='".intval($_SESSION['pid'])."';");
header ("Location: index.php?act=komm&id=$id");
}else{
echo "Напишите комментарий(max.500)<br/><br/><form action='index.php?act=addkomm&amp;id=".$id."' method='post'>
Cообщение<br/>
<textarea rows='3' name='msg'></textarea><br/><br/>
<input type='checkbox' name='msgtrans' value='1' /> Транслит<br/>
<input type='submit' name='submit' value='добавить' />  
  </form><br/>";
 echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";  
	}}else{
echo "Вы не авторизованы!<br/>";}
echo'<br/><br/><a href="?act=komm&amp;id='.$id.'">К комментариям</a><br/><a href="index.php?id='.$id.'">К фото</a><br/>';
echo "<a href='index.php'>В галерею</a><br/>";
 break;


################################
case "trans":
include("../pages/trans.$ras_pages");
echo'<br/><br/><a href="'.htmlspecialchars(getenv("HTTP_REFERER")).'">Назад</a><br/>';break;
############################
case "komm":
if ($_GET['id']==""){

echo "Не выбрано фото<br/><a href='index.php'>В галерею</a><br/>";
require ('../incfiles/end.php');exit;}
$id=intval(check(trim($_GET['id'])));
$mess = mysql_query("select * from `gallery` where type='km' and refid='".$id."' order by time desc ;");
$countm = mysql_num_rows($mess);

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
echo "<a href='../str/anketa.php?user=".$mass1[id]."'>$mass[avtor]</a>";}else{
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
case 1 :
echo ' Kil ';
break;}
$ontime=$mass1[lastdate];
$ontime2=$ontime+300;
if ($realtime>$ontime2){echo" [Off]";}else{echo" [ON]";}
echo "($vr1)<br/>";
$mass[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $mass[text]);
$mass[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $mass[text]);
$mass[text]=eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $mass[text]);

if (stristr($mass[text],"<a href=")){
$mass[text]=eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>", "<a href='\\1\\3'>\\3</a>" ,$mass[text]);}else{
$mass[text]=eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>" ,$mass[text]);}
if ($offsm!=1&&$offgr!=1){
$tekst=smiles($mass[text]);
$tekst=smilescat($tekst);

if ($mass[from]==nickadmina || $mass[from]==nickadmina2 || $mass1[rights]>=1){
$tekst=smilesadm($tekst);}}else{$tekst=$mass[text];}
echo "$tekst<br/>";
if ($dostsmod==1){
echo "$mass[ip] - $mass[soft]<br/><a href='index.php?act=delmes&amp;id=".$mass[id]."'>(Удалить)</a><br/>";}
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

if ($start != 0) {echo '<a href="index.php?act=komm&amp;id='.$id.'&amp;page='.($page-1).'">&lt;&lt;</a> ';}
if ($offpg!=1){
if($asd<$countm && $asd>0){echo ' <a href="index.php?act=komm&amp;id='.$id.'&amp;page=1&amp;">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.$paa.'">'.$paa.'</a> <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="?id='.$id.'&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="?id='.$id.'&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.$pa.'">'.$pa.'</a> <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$countm && $i>=0){
$ii=floor(1+$i/$kmess);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+$kmess;}
if ($page2>12){
echo ' .. <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.$paa2.'">'.$paa2.'</a> <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.($paa3).'">'.($paa3).'</a> <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.$pa2.'">'.$pa2.'</a> <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$countm){echo ' .. <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.$ba.'">'.$ba.'</a>';}}else{
echo "<b>[$page]</b>";}


if ($countm > $start + $kmess) {echo ' <a href="index.php?act=komm&amp;id='.$id.'&amp;page='.($page+1).'">&gt;&gt;</a>';}
echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='".$id."'/><input type='hidden' name='act' value='komm'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}

###########
echo "<br/>Всего комментариев: $countm";
echo'<br/><a href="?id='.$id.'">К фото</a><br/>';
echo "<a href='index.php'>В галерею</a><br/>"; 
break;

######################
case "preview":
if (isset($_POST['submit'])){
if (!empty($_POST['razmer'])){$razmer=intval($_POST['razmer']);}
$_SESSION['frazm']=$razmer;
echo "На время текущей сессии <br/>принят максимальный размер изображений <br/>при просмотре $razmer*$razmer px<br/>";  
}else{
echo "<form action='index.php?act=preview' method='post'>
	Выберите размеры просмотра картинок:<br/><select name='razmer'>";
if (!empty($_SESSION['frazm'])){
$realr=$_SESSION['frazm'];
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
echo "<a href='index.php?'>В галерею</a><br/>";
break;

#####################
case "load":
if (empty($_SESSION['pid'])){header ("location: index.php");}
if (empty($_GET['id'])){
echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$type = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($type);
if ($ms[type]!="al"){
echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";require ("../incfiles/end.php");exit;}
$rz = mysql_query("select * from `gallery` where type='rz' and id='".$ms[refid]."';");
$rz1=mysql_fetch_array($rz);
if ((!empty($_SESSION['pid'])&&$rz1[user]==1&&$ms[text]==$login)||($dostsmod==1)){
$text=check($_POST['text']);
$dopras = array("gif","jpg","png");
$tff=implode(" ,",$dopras);
$ftsz=$flsz/5;
$fname=$_FILES['fail']['name'];
$fsize=$_FILES['fail']['size'];
if ($fname!=""){
$ffail=strtolower($fname);
$formfail=format($ffail);

if((preg_match("/php/i",$ffail)) or (preg_match("/.pl/i",$fname)) or ($fname==".htaccess")){
echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=upl&amp;id=".$id."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if ($fsize >= 1024*$ftsz){echo "Вес файла превышает $ftsz кб<br/>
<a href='index.php?act=upl&amp;id=".$id."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if (!in_array($formfail, $dopras)) {echo "Разрешены только следующие типы файлов: $tff !.<br/><a href='index.php?act=upl&amp;id=".$id."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if(eregi("[^a-z0-9.()+_-]",$fname)){echo"В названии изображения $fname присутствуют недопустимые символы<br/><a href='index.php?act=upl&amp;id=".$id."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if ($rz1[user]==1&&$ms[text]==$login){
$fname="$login.$fname";}
if (file_exists("foto/$fname")){
$fname="$realtime.$fname";}
if((move_uploaded_file($_FILES["fail"]["tmp_name"], "foto/$fname"))==true){
$ch=$fname;
@chmod("$ch", 0777);
@chmod("foto/$ch", 0777);
	echo"Фото загружено!<br/><a href='index.php?id=".$id."'>В альбом</a><br/>";
mysql_query("insert into `gallery` values(0,'".$id."','".$realtime."','ft','".$login."','".$text."','".$ch."','','','');");}else{echo "Ошибка при загрузке фото<br/><a href='index.php?id=".$id."'>В альбом</a><br/>";}}
if (!empty($_POST['fail1'])){


$uploadedfile = $_POST['fail1'];
if (strlen($uploadedfile)>0) {
$array = explode('file=', $uploadedfile);
$tmp_name = $array[0];
$filebase64 = $array[1];}
$ffail=strtolower($tmp_name);
$fftip=format($ffail);
if((preg_match("/php/i",$ffail)) or (preg_match("/.pl/i",$fname)) or ($fname==".htaccess")){
echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=upl&amp;id=".$id."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if (strlen(base64_decode($filebase64)) >= 1024*$ftsz){echo "Вес файла превышает $ftsz кб<br/>
<a href='index.php?act=upl&amp;id=".$id."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if (!in_array($fftip, $dopras)) {echo "Разрешены только следующие типы файлов: $tff !.<br/><a href='index.php?act=upl&amp;id=".$id."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}
if(eregi("[^a-z0-9.()+_-]",$tmp_name)){echo"В названии изображения $tmp_name присутствуют недопустимые символы<br/><a href='index.php?act=upl&amp;id=".$id."'>Повторить</a><br/>";
require ('../incfiles/end.php');exit;}

if (strlen($filebase64)>0) {
if ($rz1[user]==1&&$ms[text]==$login){
$tmp_name="$login.$tmp_name";}
if (file_exists("foto/$fname")){
$tmp_name="$realtime.$tmp_name";}

$FileName = "foto/$tmp_name";
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
echo "Фото загружено!<br/><a href='index.php?id=".$id."'>В альбом</a><br/>";
$ch="$tmp_name";
mysql_query("insert into `gallery` values(0,'".$id."','".$realtime."','ft','".$login."','".$text."','".$ch."','','','');");
} else {
echo "Ошибка при загрузке фото<br/><a href='index.php?id=".$id."'>В альбом</a><br/>";}
}}}else{header ("location: index.php");}
break;
##########################
case "upl":
if (empty($_SESSION['pid'])){header ("location: index.php");}
if (empty($_GET['id'])){
echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$type = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($type);
if ($ms[type]!="al"){
echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";require ("../incfiles/end.php");exit;}
$rz = mysql_query("select * from `gallery` where type='rz' and id='".$ms[refid]."';");
$rz1=mysql_fetch_array($rz);
if ((!empty($_SESSION['pid'])&&$rz1[user]==1&&$ms[text]==$login)||($dostsmod==1)){
$dopras = array("gif","jpg","png");
$tff=implode(" ,",$dopras);
$fotsize=$flsz/5;
echo "Добавление фотографии:<br/>Разрешённые типы: $tff<br/>Максимальный вес: $fotsize кб.<br/><form action='index.php?act=load&amp;id=".$id."' method='post' enctype='multipart/form-data'>Выберите фото:<br/><input type='file' name='fail'/><hr/>Для Opera Mini:<br/><input name='fail1' value =''/>&nbsp;<br/><a href='op:fileselect'>Выбрать фото</a><hr/>Подпись:<br/><textarea name='text'></textarea><br/><input type='submit' value='Загрузить'/><br/></form><a href='index.php?id=".$id."'>Назад</a><br/>";}else{header ("location: index.php");}
break;
################
case "cral":
if ($dostsmod==1){
if (empty($_GET['id'])){
echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$type = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($type);
if ($ms[type]!="rz"){
echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";require ("../incfiles/end.php");exit;}


if (isset($_POST['submit'])){

$text=check($_POST['text']);
mysql_query("insert into `gallery` values(0,'".$id."','".$realtime."','al','','".$text."','','','','');");
header ("location: index.php?id=$id");}else{
echo "Добавление альбома в раздел $ms[text].<br/><form action='index.php?act=cral&amp;id=".$id."' method='post'>Введите название:<br/><input type='text' name='text'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=".$id."'>В раздел</a><br/>";}}else{header ("location: index.php");}

break;
###################
case "album":
if (!empty($_SESSION['pid'])){
if (empty($_GET['id'])){
echo "Ошибка!";require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$type = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($type);
if ($ms[type]!="rz"){
echo "Ошибка!";require ("../incfiles/end.php");exit;}
mysql_query("insert into `gallery` values(0,'".$id."','".$realtime."','al','".$login."','".$login."','','','','');");
$al=mysql_insert_id();
header ("location: index.php?id=$al");}else{
header ("location: index.php");}

break;



#################
case "razd":
if ($dostsmod==1){
if (isset($_POST['submit'])){
$user=intval(check($_POST['user']));
$text=check($_POST['text']);
mysql_query("insert into `gallery` values(0,'0','".$realtime."','rz','','".$text."','','".$user."','','');");
header ("location: index.php");}else{
echo "Добавление раздела.<br/><form action='index.php?act=razd' method='post'>Введите название:<br/><input type='text' name='text'/><br/><input type='checkbox' name='user' value='1'/>Для альбомов юзеров<br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php'>В галерею</a><br/>";}}else{header ("location: index.php");}

break;
#####################
default:
if (!empty($_GET['id'])){
$id=intval(check($_GET['id']));
$type = mysql_query("select * from `gallery` where id='".$id."';");
$ms=mysql_fetch_array($type);
switch ($ms[type]){
case "rz":

$al = mysql_query("select * from `gallery` where type='al' and  refid='".$id."' order by time desc;");
$count=mysql_num_rows($al);
if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*10-10;
if ($count < $start + 10){ $end = $count; }
else {$end = $start + 10; }

while ($al1=mysql_fetch_array($al)){
if($i>=$start&&$i < $end){ 
$fot = mysql_query("select * from `gallery` where type='ft' and  refid='".$al1[id]."';");
$countf=mysql_num_rows($fot);
echo "<a href='index.php?id=".$al1[id]."'>$al1[text]</a> ($countf)<br/>";}++$i;}
########
if ($count>10){
echo "<hr/>";



$ba=ceil($count/10);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}

if ($start != 0) {echo '<a href="index.php?id='.$id.'&amp;page='.($page - 1).'">&lt;&lt;</a> ';}

$asd=$start-10;
$asd2=$start+20;
if ($offpg!=1){
if($asd<$count && $asd>0){echo ' <a href="index.php?id='.$id.'&amp;page=1">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="index.php?id='.$id.'&amp;page='.$paa.'">'.$paa.'</a> <a href="index.php?id='.$id.'&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="index.php?id='.$id.'&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="index.php?id='.$id.'&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="index.php?id='.$id.'&amp;page='.$pa.'">'.$pa.'</a> <a href="index.php?id='.$id.'&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$count && $i>=0){
$ii=floor(1+$i/10);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="index.php?id='.$id.'&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+10;}
if ($page2>12){
echo ' .. <a href="index.php?id='.$id.'&amp;page='.$paa2.'">'.$paa2.'</a> <a href="index.php?id='.$id.'&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="index.php?id='.$id.'&amp;page='.($paa3).'">'.($paa3).'</a> <a href="index.php?id='.$id.'&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="index.php?id='.$id.'&amp;page='.$pa2.'">'.$pa2.'</a> <a href="index.php?id='.$id.'&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$count){echo ' .. <a href="index.php?id='.$id.'&amp;page='.$ba.'">'.$ba.'</a>';}}else{
echo "<b>[$page]</b>";}
if ($count > $start + 10) {echo ' <a href="index.php?id='.$id.'&amp;page='.($page + 1).'">&gt;&gt;</a>';}
echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='".$id."'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}


##########
echo "Всего альбомов: $count<br/>";
if (!empty($_SESSION['pid'])&&$ms[user]==1){
$alb = mysql_query("select * from `gallery` where type='al' and  refid='".$id."' and avtor='".$login."';");
$cnt=mysql_num_rows($alb);
if ($cnt==1){
$alb1=mysql_fetch_array($alb);
echo "<a href='index.php?id=".$alb1[id]."'>В свой альбом</a><br/>";}else{
echo "<a href='index.php?act=album&amp;id=".$id."'>Создать свой альбом</a><br/>";}}
if ($dostsmod==1){echo "<a href='index.php?act=cral&amp;id=".$id."'>Создать новый альбом</a><br/>";
echo "<a href='index.php?act=del&amp;id=".$id."'>Удалить раздел</a><br/>";
echo "<a href='index.php?act=edit&amp;id=".$id."'>Изменить раздел</a><br/>";}
echo "<a href='index.php'>В галерею</a><br/>";

break;

case "al":
$delimag=opendir("temp");
while ($imd=readdir($delimag)){
if ($imd!="." && $imd!=".." && $imd!="index.php"){
$im[]=$imd;}}
closedir($delimag);
$totalim = count($im);
for ($imi = 0; $imi < $totalim; $imi++){
$filtime[$imi]=filemtime ("temp/$im[$imi]");
$tim=time();
$ftime1=$tim-10;
if ($filtime[$imi] < $ftime1){
unlink ("temp/$im[$imi]");}}

echo "Альбом $ms[text]<br/>";
$fot = mysql_query("select * from `gallery` where type='ft' and  refid='".$id."' order by time desc;");
$count=mysql_num_rows($fot);
if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*10-10;
if ($count < $start + 10){ $end = $count; }
else {$end = $start + 10; }
while ($fot1=mysql_fetch_array($fot)){
#######
if($i>=$start&&$i < $end){
$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='c'>";}else{$div="<div class='b'>";}
echo "$div <br/>&nbsp;<a href='index.php?id=".$fot1[id]."'>";




$infile="foto/$fot1[name]";

if (!empty($_SESSION['frazm'])){
$razm=$_SESSION['frazm'];}else{$razm=50;}
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
$format=format($infile);

switch($format){ 
case "gif": $im = ImageCreateFromGIF( $infile ); break; 
case "jpg": $im = ImageCreateFromJPEG( $infile ); break; 
case "jpeg": $im = ImageCreateFromJPEG( $infile ); break; 
case "png": $im = ImageCreateFromPNG( $infile ); break; 
}


$im1=imagecreatetruecolor($tn_width,$tn_height);
$namefile="$fot1[name]";

imagecopyresized($im1,$im,0,0,0,0,$tn_width,$tn_height,$width,$height);

switch($format){
case "gif":
$imagnam="temp/$namefile.temp.gif"; ImageGif($im1,$imagnam,$quality ); echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpg": 
$imagnam="temp/$namefile.temp.jpg"; imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpeg":
$imagnam="temp/$namefile.temp.jpg";imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";

 break;
case "png":
$imagnam="temp/$namefile.temp.png"; imagePng($im1,$imagnam,$quality); echo "<img src='".$imagnam."' alt=''/><br/>";

break; }
imagedestroy($im);
imagedestroy($im1);

#############
$fotsz=filesize("foto/$ms[name]");
$vrf=$fot1[time]+$sdvig*3600;
$vrf1=date("d.m.y / H:i",$vrf);
$kom = mysql_query("select * from `gallery` where type='km' and refid='".$fot1[id]."';");
$kom1=mysql_num_rows($kom);

echo "</a><br/>Добавлено: $vrf1<br/>Подпись: $fot1[text]<br/><a href='index.php?act=komm&amp;id=".$fot1[id]."'>Комментарии</a> ($kom1)<br/>";
if ($dostsmod==1){
echo "<a href='index.php?act=edf&amp;id=".$fot1[id]."'>Изменить</a> | <a href='index.php?act=delf&amp;id=".$fot1[id]."'>Удалить</a><br/>";}
echo "</div>";}++$i;



}
############
if ($count>10){
echo "<hr/>";



$ba=ceil($count/10);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}

if ($start != 0) {echo '<a href="index.php?id='.$id.'&amp;page='.($page - 1).'">&lt;&lt;</a> ';}

$asd=$start-10;
$asd2=$start+20;
if ($offpg!=1){
if($asd<$count && $asd>0){echo ' <a href="index.php?id='.$id.'&amp;page=1">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="index.php?id='.$id.'&amp;page='.$paa.'">'.$paa.'</a> <a href="index.php?id='.$id.'&amp;page='.($paa+1).'">'.($paa+1).'</a> .. <a href="index.php?id='.$id.'&amp;page='.($paa*2).'">'.($paa*2).'</a> <a href="index.php?id='.$id.'&amp;page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="index.php?id='.$id.'&amp;page='.$pa.'">'.$pa.'</a> <a href="index.php?id='.$id.'&amp;page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$count && $i>=0){
$ii=floor(1+$i/10);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="index.php?id='.$id.'&amp;page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+10;}
if ($page2>12){
echo ' .. <a href="index.php?id='.$id.'&amp;page='.$paa2.'">'.$paa2.'</a> <a href="index.php?id='.$id.'&amp;page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="index.php?id='.$id.'&amp;page='.($paa3).'">'.($paa3).'</a> <a href="index.php?id='.$id.'&amp;page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="index.php?id='.$id.'&amp;page='.$pa2.'">'.$pa2.'</a> <a href="index.php?id='.$id.'&amp;page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$count){echo ' .. <a href="index.php?id='.$id.'&amp;page='.$ba.'">'.$ba.'</a>';}}else{
echo "<b>[$page]</b>";}
if ($count > $start + 10) {echo ' <a href="index.php?id='.$id.'&amp;page='.($page + 1).'">&gt;&gt;</a>';}
echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='".$id."'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}

#############

if ($count!=0){
echo "Всего фотографий: $count<br/>";}else{
echo "В этом альбоме нет фотографий<br/>";}
$rz = mysql_query("select * from `gallery` where type='rz' and  id='".$ms[refid]."';");
$rz1=mysql_fetch_array($rz);
if ((!empty($_SESSION['pid'])&&$rz1[user]==1&&$ms[text]==$login)||($dostsmod==1)){
echo "<a href='index.php?act=upl&amp;id=".$id."'>Выгрузить фото</a><br/>";}
if ($dostsmod==1){
echo "<a href='index.php?act=del&amp;id=".$id."'>Удалить альбом</a><br/>";
echo "<a href='index.php?act=edit&amp;id=".$id."'>Изменить альбом</a><br/>";}
echo "<a href='index.php?id=".$ms[refid]."'>$rz1[text]</a><br/>";
echo "<a href='index.php'>В галерею</a><br/>";
break;

case "ft":
echo "<br/>&nbsp;";
$infile="foto/$ms[name]";

if (!empty($_SESSION['frazm'])){
$razm=$_SESSION['frazm'];}else{$razm=50;}
$sizs = GetImageSize($infile); 
$width = $sizs[0]; 
$height = $sizs[1];
$quality=100;

$format=format($infile);

switch($format){ 
case "gif": $im = ImageCreateFromGIF( $infile ); break; 
case "jpg": $im = ImageCreateFromJPEG( $infile ); break; 
case "jpeg": $im = ImageCreateFromJPEG( $infile ); break; 
case "png": $im = ImageCreateFromPNG( $infile ); break; 
}


$im1=imagecreatetruecolor($width,$height);
$namefile="$ms[name]";

imagecopy($im1,$im,0,0,0,0,$width,$height);

switch($format){
case "gif":
$imagnam="temp/$namefile.gif"; ImageGif($im1,$imagnam,$quality ); echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpg": 
$imagnam="temp/$namefile.jpg"; imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";
break; 
case "jpeg":
$imagnam="temp/$namefile.jpg";imageJpeg($im1,$imagnam,$quality );echo "<img src='".$imagnam."' alt=''/><br/>";

 break;
case "png":
$imagnam="temp/$namefile.png"; imagePng($im1,$imagnam,$quality); echo "<img src='".$imagnam."' alt=''/><br/>";

break; }
imagedestroy($im);
imagedestroy($im1);
$fotsz=filesize("foto/$ms[name]");
$fotsz= round($fotsz/1024,2);
$sizs = GetImageSize("foto/$ms[name]"); 
$fwidth = $sizs[0]; 
$fheight = $sizs[1];
$vrf=$ms[time]+$sdvig*3600;
$vrf1=date("d.m.y / H:i",$vrf);
echo "Размеры: $fwidth*$fheight пкс.<br/>Вес: $fotsz кб.<br/>Добавлено: $ms[avtor]<br/>Подпись: $ms[text]<br/>";
$kom = mysql_query("select * from `gallery` where type='km' and refid='".$id."';");
$kom1=mysql_num_rows($kom);
echo "<a href='index.php?act=komm&amp;id=".$id."'>Комментарии</a> ($kom1)<br/>";
echo "<a href='index.php?id=".$ms[refid]."'>Назад</a><br/>";
echo "<a href='index.php'>В галерею</a><br/>";
break;

default:
header ("location: index.php");
break;}
}else{
echo "<a href='index.php?act=new'>Новые фото</a><br/><br/>";
$rz = mysql_query("select * from `gallery` where type='rz';");
$count=mysql_num_rows($rz);
while ($rz1=mysql_fetch_array($rz)){
$al = mysql_query("select * from `gallery` where type='al' and  refid='".$rz1[id]."';");
$countal=mysql_num_rows($al);
echo "<a href='index.php?id=".$rz1[id]."'>$rz1[text]</a> ($countal)<br/>";}


if ($count!=0){
echo "Всего разделов: $count<br/>";}else{
echo "Разделы не созданы!<br/>";}
if ($dostsmod==1){
echo "<a href='index.php?act=razd'>Создать раздел</a><br/>";}
echo "<a href='index?act=preview'>Размеры изображений</a><br/>";
}
break;}

require ("../incfiles/end.php");
?>