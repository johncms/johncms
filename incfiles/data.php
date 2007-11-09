<?php

defined('_IN_PUSTO') or die ('Error:restricted access');
$settings = mysql_query("select * from `settings`;");
$set = mysql_fetch_array($settings);
$nickadmina2=trim($set['nickadmina2']); 
$nickadmina=trim($set['nickadmina']);	          
$emailadmina=trim($set['emailadmina']); 
$sdvigclock=trim($set['sdvigclock']);       
$copyright=trim($set['copyright']);
$home=trim($set['homeurl']);
$ras_pages=trim($set['rashstr']);      
$gzip=trim($set['gzip']);
$admp=trim($set['admp']);
$fmod=trim($set['fmod']);
$rmod=trim($set['rmod']);
$flsz=trim($set['flsz']);
$gb=trim($set['gb']);
$provkode=18111977;

$realtime=time()+$sdvigclock*3600;

###
if (!empty($_SESSION[pid]))
{$q = mysql_query("select * from `users` where id='".intval($_SESSION['pid'])."';");
$datauser = mysql_fetch_array($q);
$idus=$_SESSION['pid'];
$login=trim($datauser['name']);
$kmess=trim($datauser['kolanywhwere']);
$obn=trim($datauser['timererfesh']);
$fon=trim($datauser['bgcolor']);

$clb=trim($datauser['bclass']);
$clc=trim($datauser['cclass']);
$cld=trim($datauser['dclass']);
$ris=trim($datauser['bgim']);
$ris2=trim($datauser['bgr']);
$colt=trim($datauser['tex']);
$clink=trim($datauser['link']);
$statad=trim($datauser['rights']);
$bann=trim($datauser['ban']);
$whobann=trim($datauser['who']); 
$whybann=trim($datauser['why']);
$sdvig=trim($datauser['sdvig']);
$prvr=trim($datauser['pvrem']);
$vrsite=trim($datauser['vremja']);
$ipadr=trim($datauser['ip']);
$soft=trim($datauser['browser']);
$offpg=trim($datauser['offpg']);
$offtr=trim($datauser['offtr']);
$offgr=trim($datauser['offgr']);
$offsm=trim($datauser['offsm']);
$bann=trim($datauser['ban']);
$whobann=trim($datauser['who']); 
$whybann=trim($datauser['why']);
$pereh=trim($datauser['pereh']);
$cntem=trim($datauser['cntem']);
$ccolp=trim($datauser['ccolp']);
$cdtim=trim($datauser['cdtim']);
$cssip=trim($datauser['cssip']);
$csnik=trim($datauser['csnik']);
$conik=trim($datauser['conik']);
$cadms=trim($datauser['cadms']);
$cons=trim($datauser['cons']);
$coffs=trim($datauser['coffs']);
$cdinf=trim($datauser['cdinf']);
$upfp=trim($datauser['upfp']);
$nmenu=trim($datauser['nmenu']);
$farea=trim($datauser['farea']);
$carea=trim($datauser['carea']);
$pfon=trim($datauser['pfon']);
$cpfon=trim($datauser['cpfon']);
$ccfon=trim($datauser['ccfon']);
$cctx=trim($datauser['cctx']);
$chmes=trim($datauser['chmes']);
$nastroy=trim($datauser['nastroy']);
$dpp=date("d.m.Y / H:i",trim($datauser['lastdate']));
$dayr=trim($datauser['dayb']);
$monthr=trim($datauser['monthb']);
if ($login==$nickadmina ||$login==$nickadmina2){$dostsadm="1";}
if($login==$nickadmina ||$login==$nickadmina2||$statad=="7"){$dostadm="1";}
if ($login==$nickadmina ||$login==$nickadmina2||$statad=="7"||$statad=="6"){$dostsmod="1";}
if ($login==$nickadmina ||$login==$nickadmina2||$statad=="7"||$statad=="6"||$statad=="5"){$dostlmod="1";}
if ($login==$nickadmina ||$login==$nickadmina2||$statad=="7"||$statad=="6"||$statad=="4"){$dostdmod="1";}
if ($login==$nickadmina ||$login==$nickadmina2||$statad=="7"||$statad=="6"||$statad=="3"){$dostfmod="1";}
if ($login==$nickadmina ||$login==$nickadmina2||$statad=="7"||$statad=="6"||$statad=="2"){$dostcmod="1";}
if ($login==$nickadmina ||$login==$nickadmina2||$statad=="1"||$statad=="7"||$statad=="6"){$dostkmod="1";}
if ($login==$nickadmina ||$login==$nickadmina2||$statad>="1"){$dostmod="1";}
}else{
$kmess=10;
$sdvig=0;
$obn=20;}
if ($clink==""){$clink="#CCCCCC";}
if ($fon==""){$fon="#666666";}
if ($clb==""){$clb="#009999";}
if ($clc==""){$clc="#0000FF";}
if ($colt==""){$colt="#000000";}
/*
if ($clink==""){$clink="#99FF33";}
if ($fon==""){$fon="#333333";}
if ($clb==""){$clb="#666666";}
if ($clc==""){$clc="#999999";}
if ($colt==""){$colt="#FFFFFF";}
*/

if ($cntem==""){$cntem=$colt;}
if ($ccolp==""){$ccolp=$colt;}
if ($cdtim==""){$cdtim=$colt;}
if ($cssip==""){$cssip=$colt;}
if ($csnik==""){$csnik=$colt;}
if ($conik==""){$conik=$clink;}
if ($cons==""){$cons=$clink;}
if ($coffs==""){$coffs=$colt;}
if ($cdinf==""){$cdinf=$clink;}
if ($cadms==""){$cadms=$colt;}
if ($cctx==""){$cctx=$colt;}
if ($cpfon==""){$cpfon=$fon;}
if ($ccfon==""){$ccfon=$fon;}
?>