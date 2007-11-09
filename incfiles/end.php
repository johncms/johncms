<?php
defined('_IN_PUSTO') or die('Error:restricted access');

if ($headmod!="mainpage"){


echo '<a href=\''.$home.'\'>На главную</a><br/>';}
 echo "</div><div class='a'>";
 $ontime=$realtime-300;
$qon = @mysql_query("select * from `users` where lastdate>='".intval($ontime)."';");
$qon2 = mysql_num_rows($qon);

if (!empty($_SESSION['pid'])){
echo '<center><a href=\''.$home.'/str/online.php\'>Онлайн: '.$qon2.'</a></center>';}else{
echo'<center>Онлайн: '.$qon2.'</center>';}

 if (!empty($_SESSION['pid'])){
$prh = @mysql_query("select * from `count` where time>='".intval($datauser[sestime])."' and name='".$login."';");
$prh1=mysql_num_rows($prh);
$svr=$realtime-$datauser[sestime];
if ($svr>="3600"){
$hvr=ceil($svr/3600)-1;if($hvr<10){$hvr="0$hvr";}
$svr1=$svr-$hvr*3600;
$mvr=ceil($svr1/60)-1;if($mvr<10){$mvr="0$mvr";}
$ivr=$svr1-$mvr*60;if($ivr<10){$ivr="0$ivr";}
if ($ivr=="60"){$ivr="59";}
$sitevr="$hvr:$mvr:$ivr";}
else{ if ($svr>="60"){$mvr=ceil($svr/60)-1;
if($mvr<10){$mvr="0$mvr";}
$ivr=$svr-$mvr*60;
if($ivr<10){$ivr="0$ivr";}
if ($ivr=="60"){$ivr="59";}
$sitevr="00:$mvr:$ivr";}else{
$ivr=$svr;
if($ivr<10){$ivr="0$ivr";}
$sitevr="00:00:$ivr";}}
echo'<center>['.$prh1.' - '.$sitevr.']</center>';}



  
if ($gzip=="1")
  {

    $Contents = ob_get_contents();
    $gzib_file = strlen($Contents); 
    $gzib_file_out = strlen(gzcompress($Contents,9));
    $gzib_pro=round(100-(100/($gzib_file/$gzib_file_out)),1);

    echo'<center>Cжатие вкл.('.$gzib_pro.'%)</center>';
    
  }
if ($gzip=="0")
  {

      echo'<center>Cжатие выкл.</center>';
  }
  
echo "</div>";  
if (empty($_SESSION['pid'])||$pereh!=1){
echo "<div class='a'><div class='e'>Перейти:<br/><form action='".$home."/go.php' method='post'><select name='adres'>";
if (!empty($_SESSION['pid'])){
echo "<option value='privat'>Приват</option><option value='set'>Настройки</option><option value='prof'>Анкета</option><option value='chat'>Чат</option>";}
echo "<option value='guest'>Гостевая</option><option value='forum'>Форум:</option>";
$fr=@mysql_query("select * from `forum` where type='f';");
while($fr1=mysql_fetch_array($fr)){

echo "<option value='frm.".$fr1[id]."'>&nbsp;&quot; $fr1[text]&quot;</option>";}
echo "<option value='news'>Новости</option><option value='gallery'>Галерея</option><option value='down'>Загрузки</option><option value='upl'>Обменник</option><option value='lib'>Библиотека</option></select><br/><input type='submit' value='Go!'/><br/></form></div></div>";}
echo "<div class='a'>";
if ($headmod=="mainpage"){
echo "<br/><center><a href='http://waplog.net/ru/c.shtml?11641'><img src='http://c.waplog.net/ru/11641.cnt' alt='' width='72' height='25'/></a></center><br/>";
echo'<center><b>&#169;'.$copyright.'</b><br/>2007<br/>Все права защищены<br/></center>';
}else{
echo "<br/><center><a href='http://waplog.net/ru/c.shtml?9044'><img src='http://c.waplog.net/ru/9044.cnt' alt='' width='72' height='15'/></a></center><br/>";
}



echo'</div>';
echo'</body></html>';

ob_end_flush();
exit;
?>