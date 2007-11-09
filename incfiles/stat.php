<?
defined('_IN_PUSTO') or die('Error:restricted access'); 

##########
function dnews(){
if (!empty($_SESSION[pid]))
{$q = mysql_query("select * from `users` where id='".intval($_SESSION['pid'])."';");
$datauser = mysql_fetch_array($q);
$sdvig=trim($datauser['sdvig']);}
$nw = @mysql_query("select * from `news` order by time desc;");
while ($nw1=mysql_fetch_array($nw)){
$ar[]=$nw1[time];}
$vrn=$ar[0]+$sdvig*3600;
$vrn1=date("H:i/d.m.y",$vrn);
echo $vrn1;}

function kuser(){
$uzs = @mysql_query("select * from `users` ;");
$uzs1 = @mysql_num_rows($uzs);
echo "$uzs1";}

function wfrm($id){
$tt = @mysql_query("select * from `settings` where  id='1';");
$tt1 = mysql_fetch_array($tt);
$sdvigclock=$tt1[sdvigclock];

$realtime=time()+$sdvigclock*3600;
$onltime=$realtime-300;
$count=0;
$qf = @mysql_query("select * from `users` where  lastdate>='".intval($onltime)."';");
while($arrf = mysql_fetch_array($qf)) {
$whf = mysql_query("select * from `count` where name='".$arrf[name]."' order by time desc ;");
while($whf1 = mysql_fetch_array($whf)){
$whf2[]=$whf1[where];}
$wherf=$whf2[0];
$whf2=array();
$wherf1=explode(",",$wherf);
if (empty($id)){
if ($wherf1[0]=="forum"){$count=$count+1;}}else{
if ($wherf=="forum,$id"){$count=$count+1;}}}
echo "$count";}

function dload(){
$tt = @mysql_query("select * from `settings` where  id='1';");
$tt1 = mysql_fetch_array($tt);
$sdvigclock=$tt1[sdvigclock];

$realtime=time()+$sdvigclock*3600;
$fl = @mysql_query("select * from `download` where type='file' ;");
$countf = @mysql_num_rows($fl);
$old=$realtime-(3*24*3600);
$fl1 = @mysql_query("select * from `download` where time > '".$old."' and type='file' ;");
$countf1 = @mysql_num_rows($fl1);
echo "$countf"; 
if ($countf1>0){echo "/+$countf1";}}

function uload(){
$tt = @mysql_query("select * from `settings` where  id='1';");
$tt1 = mysql_fetch_array($tt);
$sdvigclock=$tt1[sdvigclock];

$realtime=time()+$sdvigclock*3600;
$fl = @mysql_query("select * from `upload` where type='file' and moder='1';");
$countf = @mysql_num_rows($fl);
$old=$realtime-(3*24*3600);
$fl1 = @mysql_query("select * from `upload` where time > '".$old."' and type='file' and moder='1';");
$countf1 = @mysql_num_rows($fl1);
echo "$countf"; 
if ($countf1>0){echo "/+$countf1";}
$fm = @mysql_query("select * from `upload` where type='file' and moder='0';");
$countm = @mysql_num_rows($fm);
if (!empty($_SESSION[pid]))
{$q = mysql_query("select * from `users` where id='".intval($_SESSION['pid'])."';");
$datauser = mysql_fetch_array($q);
$login=trim($datauser['name']);
$statad=trim($datauser['rights']);
if (($login==$nickadmina ||$login==$nickadmina2||$statad=="7"||$statad=="6"||$statad=="5")&&($countm>0)){echo "/<a href='".$home."/download/upload.php?act=moder'><font color='#FF0000'> +$countm</font></a>";}}}

function fgal(){
$tt = @mysql_query("select * from `settings` where  id='1';");
$tt1 = mysql_fetch_array($tt);
$sdvigclock=$tt1[sdvigclock];

$realtime=time()+$sdvigclock*3600;
$fl = @mysql_query("select * from `gallery` where type='ft' ;");
$countf = @mysql_num_rows($fl);
$old=$realtime-(3*24*3600);
$fl1 = @mysql_query("select * from `gallery` where time > '".$old."' and type='ft' ;");
$countf1 = @mysql_num_rows($fl1);
echo "$countf"; 
if ($countf1>0){echo "/+$countf1";}}

function brth(){
$tt = @mysql_query("select * from `settings` where  id='1';");
$tt1 = mysql_fetch_array($tt);
$sdvigclock=$tt1[sdvigclock];

$realtime=time()+$sdvigclock*3600;
$mon=date("m",$realtime);
if (substr($mon,0,1)==0){$mon=str_replace("0","",$mon);}

$day=date("d",$realtime);
if (substr($day,0,1)==0){$day=str_replace("0","",$day);}
$q = mysql_query("select * from `users` where dayb='".$day."' and monthb='".$mon."' and preg='1';");
$count = mysql_num_rows($q);
echo $count;}

function stlib(){
$tt = @mysql_query("select * from `settings` where  id='1';");
$tt1 = mysql_fetch_array($tt);
$sdvigclock=$tt1[sdvigclock];

$realtime=time()+$sdvigclock*3600;
$fl = @mysql_query("select * from `lib` where type='bk' and moder='1';");
$countf = @mysql_num_rows($fl);
$old=$realtime-(3*24*3600);
$fl1 = @mysql_query("select * from `lib` where time > '".$old."' and type='bk' and moder='1';");
$countf1 = @mysql_num_rows($fl1);
echo "$countf"; 
if ($countf1>0){echo "/+$countf1";}
$fm = @mysql_query("select * from `lib` where type='bk' and moder='0';");
$countm = @mysql_num_rows($fm);
if (!empty($_SESSION[pid]))
{$q = mysql_query("select * from `users` where id='".intval($_SESSION['pid'])."';");
$datauser = mysql_fetch_array($q);
$login=trim($datauser['name']);
$statad=trim($datauser['rights']);
if (($login==$nickadmina ||$login==$nickadmina2||$statad=="7"||$statad=="6"||$statad=="5")&&($countm>0)){echo "/<a href='".$home."/str/lib.php?act=moder'><font color='#FF0000'> +$countm</font></a>";}}}

function wch($id){
$tt = @mysql_query("select * from `settings` where  id='1';");
$tt1 = mysql_fetch_array($tt);
$sdvigclock=$tt1[sdvigclock];

$realtime=time()+$sdvigclock*3600;
$onltime=$realtime-300;
$count=0;
$qf = @mysql_query("select * from `users` where  lastdate>='".intval($onltime)."';");
while($arrf = mysql_fetch_array($qf)) {
$whf = mysql_query("select * from `count` where name='".$arrf[name]."' order by time desc ;");
while($whf1 = mysql_fetch_array($whf)){
$whf2[]=$whf1[where];}
$wherf=$whf2[0];
$whf2=array();
$wherf1=explode(",",$wherf);
if (empty($id)){
if ($wherf1[0]=="chat"){$count=$count+1;}}else{
if ($wherf=="chat,$id"){$count=$count+1;}}}
echo "$count";}

function gbook(){
$gbs = @mysql_query("select * from `guest` ;");
$gbs1 = @mysql_num_rows($gbs);
echo "$gbs1";}
?>