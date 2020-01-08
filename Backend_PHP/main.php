<?php
require_once("utils.php");

if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)
  $useragent = 'Internet Explorer';
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE) //For Supporting IE 11
  $useragent = 'Internet Explorer';
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE)
  $useragent = 'Mozilla Firefox';
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE)
  $useragent = 'Google Chrome';
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE)
  $useragent = 'Opera Mini';
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE)
  $useragent = 'Opera';
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE)
  $useragent = 'Safari';
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'Dart') !== FALSE)
  $useragent = 'Android_Dart';
elseif(empty($_SERVER['HTTP_USER_AGENT'])){
  $useragent = 'Cron';
}
else{
  $useragent = 'Outros';
}

date_default_timezone_set('America/Sao_Paulo');
$date = date('d/m/Y H:i:s a', time());
$fp = fopen('log.txt', 'a');//opens file in append mode
if(empty($_SERVER['HTTP_USER_AGENT'])){
  fwrite($fp, 'Acesso feito em '.$date." por: ".$useragent." (Cron)\n");
}else{
  fwrite($fp, 'Acesso feito em '.$date." por: ".$useragent." (".$_SERVER['HTTP_USER_AGENT'].")\n");
}

if(
  ($useragent == "Google Chrome") || ($useragent == "Safari") || ($useragent == "Internet Explorer") ||
  ($useragent == "Opera Mini") || ($useragent == "Opera") || ($useragent == "Outros") || ($useragent == "Mozilla Firefox")){
    print("<br><br><div align='center'><img src='RageFace.jpg' width='45%' height='45%'></img></div>");
    fwrite($fp, "API não foi acessada ".$_GET["assinaturaappandroidsvbr"]."\n");
    fclose($fp);
}else if(($useragent == "Android_Dart") && ($_GET["assinaturaappandroidsvbr"] == 'CHAVE_ACESSO_APP_ANDROID')){
  $API_key = "API_KEY_GOOGLE_YT";

  computePlaylistIdFileOfEachSVBRChannel($API_key); //só usar quando houver membros novos no svbr
  computeRecentVideoFileOfEachSVBRChannel($API_key); //180 de cota
  computeWebserviceOfEachRecentVideosOfSVBRChannel("lastVideos", "recentVideosWebservice.json", $API_key);

  printEachVideoForDebug("recentVideosWebservice.json");
  fwrite($fp, "Acesso à API foi feito! ".$_GET["assinaturaappandroidsvbr"]."\n");
  fclose($fp);
}else{
  fclose($fp);
}
?>
