<?php


define('_IN_PUSTO', 1);

$headmod = 'auto';
require("incfiles/db.php");
require("incfiles/func.php");
require("incfiles/data.php");

//require("incfiles/inc.php");



    
    $query = "SELECT * FROM `users` WHERE name='".check($_GET['n'])."'";
    $nme = mysql_fetch_array(mysql_query($query));
    if (!$nme) {header("Location: index.php?nolog");exit;}
    
if ($nme['password']==md5(MD5(check($_GET['p']))))
    { 
$provid=$nme['id'];
################
if ($nme['preg']=="0" && $nme['regadm']==""){
header ("Location: index.php?regwait");exit;}
if ($nme['preg']=="0" && $nme['regadm']!==""){
$admreg=$nme['regadm'];
header ("Location: index.php?regotkl&regadmin=$admreg");exit;}
if ($nme['preg']=="1" && $nme['regadm']!=="" && $nme['pvrem']=="0"){
if(@mysql_query("update `users` set  pvrem='$realtime'  where name='".check($_GET['n'])."';")){
    if (session_start())
    	{
if ($_GET['mem']==1){ 
$cpid=base64_encode(intval($provid));
$ckod=base64_encode(intval($provkode));
SetCookie("cpide", $cpid, time()+3600*24*365);
SetCookie("ckode", $ckod, time()+3600*24*365);} 
$_SESSION['pid'] = intval($provid);
$_SESSION['provc'] = intval($provkode);
  	   
    		header ("Location: index.php?enter&regprin");
	exit;	
    	}}
}
###################

if ($_GET['mem']==1){
$cpid=base64_encode(intval($provid));
$ckod=base64_encode(intval($provkode));
SetCookie("cpide", $cpid, time()+3600*24*365);
SetCookie("ckode", $ckod, time()+3600*24*365); }
$_SESSION['pid'] = intval($provid);
$_SESSION['provc'] = intval($provkode);
mysql_query("update `users` set sestime='".$realtime."' where id='".intval($_SESSION['pid'])."';");
header ("Location: index.php?enter");
		
	}
 
   else
  {
    header ("Location: in.php?err=1");
  }
  
?>