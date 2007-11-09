<?php

define('_IN_PUSTO', 1);


$textl='Новости сайта';
$headmod="news";
require("../incfiles/db.php");
require("../incfiles/func.php");
require("../incfiles/data.php");
require("../incfiles/head.php");
require("../incfiles/inc.php");

$nw = mysql_query("select * from `news` order by time desc;");
if (!empty($_GET['kv'])){$count=intval(check($_GET['kv']));}else{
$count=mysql_num_rows($nw);}
if (empty($_GET['page'])) {$page = 1;}
else {$page = intval($_GET['page']);}
$start=$page*$kmess-$kmess;
if ($count < $start + $kmess){ $end = $count; }
else {$end = $start + $kmess; } 

while ($nw1=mysql_fetch_array($nw)){
if($i>=$start&&$i < $end){ 
$d=$i/2;$d1=ceil($d);$d2=$d1-$d;$d3=ceil($d2);
if ($d3==0){$div="<div class='c'>";}else{$div="<div class='b'>";}
$nw1[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $nw1[text]);
$nw1[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $nw1[text]);
$nw1[text]=eregi_replace("\\[l\\]((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='\\1\\3'>\\7</a>", $nw1[text]);

if (stristr($nw1[text],"<a href=")){
$nw1[text]=eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)</a>", "<a href='\\1\\3'>\\3</a>" ,$nw1[text]);}else{
$nw1[text]=eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)", "<a href='\\1\\3'>\\3</a>" ,$nw1[text]);}
if ($offsm!=1&&$offgr!=1){
$tekst=smiles($nw1[text]);
$tekst=smilescat($tekst);

if ($nw1[from]==nickadmina || $nw1[from]==nickadmina2 || $nw11[rights]>=1){
$tekst=smilesadm($tekst);}}else{$tekst=$nw1[text];}


$vr=$nw1[time]+$sdvig*3600;
$vr1=date("d.m.y / H:i",$vr);
echo "$div<b>$nw1[name]</b><br/>Добавил: $nw1[avt] ($vr1)<br/><br/>$tekst<br/>";
if ($nw1[kom]!=0&&$nw1[kom]!=""){
$mes = mysql_query("select * from `forum` where type='m' and refid= '".$nw1[kom]."';");
$komm=mysql_num_rows($mes)-1;
echo "<a href='../forum/?id=".$nw1[kom]."'>Обсудить на форуме ($komm)</a><br/>";}else{echo "Новость не нуждается в комментариях<br/>";}

echo "</div>";}++$i;}
######
if ($count>$kmess){
echo "<hr/>";

$ba=ceil($count/$kmess);
if ($offpg!=1){
echo"Страницы:<br/>";}else{echo"Страниц: $ba<br/>";}
$asd=$start-($kmess);
$asd2=$start+($kmess*2);

if ($start != 0) {echo '<a href="news.php?page='.($page-1).'">&lt;&lt;</a> ';}
if ($offpg!=1){
if($asd<$count && $asd>0){echo ' <a href="news.php?page=1&amp;">1</a> .. ';}
$page2=$ba-$page;
$pa=ceil($page/2);
$paa=ceil($page/3);
$pa2=$page+floor($page2/2);
$paa2=$page+floor($page2/3);
$paa3=$page+(floor($page2/3)*2);
if ($page>13){
echo ' <a href="pnews.php?page='.$paa.'">'.$paa.'</a> <a href="news.php?page='.($paa+1).'">'.($paa+1).'</a> .. <a href="news.php?page='.($paa*2).'">'.($paa*2).'</a> <a href="news.php?page='.($paa*2+1).'">'.($paa*2+1).'</a> .. ';}
elseif ($page>7){
echo ' <a href="news.php?page='.$pa.'">'.$pa.'</a> <a href="news.php?page='.($pa+1).'">'.($pa+1).'</a> .. ';}
for($i=$asd; $i<$asd2;)
{
if($i<$count && $i>=0){
$ii=floor(1+$i/$kmess);

if ($start==$i) {
echo " <b>$ii</b>";
               }
                else {
echo ' <a href="news.php?page='.$ii.'">'.$ii.'</a> ';
                     }}
$i=$i+$kmess;}
if ($page2>12){
echo ' .. <a href="news.php?page='.$paa2.'">'.$paa2.'</a> <a href="news.php?page='.($paa2+1).'">'.($paa2+1).'</a> .. <a href="news.php?page='.($paa3).'">'.($paa3).'</a> <a href="news.php?page='.($paa3+1).'">'.($paa3+1).'</a> ';}
elseif ($page2>6){
echo ' .. <a href="news.php?page='.$pa2.'">'.$pa2.'</a> <a href="news.php?page='.($pa2+1).'">'.($pa2+1).'</a> ';}
if($asd2<$count){echo ' .. <a href="news.php?page='.$ba.'">'.$ba.'</a>';}}else{
echo "<b>[$page]</b>";}


if ($count > $start + $kmess) {echo ' <a href="news.php?page='.($page+1).'">&gt;&gt;</a>';}
echo "<form action='news.php'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";}
if (!empty($_GET['kv'])){echo "Новых: $count<br/><a href='news.php'>Все новости</a><br/>";}else{echo "Всего: $count<br/>";}

require ("../incfiles/end.php");
?>