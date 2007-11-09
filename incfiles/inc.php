<?php

defined('_IN_PUSTO') or die ('Error:restricted access');

$agn=check(getenv(HTTP_USER_AGENT));
if (!empty($_SESSION['pid'])){
if ($datauser['browser']!=$agn){
echo "Предупреждение безопасности:<br/> Ваш юзерагент<br/><font color='".$clink."'> $agn </font><br/>отличается от используемого ранее<br/> <font color='".$clink."'>$datauser[browser]</font>!<br/>";


if ($dostmod==1){
echo "<form action='".$home."/auto.php' method='get'>Имя:<br/><input type='text' name='n' maxlength='20' value='".$login."'/><br/>Пароль:<br/><input type='password' name='p' maxlength='20'/><br/><input type='checkbox' name='mem' value='1' checked='checked'/>Запомнить меня<br/><input type='submit' value='Вход'/></form>";



mysql_query("update `users` set `browser`='".$agn."' where `id`='".intval(check($_SESSION['pid']))."';");
$_SESSION=array();
setcookie('cpide', '');
setcookie('ckode', '');
setcookie(session_name(), '');
session_destroy();
echo'<div style=\'text-align: center\'><a href=\''.$home.'\'>&#169;'.$copyright.'</a></div></div></body></html>';exit;
}
mysql_query("update `users` set `browser`='".$agn."' where `id`='".intval(check($_SESSION['pid']))."';");
}}

if (isset($_GET['login'])||isset($_GET['pid'])||isset($_GET['statad'])||isset($_GET['dostsadm'])||isset($_GET['dostadm'])||isset($_GET['dostsmod'])||isset($_GET['dostfmod'])||isset($_GET['dostcmod'])||isset($_GET['dostkmod'])||isset($_GET['dostmod'])||isset($_GET['textl'])||isset($_GET['headmod'])){echo"<div>Чё,самый умный,да????  ПШОЛНАХ ОТСЮДА!!!!</div>";echo"<div style='text-align: center'>";
echo'<a href=\''.$home.'\'>&#169;'.$copyright.'</a></div></div></body></html>';exit;}


if ($headmod!="pradd"){
$newl = mysql_query("select * from `privat` where user = '".$login."' and type = 'in' and chit = 'no';");
$countnew = mysql_num_rows($newl);
if ($countnew>0)
{echo "<div style='text-align: center'><a href='$home/str/pradd.php?act=in&amp;new'><b><font color='red'>Вам письмо: $countnew</font></b></a></div>";}}

if (getenv("HTTP_CLIENT_IP")) $ipp = getenv("HTTP_CLIENT_IP");
else if(getenv("REMOTE_ADDR")) $ipp = getenv("REMOTE_ADDR");
else if(getenv("HTTP_X_FORWARDED_FOR")) $ipp = getenv("HTTP_X_FORWARDED_FOR");
else {$ipp = "not detected";}
$ipp=check($ipp);
$agn=getenv(HTTP_USER_AGENT);
$agn=check($agn); 
$dtime=$realtime-60;


$dos = mysql_query("select * from `count` where ip='".$ipp."' and time>='".$dtime."' ;");
$dos2 = mysql_num_rows($dos);
$dos1 = mysql_query("select * from `count` where ip='".$ipp."' order by time desc ;");
$pp=0;
while($dos11 = mysql_fetch_array($dos1)){if ($pp<1){
$idip = $dos11['id'];}
++$pp;}
if ($dos2>15){
$dos3 = mysql_query("select * from `count` where id='".$idip."'  ;");
$dos33=mysql_fetch_array($dos3);
$provdos=$dos33['dos'];
if ($provdos==0){
$dosp = mysql_query("select * from `privat` where temka='".$ipp."' and user='".$nickadmina."' and type='in'  ;");
$dosp2 = mysql_num_rows($dosp);
if ($dosp2<=3){

$messg="Превышение допустимого количества переходов за единицу времени!!!$agn/$ipp.";
if (empty($_SESSION['pid']))
{$otk="Guest";}
else
{$otk=$login;}
//mysql_query("insert into `privat` values(0,'".$nickadmina."','".$messg."','".$realtime."','".$otk."','in','no','".$ipp."','0','','','','');");
}
mysql_query("update `count` set `dos`='1' where `id`='".$idip."';");}

echo "Превышение допустимого количества переходов за единицу времени!!!<br/>Отдохните минутку<br/>";

echo"<div style='text-align: center'>";
echo'<a href=\''.$home.'\'><b>&#169;'.$copyright.'</b></a></div></div></body></html>';exit;}
$delid = mysql_query("select * from `count` order by id desc ;");
$pp1=0;
while($delid1 = mysql_fetch_array($delid)){if ($pp1<1){
$iddel = $delid1['id'];}
++$pp1;}
$iddel1=$iddel-1000;if ($iddel1>0){
mysql_query("delete from `count` where id='".intval($iddel1)."' LIMIT 1;");}
if (empty($_SESSION['pid']))
{$user1="Guestuser";}
else
{$user1=$login;}

if ($headmod!="forum"&&$headmod!="chat"){
mysql_query("insert into `count` values(0,'".$ipp."','".$agn."','".$realtime."','".$headmod."','".$user1."','0');");}
###
if ($bann=="1"){

$tti=$realtime;
$jjtti=round(($datauser['bantime']-$tti)/60);
if($jjtti>60) {$jjtti=round($jjtti/60).' час.';}else{$jjtti=$jjtti.' мин';}	

if($jjtti>0){
echo"<div>Вас забанил <font color='red'>$whobann</font></div>";	
if($whybann==""){echo"<div>Причина не указана</div>";}else{echo"<div>Причина:<font color='red'> $whybann</font></div>";}
echo"<div>Время до окончания бана: $jjtti</div>";}	
else{$vrem="";$banned="0";$whyban="";
if(@mysql_query("update `users` set  bantime='".$vrem."', why='".$whyban."', who='".$whoban."', ban='".$banned."'  where id='".intval($_SESSION['pid'])."';")) 
echo "<div>ВЫ БЫЛИ ЗАБАНЕНЫ<br/>Поздравляем!!! Время вашего бана вышло, постарайтесь вести себя достойно, чтобы не злить админа или модераторов</div>";

}
echo"<div style='text-align: center'>";
echo'<a href=\''.$home.'\'>&#169;'.$copyright.'-2007 г.</a></div></div></body></html>';exit;
}
if ($bann=="2"){
echo"<div>Вас забанил <font color='red'>$whobann</font></div>";	
if($whybann==""){echo"<div>Причина не указана</div>";}else{echo"<div>Причина:<font color='red'> $whybann</font></div>";}
echo"<div>Бан активен до отмены</div>";
echo"<div style='text-align: center'>";
echo'<a href=\''.$home.'\'>&#169;'.$copyright.'-2007 г.</a></div></div></body></html>';exit;
}


                      if($ipb1!=0){
echo"<div>Вас забанил <font color='red'>$ipadm</font> по IP+Soft(<font color='red'>$REMOTE_ADDR/$HTTP_USER_AGENT</font>)</div>";

echo"<div style='text-align: center'>";
echo'<a href=\''.$home.'\'>&#169;'.$copyright.'-2007 г.</a></div></div></body></html>';exit;
}
#
if (!empty($_SESSION['pid'])&&$headmod!=="auto"&&$headmod!=="mainpage"){
mysql_query("update `users` set lastdate='".$realtime."', ip='".$ipp."', browser='".$agn."' where id='".intval($_SESSION['pid'])."';");}


if (!empty($_SESSION['pid'])&& $headmod!=="auto" && !isset($_GET['enter']))
{
$vremja=$realtime-$datauser[lastdate];
$vremja2=$vrsite+$vremja;
mysql_query("update `users` set  vremja='".$vremja2."'  where id='".intval($_SESSION['pid'])."';");}
#
 $rega = mysql_query("select * from `users` where preg='0' ;");
$rega1 = mysql_fetch_array($rega);
$rega2 = mysql_num_rows($rega);	
if ($dostadm=="1"&&$rega2!==0){ 
 echo"<div>Подтверждения регистрации ожидают
<a href=\"$home/$admp/preg.php\">$rega2</a>
человек</div>";}

if ((!empty($_SESSION['pid'])) && $headmod!="auto")
{
if ($_SESSION['provc']!=$provkode){
exit("Ошибка выполения запроса<br/><a href='".$home."/exit.php'>Очистить сессию</a></div></div></body></html>");}}
$mon=date("m",$realtime);
if (substr($mon,0,1)==0){$mon=str_replace("0","",$mon);}

$day=date("d",$realtime);
if (substr($day,0,1)==0){$day=str_replace("0","",$day);}
$mesyac=array(1=>"января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря");

$ref=getenv("HTTP_REFERER");
$ref=htmlspecialchars($ref);

if ((isset($_SESSION['refsm']))&&($headmod!="smile")){
unset($_SESSION['refsm']);}
if ($offgr==1){ob_start(offimg);}

?>