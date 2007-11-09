<?php

define('_IN_PUSTO', 1);
session_name("SESID");
session_start();
$textl='Форум';
require("../incfiles/db.php");
require("../incfiles/func.php");
require("../incfiles/data.php");

if ($dostsmod==1){
if (!empty($_GET['act'])){$act=check($_GET['act']);}
switch ($act){
###########
case "moders":
if (isset($_POST['submit'])){
if (empty($_GET['id'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
if (isset($_POST['mod'])){

$q = mysql_query("select * from `forum` where type='a' and refid='".$id."';");
while ($q1=mysql_fetch_array($q)){
if (!in_array($q1[from],$_POST['mod'])){
mysql_query("delete from `forum` where `id`='".$q1[id]."';");}}
foreach ($_POST['mod'] as $v){
$q2 = mysql_query("select * from `forum` where type='a' and `from`='".$v."' and refid='".$id."';");
$q3=mysql_num_rows($q2);
if ($q3==0){
mysql_query("insert into `forum` values(0,'".$id."','a','','".check($v)."','','','','','','','','','','','','');");}}}else{

$q = mysql_query("select * from `forum` where type='a' and refid='".$id."';");
while ($q1=mysql_fetch_array($q)){
mysql_query("delete from `forum` where `id`='".$q1[id]."';");}}
header ("Location: forum.php?act=moders&id=$id");}else{
require("../incfiles/head.php");
require("../incfiles/inc.php");
if (!empty($_GET['id'])){
$id=intval(check($_GET['id']));
$typ = mysql_query("select * from `forum` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="f"){

echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
echo"Назначение модеров в подфорум $ms[text]<br/><form action='forum.php?act=moders&amp;id=".$id."' method='post'>";
$q = mysql_query("select * from `users` where rights='3';");
while ($q1=mysql_fetch_array($q)){
$q2 = mysql_query("select * from `forum` where type='a' and `from`='".$q1[name]."' and refid='".$id."';");
$q3=mysql_num_rows($q2);
if ($q3==0){
echo "<input type='checkbox' name='mod[]' value='".$q1[name]."'/>$q1[name]<br/>";}else{
echo "<input type='checkbox' name='mod[]' value='".$q1[name]."' checked='checked'/>$q1[name]<br/>";}}
echo "<input type='submit' name='submit' value='Ok!'/><br/></form>";
echo "<br/><a href='forum.php?act=moders'>Выбрать подфорум</a>";}else{
echo "Выберите подфорум<hr/>";
$q = mysql_query("select * from `forum` where type='f' order by realid;");
while ($q1=mysql_fetch_array($q)){
echo "<a href='forum.php?act=moders&amp;id=".$q1[id]."'>$q1[text]</a><br/>";}}}
echo "<br/><a href='forum.php?'>В управление форумом</a><br/>";

break;
############
case "del":
require("../incfiles/head.php");
require("../incfiles/inc.php");
if (empty($_GET['id'])){
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$typ = mysql_query("select * from `forum` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="f"&&$ms[type]!="r"){
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
switch ($ms[type]){
case "f":
if (isset($_GET['yes'])){
$raz = mysql_query("select * from `forum` where refid='".$id."';");
while($raz1=mysql_fetch_array($raz)){
$tem = mysql_query("select * from `forum` where refid='".$raz1[id]."';");
while($tem1=mysql_fetch_array($tem)){
$mes = mysql_query("select * from `forum` where refid='".$tem1[id]."';");
while($mes1=mysql_fetch_array($mes)){
if (!empty($mes1[attach])){
unlink ("../forum/files/$mes1[attach]");}
mysql_query("delete from `forum` where `id`='".$mes1[id]."';");}
mysql_query("delete from `forum` where `id`='".$tem1[id]."';");}
mysql_query("delete from `forum` where `id`='".$raz1[id]."';");}
mysql_query("delete from `forum` where `id`='".$id."';");
header ("Location: forum.php");}else{
echo "Вы уверены,что хотите удалить подфорум $ms[text]?<br/><a href='forum.php?act=del&amp;id=".$id."&amp;yes'>Да</a>|<a href='forum.php'>Нет</a><br/>";}
break;
case "r":
if (isset($_GET['yes'])){
$tem = mysql_query("select * from `forum` where refid='".$id."';");
while($tem1=mysql_fetch_array($tem)){
$mes = mysql_query("select * from `forum` where refid='".$tem1[id]."';");
while($mes1=mysql_fetch_array($mes)){
if (!empty($mes1[attach])){
unlink ("../forum/files/$mes1[attach]");}
mysql_query("delete from `forum` where `id`='".$mes1[id]."';");}
mysql_query("delete from `forum` where `id`='".$tem1[id]."';");}
mysql_query("delete from `forum` where `id`='".$id."';");
header ("Location: forum.php");}else{
echo "Вы уверены,что хотите удалить раздел $ms[text]?<br/><a href='forum.php?act=del&amp;id=".$id."&amp;yes'>Да</a>|<a href='forum.php'>Нет</a><br/>";}

break;}
echo "<a href='forum.php?'>В управление форумом</a><br/>";
break;
####
case "crraz":

if (empty($_GET['id'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$raz = mysql_query("select * from `forum` where type='f' and id='".$id."' ;");
if (mysql_num_rows($raz)==0){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$raz1 = mysql_fetch_array($raz);
if (isset($_POST['submit'])){
if (empty($_POST['nr'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Вы не ввели имя раздела!<br/><a href='forum.php?act=crraz&amp;id=".$id."'>Повторить</a><br/>";
require ("../incfiles/end.php");exit;}
$nr=check($_POST['nr']);
$q = mysql_query("select * from `forum` where type='r' and refid='".$id."' order by realid desc ;");
$q1 = mysql_num_rows($q);
if ($q1==0){$rid=1;}else{
while ($arr=mysql_fetch_array($q)){
$arr1[]=$arr[realid];}
$rid=$arr1[0]+1;}
mysql_query("insert into `forum` values(0,'".$id."','r','".$realtime."','','','".$rid."','','','".$nr."','','','','','','','');");
header ("Location: forum.php?id=$id");
}else{
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Добавление раздела в подфорум <font color='orange'>$raz1[text]</font>:<br/><form action='forum.php?act=crraz&amp;id=".$id."' method='post'><input type='text' name='nr'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form>";}
echo "<a href='forum.php?'>В управление форумом</a><br/>";
break;


#############
case "crforum":
if (isset($_POST['submit'])){
if (empty($_POST['nf'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Вы не ввели имя подфорума!<br/><a href='forum.php?act=crforum'>Повторить</a><br/>";
require ("../incfiles/end.php");exit;}
$nf=check($_POST['nf']);
$q = mysql_query("select * from `forum` where type='f' order by realid desc;");
$q1 = mysql_num_rows($q);
if ($q1==0){$rid=1;}else{
while ($arr=mysql_fetch_array($q)){
$arr1[]=$arr[realid];}
$rid=$arr1[0]+1;}
mysql_query("insert into `forum` values(0,'','f','".$realtime."','','','".$rid."','','','".$nf."','','','','','','','');");
header ("Location: forum.php");}else{
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Добавление подфорума:<br/><form action='forum.php?act=crforum' method='post'><input type='text' name='nf'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form>";}
echo "<a href='forum.php?'>В управление форумом</a><br/>";
break;
##########
case "edit":
if (empty($_GET['id'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$typ = mysql_query("select * from `forum` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="f"&&$ms[type]!="r"){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}

if (isset($_POST['submit'])){
if (empty($_POST['nf'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Вы не ввели новое название!<br/><a href='forum.php?act=edit&amp;id=".$id."'>Повторить</a><br/>";
require ("../incfiles/end.php");exit;}
$nf=check(trim($_POST['nf']));

mysql_query("update `forum` set  text='".$nf."' where id='".$id."';");
header ("Location: forum.php?id=$ms[refid]");}else{
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Изменить название:<br/><form action='forum.php?act=edit&amp;id=".$id."' method='post'><input type='text' name='nf' value='".$ms[text]."'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form>";}
echo "<a href='forum.php?'>В управление форумом</a><br/>";
break;
##########
case "up":
require("../incfiles/head.php");
require("../incfiles/inc.php");
if (empty($_GET['id'])){
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$typ = mysql_query("select * from `forum` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="f"&&$ms[type]!="r"){
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
switch ($ms[type]){
case "f":
$ri=mysql_query("select * from `forum` where type='f' and realid<'".$ms[realid]."' order by realid desc;");
break;
case "r":
$ri=mysql_query("select * from `forum` where type='r' and refid='".$ms[refid]."' and realid<'".$ms[realid]."' order by realid desc;");
break;}
$rei = mysql_num_rows($ri);
if ($rei==0){
echo "Нельзя туда двигать!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
while ($rid=mysql_fetch_array($ri)){
$arr[]=$rid[id];}
switch ($ms[type]){
case "f":
$tr=mysql_query("select * from `forum` where type='f' and id='".$arr[0]."';");
break;
case "r":
$tr=mysql_query("select * from `forum` where type='r' and id='".$arr[0]."';");
break;}
$tr1=mysql_fetch_array($tr);
$real1=$tr1[realid];
$real2=$ms[realid];
mysql_query("update `forum` set  realid='".$real1."' where id='".$id."';");
mysql_query("update `forum` set  realid='".$real2."' where id='".$arr[0]."';");
header ("Location: forum.php?id=$ms[refid]");

break;
######
case "down":
require("../incfiles/head.php");
require("../incfiles/inc.php");
if (empty($_GET['id'])){
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$typ = mysql_query("select * from `forum` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="f"&&$ms[type]!="r"){
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
switch ($ms[type]){
case "f":
$ri=mysql_query("select * from `forum` where type='f' and realid>'".$ms[realid]."' order by realid;");
break;
case "r":
$ri=mysql_query("select * from `forum` where type='r' and refid='".$ms[refid]."' and realid>'".$ms[realid]."' order by realid;");
break;}
$rei = mysql_num_rows($ri);
if ($rei==0){
echo "Нельзя туда двигать!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
while ($rid=mysql_fetch_array($ri)){
$arr[]=$rid[id];}
switch ($ms[type]){
case "f":
$tr=mysql_query("select * from `forum` where type='f' and id='".$arr[0]."';");
break;
case "r":
$tr=mysql_query("select * from `forum` where type='r' and id='".$arr[0]."';");
break;}
$tr1=mysql_fetch_array($tr);
$real1=$tr1[realid];
$real2=$ms[realid];
mysql_query("update `forum` set  realid='".$real1."' where id='".$id."';");
mysql_query("update `forum` set  realid='".$real2."' where id='".$arr[0]."';");
header ("Location: forum.php?id=$ms[refid]");

break;
#####
case "them":
if ($dostsadm==1){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Скрытые темы<br/>";
$dt=mysql_query("select * from `forum` where type='t' and close='1';");
$dt1=mysql_num_rows($dt);
while ($dt2=mysql_fetch_array($dt)){
$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='b'>";}else{$div="<div class='c'>";}
$dr=mysql_query("select * from `forum` where type='r' and id='".$dt2[refid]."';");
$dr1=mysql_fetch_array($dr);
$df=mysql_query("select * from `forum` where type='f' and id='".$dr1[refid]."';");
$df1=mysql_fetch_array($df);
echo "$div<a href='../forum/?id=".$dt2[id]."'>$df1[text]/$dr1[text]/$dt2[text]</a><br/>";
echo "<a href='forum.php?act=nah&amp;id=".$dt2[id]."'>Удалить</a> | <a href='forum.php?act=ins&amp;id=".$dt2[id]."'>Восстановить</a>";

echo "</div>";++$i;}
echo "<a href='forum.php?'>В управление форумом</a><br/>";
}else{header ("Location: forum.php");}
break;
######
case "ins":
if ($dostsadm==1){
require("../incfiles/head.php");
require("../incfiles/inc.php");
if (empty($_GET['id'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$typ = mysql_query("select * from `forum` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="t"&&$ms[type]!="m"){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
mysql_query("update `forum` set  close='0' where id='".$id."';");
switch ($ms[type]){
case "t":
header ("Location: forum.php?act=them");
break;
case "m":
header ("Location: forum.php?act=post");
break;}}else{header ("Location: forum.php");}
break;

######
case "nah":
if ($dostsadm==1){
if (empty($_GET['id'])){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
$id=intval(check($_GET['id']));
$typ = mysql_query("select * from `forum` where id='".$id."';");
$ms=mysql_fetch_array($typ);
if ($ms[type]!="t"&&$ms[type]!="m"){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
require ("../incfiles/end.php");exit;}
if (!empty($ms[attach])){
unlink ("../forum/files/$ms[attach]");}
mysql_query("delete from `forum` where id='".$ms[id]."';");
switch ($ms[type]){
case "t":
header ("Location: forum.php?act=them");
break;
case "m":
header ("Location: forum.php?act=post");
break;}}else{header ("Location: forum.php");}
break;
######
case "delhid":

if ($dostsadm==1){
if (isset($_GET['yes'])){
$dd = mysql_query("select * from `forum` where close='1';");
while ($dd1=mysql_fetch_array($dd)){
if (!empty($dd1[attach])){
unlink ("../forum/files/$dd1[attach]");}
mysql_query("delete from `forum` where id='".$dd1[id]."';");
header ("Location: forum.php");
}}else{
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Вы уверены?<br/><a href='forum.php?act=delhid&amp;yes'>Да</a>|<a href='forum.php'>Нет</a><br/>";}}
else{header ("Location: forum.php");}
break;
######
case "post":

if ($dostsadm==1){
require("../incfiles/head.php");
require("../incfiles/inc.php");
echo "Скрытые посты<br/>";
$dp=mysql_query("select * from `forum` where type='m' and close='1';");
$dp1=mysql_num_rows($dp);
while ($dp2=mysql_fetch_array($dp)){
$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='b'>";}else{$div="<div class='c'>";}
$dt=mysql_query("select * from `forum` where type='t' and id='".$dp2[refid]."';");
$dt1=mysql_fetch_array($dt);
$dr=mysql_query("select * from `forum` where type='r' and id='".$dt1[refid]."';");
$dr1=mysql_fetch_array($dr);
$df=mysql_query("select * from `forum` where type='f' and id='".$dr1[refid]."';");
$df1=mysql_fetch_array($df);
###
$vrp=$dp2[time]+$sdvig*3600;
$vr=date("d.m.Y / H:i",$vrp);
$uz= @mysql_query("select * from `users` where name='".check($dp2[from])."';");
$mass1 = @mysql_fetch_array($uz);
switch ($mass1[rights]){
case 7 :
$stat="Adm";
break;
case 6 :
$stat="Smd";
break;
case 3 :
$stat="Mod";
break;
case 1 :
$stat="Kil";
break;
default:
$stat="";
break;}
switch ($mass1[sex]){
case "m":
$pol="<img src='../images/m.gif' alt=''/>";
break;
case "zh":
$pol="<img src='../images/f.gif' alt=''/>";
break;}
$hd="$pol <b>$dp2[from]</b> $stat ($vr)<br/>";

if (!empty($dp2[to])){$hd="$hd $dp2[to], ";}



##
$dp2[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $dp2[text]);
$dp2[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $dp2[text]);
$dp2[text]=eregi_replace("\\[l\\]((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='\\1\\3'>\\7</a>", $dp2[text]);

if (stristr($dt2[text],"<a href=")){
$dp2[text]=eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)</a>", "<a href='\\1\\3'>\\3</a>" ,$dp2[text]);}else{
$dp2[text]=eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)", "<a href='\\1\\3'>\\3</a>" ,$dp2[text]);}
if ($offsm!=1&&$offgr!=1){
$tekst=smiles($dp2[text]);
$tekst=smilescat($tekst);

if ($dp2[from]==nickadmina || $dp2[from]==nickadmina2 || $array1[rights]>=1){
$tekst=smilesadm($tekst);}}else{$tekst=$dp2[text];}


###
echo "$div $hd $tekst<br/>$df1[text]/$dr1[text]/$dt1[text]<br/>";
echo "<a href='forum.php?act=nah&amp;id=".$dp2[id]."'>Удалить</a> | <a href='forum.php?act=ins&amp;id=".$dp2[id]."'>Восстановить</a>";

echo "</div>";++$i;}
echo "<a href='forum.php?'>В управление форумом</a><br/>";
}else{header ("Location: forum.php");}
break;



######
default: 
require("../incfiles/head.php");
require("../incfiles/inc.php");
if (empty($_GET['id'])){ 
echo "Все форумы<hr/>";
$q = mysql_query("select * from `forum` where type='f' order by realid ;");
while($mass = mysql_fetch_array($q)) {
$colraz = mysql_query("select * from `forum` where type='r' and refid='".$mass[id]."';");
$colraz1 = mysql_num_rows($colraz);
$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='b'>";}else{$div="<div class='c'>";}
$ri=mysql_query("select * from `forum` where type='f' and  realid>'".$mass[realid]."';");
$rei = mysql_num_rows($ri);
$ri1=mysql_query("select * from `forum` where type='f' and realid<'".$mass[realid]."';");
$rei1 = mysql_num_rows($ri1);
echo "$div<a href='forum.php?id=".$mass[id]."'>$mass[text]</a> ($colraz1)<br/>";
if ($rei1!=0){echo "<a href='forum.php?act=up&amp;id=".$mass[id]."'>Вверх</a> | ";}
if ($rei!=0){echo "<a href='forum.php?act=down&amp;id=".$mass[id]."'>Вниз</a> | ";}
echo "<a href='forum.php?act=edit&amp;id=".$mass[id]."'>Edit</a> | <a href='forum.php?act=del&amp;id=".$mass[id]."'>Del</a>";
echo "</div>";	 ++$i;
}
echo "<hr/><a href='?act=crforum'>Создать подфорум</a><br/><br/>";
echo "<a href='?act=moders'>Модераторы</a><br/>";
if ($dostsadm==1){
$dt=mysql_query("select * from `forum` where type='t' and close='1';");
$dt1=mysql_num_rows($dt);
$dp=mysql_query("select * from `forum` where type='m' and close='1';");
$dp1=mysql_num_rows($dp);
echo "<a href='forum.php?act=them'>Скрытые темы</a>($dt1)<br/><a href='forum.php?act=post'>Скрытые посты</a>($dp1)<br/><a href='forum.php?act=delhid'>Удалить скрытые темы и посты</a><br/>";}
}
if (!empty($_GET['id'])){
$id=intval(check($_GET['id']));
$type = mysql_query("select * from `forum` where id= '".$id."';");
$type1 = mysql_fetch_array($type);
$tip=$type1[type];
switch ($tip){
case "f":
echo "<b>$type1[text]</b><hr/>";
$q1 = mysql_query("select * from `forum` where type='r' and refid='".$id."'  order by realid ;");
$colraz2 = mysql_num_rows($q1);

while($mass = mysql_fetch_array($q1)) {	$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='b'>";}else{$div="<div class='c'>";}
$ri=mysql_query("select * from `forum` where type='r' and refid='".$id."' and  realid>'".$mass[realid]."';");
$rei = mysql_num_rows($ri);
$ri1=mysql_query("select * from `forum` where type='r' and refid='".$id."' and realid<'".$mass[realid]."';");
$rei1 = mysql_num_rows($ri1);

	echo "$div$mass[text]<br/>";
if ($rei1!=0){echo "<a href='forum.php?act=up&amp;id=".$mass[id]."'>Вверх</a> | ";}
if ($rei!=0){echo "<a href='forum.php?act=down&amp;id=".$mass[id]."'>Вниз</a> | ";}
echo "<a href='forum.php?act=edit&amp;id=".$mass[id]."'>Edit</a> | <a href='forum.php?act=del&amp;id=".$mass[id]."'>Del</a>";
echo "</div>";	 ++$i;}
echo "<hr/><a href='?act=crraz&amp;id=".$id."'>Создать раздел</a><br/>";
echo "<a href='forum.php?'>В управление форумом</a><br/>";
break;}
}

break;}


}else{header("Location: ../index.php?err");}
echo "<a href='../forum/?'>В форум</a><br/>";
require ("../incfiles/end.php");
?>