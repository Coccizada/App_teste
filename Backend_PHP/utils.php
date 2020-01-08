<?php

define("HOURS_TO_WAIT_UNTIL_UPDATE", 2);
define("MINUTES_TO_WAIT_UNTIL_UPDATE", 17);
define("SECONDS_TO_WAIT_UNTIL_UPDATE", 24);

require_once 'vendor/autoload.php';
use Sunra\PhpSimple\HtmlDomParser;

function getIdOfSVBRchannels($htmlFileOfSVBRChannels){
  //código html dessa página: https://www.youtube.com/channel/UCqiD87j08pe5NYPZ-ncZw2w/channels?view=60&shelf_id=0
  if(!file_exists($htmlFileOfSVBRChannels)){
    exit("Erro, não foi encontrado o arquivo ".$htmlFileOfSVBRChannels." na pasta do projeto.");
  }

  $url = $htmlFileOfSVBRChannels;

  $context = stream_context_create(array('http' => array('header' => 'User-Agent: Mozilla compatible')));
  $response = file_get_contents($url, false, $context);
  $html = HtmlDomParser::str_get_html($response);

  $temp = new stdClass;
  foreach($html->find('a[id="channel-info"]') as $eachChannel) {
    $channelId = str_replace("/channel/", "", $eachChannel->href);
    $channelName = $eachChannel->find('span[id="title"]')[0]->innertext;

    $temp->{$channelName} = $channelId;
  }

  $channelsArray = (array) $temp;
  return $channelsArray;
}

function getPlaylistIdByUsername($channelUsername, $API_key){
  $webServiceYoutubeUrl = "https://www.googleapis.com/youtube/v3/channels?part=contentDetails&key=".$API_key."&forUsername=".$channelUsername;
  $objectReturned = json_decode(file_get_contents($webServiceYoutubeUrl));
  $playlistId = $objectReturned->{'items'}[0]->{'contentDetails'}->{'relatedPlaylists'}->{'uploads'};

  return $playlistId;
}

function getPlaylistIdById($channelId, $API_key){
  $webServiceYoutubeUrl = "https://www.googleapis.com/youtube/v3/channels?part=contentDetails&key=".$API_key."&id=".$channelId;
  $objectReturned = json_decode(file_get_contents($webServiceYoutubeUrl));
  $playlistId = $objectReturned->{'items'}[0]->{'contentDetails'}->{'relatedPlaylists'}->{'uploads'};

  return $playlistId;
}

//essa função usa da cota da API do YouTube
function serializeUploadedPlaylistIdOfEachSVBRChannel($API_key){
  $channelsById = getIdOfSVBRchannels(); //sem custo de cota do youtube

  $channelsByUploadedPlaylistId = [];
  foreach ($channelsById as $name => $id){
    //print("Salvando em arquivo o id da playlist do canal ".$name."<br>");
    $obj["name"] = $name;
    $obj["id"] = $id;
    $obj["playlistId"] = getPlaylistIdById($id, $API_key);

    array_push($channelsByUploadedPlaylistId, $obj);
  }

  //print($channelsByUploadedPlaylistId[0]["name"]);
  $contentUploadedPlaylistLog = serialize($channelsByUploadedPlaylistId);
  file_put_contents("playlists", $contentUploadedPlaylistLog);
}

function serializeLastVideosOfEachSVBRChannel($filenameOfUploadedPlaylistLog, $filenameOfRecentVideosLog, $API_key){
  if(!file_exists($filenameOfUploadedPlaylistLog)){
    exit("Erro, não foi encontrado o arquivo ".$filenameOfUploadedPlaylistLog." na pasta do projeto.");
  }

  $s = file_get_contents($filenameOfUploadedPlaylistLog);
  $channelsByUploadedPlaylistId = unserialize($s);

  $dataFromLastVideos = [];

  foreach ($channelsByUploadedPlaylistId as $key => $value){
    $webServiceYoutubeUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&key=".$API_key."&playlistId=".$value["playlistId"];
    $obj = json_decode(file_get_contents($webServiceYoutubeUrl));

    array_push($dataFromLastVideos, $obj);
  }

  $s = serialize($dataFromLastVideos);
  file_put_contents($filenameOfRecentVideosLog, $s);
}

function computeJSONOfSortedArrayOfRecentVideos($filenameOfRecentVideosLog, $serializedJsonOfRecentVideos){
  if(!file_exists($filenameOfRecentVideosLog)){
    exit("Erro, não foi encontrado o arquivo ".$filenameOfRecentVideosLog." na pasta do projeto.");
  }

  $s = file_get_contents($filenameOfRecentVideosLog);
  $dataFromLastVideos = unserialize($s);

  $arrayOfRecentVideos = [];
  $arrayToSortVideos = [];
  foreach ($dataFromLastVideos as $key => $channelContent){
    foreach ($channelContent as $key_ => $lastVideosContents){
      if(strcmp($key_, "items") == 0){
        foreach ($lastVideosContents as $key__ => $eachVideoContent){
          /* Criando vetor com os dados fundamentais de cada vídeo */
          $tempEachVideo["publishedDate"] = $eachVideoContent->{'snippet'}->{'publishedAt'};
          $tempEachVideo["channelId"] = $eachVideoContent->{'snippet'}->{'channelId'};
          $tempEachVideo["channelName"] = $eachVideoContent->{'snippet'}->{'channelTitle'};
          $tempEachVideo["videoUrl"] = "https://www.youtube.com/watch?v=".$eachVideoContent->{'snippet'}->{'resourceId'}->{'videoId'};
          $tempEachVideo["videoTitle"] = $eachVideoContent->{'snippet'}->{'title'};
          $tempEachVideo["videoTitle"] = str_replace("\"", "", $tempEachVideo["videoTitle"]);
          $tempEachVideo["videoDescription"] = $eachVideoContent->{'snippet'}->{'description'};
          $tempEachVideo["videoDescription"] = str_replace("\"", "", $tempEachVideo["videoDescription"]);

          $tempEachVideo["thumbnailUrl"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'maxres'}->{'url'};
          $tempEachVideo["thumbnailWidth"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'maxres'}->{'width'};
          $tempEachVideo["thumbnailHeight"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'maxres'}->{'height'};
          if(!isset($tempEachVideo["thumbnailUrl"])){
            $tempEachVideo["thumbnailUrl"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'standard'}->{'url'};
            $tempEachVideo["thumbnailWidth"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'standard'}->{'width'};
            $tempEachVideo["thumbnailHeight"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'standard'}->{'height'};
          }
          if(!isset($tempEachVideo["thumbnailUrl"])){
            $tempEachVideo["thumbnailUrl"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'high'}->{'url'};
            $tempEachVideo["thumbnailWidth"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'high'}->{'width'};
            $tempEachVideo["thumbnailHeight"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'high'}->{'height'};
          }
          if(!isset($tempEachVideo["thumbnailUrl"])){
            $tempEachVideo["thumbnailUrl"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'medium'}->{'url'};
            $tempEachVideo["thumbnailWidth"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'medium'}->{'width'};
            $tempEachVideo["thumbnailHeight"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'medium'}->{'height'};
          }
          if(!isset($tempEachVideo["thumbnailUrl"])){
            $tempEachVideo["thumbnailUrl"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'default'}->{'url'};
            $tempEachVideo["thumbnailWidth"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'default'}->{'width'};
            $tempEachVideo["thumbnailHeight"] = $eachVideoContent->{'snippet'}->{'thumbnails'}->{'default'}->{'height'};
          }

          array_push($arrayOfRecentVideos, $tempEachVideo);

          /* Criando vetor que vai servir pra colocar os vídeos em ordem */
          $videoId = $eachVideoContent->{"snippet"}->{"resourceId"}->{"videoId"};
          $arrayToSortVideos[$videoId] = strtotime($tempEachVideo["publishedDate"]);
        }
      }
    }
  }

  arsort($arrayToSortVideos);

  $sortedArrayOfRecentVideos = array_fill(0, count($arrayToSortVideos), $arrayOfRecentVideos[0]);
  $indexOfVideoInOriginalArray = 0;
  foreach ($arrayToSortVideos as $videoId => $timestamp){
    $videoIdOfOriginalArray = str_replace("https://www.youtube.com/watch?v=", "", $arrayOfRecentVideos[$indexOfVideoInOriginalArray]["videoUrl"]);
    $indexOfVideoIdOfOriginalArrayInSortedArray = array_search($videoIdOfOriginalArray, array_keys($arrayToSortVideos));

    $sortedArrayOfRecentVideos[$indexOfVideoIdOfOriginalArrayInSortedArray] = $arrayOfRecentVideos[$indexOfVideoInOriginalArray];
    $indexOfVideoInOriginalArray++;
  }

  //var_dump($sortedArrayOfRecentVideos);

  $json = json_encode($sortedArrayOfRecentVideos);
  $fp = fopen($serializedJsonOfRecentVideos, 'w');
  fwrite($fp, utf8_encode(json_encode($json)));
  fclose($fp);
}

function computePlaylistIdFileOfEachSVBRChannel($API_key){
  if (!file_exists("playlists")) {
    serializeUploadedPlaylistIdOfEachSVBRChannel($API_key);
  }
}

function computeRecentVideoFileOfEachSVBRChannel($API_key){
  if(!file_exists("lastVideos")){
    serializeLastVideosOfEachSVBRChannel("playlists", "lastVideos", $API_key);
  }
}

function forceComputeWebserviceOfEachRecentVideosOfSVBRChannel($filenameOfRecentVideosLog, $jsonFile, $API_key){
  if(!file_exists($filenameOfRecentVideosLog)){
    exit("Erro, não foi encontrado o arquivo ".$filenameOfRecentVideosLog." na pasta do projeto.");
  }
    $startTime = microtime(true);
    unlink($filenameOfRecentVideosLog);
    unlink($jsonFile);
    computeRecentVideoFileOfEachSVBRChannel($API_key); //180 de cota
    computeJSONOfSortedArrayOfRecentVideos($filenameOfRecentVideosLog, $jsonFile);
    print("Atualizado! ");
    print("Time:  ".number_format((microtime(true) - $startTime), 4) . " Seconds\n");
}

function computeWebserviceOfEachRecentVideosOfSVBRChannel($filenameOfRecentVideosLog, $jsonFile, $API_key){
  if(!file_exists($filenameOfRecentVideosLog)){
    exit("Erro, não foi encontrado o arquivo ".$filenameOfRecentVideosLog." na pasta do projeto.");
  }

  if(!file_exists($jsonFile)){
    computeJSONOfSortedArrayOfRecentVideos($filenameOfRecentVideosLog, $jsonFile);
  }else{
    $today = new DateTime(date("Y-m-d H:i:s.u"));
    $today->setTimezone(new DateTimeZone('America/Sao_Paulo'));

    $lastUpdateOfWebserviceJson = new DateTime(date("Y-m-d H:i:s.u", filemtime($jsonFile)));
    $lastUpdateOfWebserviceJson->setTimezone(new DateTimeZone('America/Sao_Paulo'));

    $intervalBetweenDates = date_diff($today, $lastUpdateOfWebserviceJson);

    $timestampDiff = $intervalBetweenDates->h * 60 * 60 + $intervalBetweenDates->i * 60 + $intervalBetweenDates->s;
    $timestampDiff -= (HOURS_TO_WAIT_UNTIL_UPDATE * 60 * 60 + MINUTES_TO_WAIT_UNTIL_UPDATE * 60 + SECONDS_TO_WAIT_UNTIL_UPDATE);
    //2h17m24s

    if ($timestampDiff > 0){
      print("Atualizado! ");
      print("Faltam ".HOURS_TO_WAIT_UNTIL_UPDATE." horas, ".MINUTES_TO_WAIT_UNTIL_UPDATE." minutos e ".SECONDS_TO_WAIT_UNTIL_UPDATE." segundos para procurar novos vídeos dos canais SVBR.<br>");
      //normalmente ele precisa de 23 segundos pra concluir essa operação

      //$startTime = microtime(true);
      unlink($filenameOfRecentVideosLog);
      unlink($jsonFile);
      computeRecentVideoFileOfEachSVBRChannel($API_key); //180 de cota
      computeJSONOfSortedArrayOfRecentVideos($filenameOfRecentVideosLog, $jsonFile);
      //print("Time:  ".number_format((microtime(true) - $startTime), 4) . " Seconds\n");
    }else{
      $timestampDiff = abs($timestampDiff);
      $hoursToWait = intdiv($timestampDiff, 60 * 60);
      $timestampDiff -= abs($hoursToWait * 60 * 60);
      $minutesToWait = intdiv($timestampDiff, 60);
      $timestampDiff -= abs($minutesToWait * 60);
      $secondsToWait = $timestampDiff;

      print("Faltam ".$hoursToWait." horas, ".$minutesToWait." minutos e ".$secondsToWait." segundos para procurar novos vídeos dos canais SVBR.<br>");
    }
  }
}

function Utf8_ansi($valor='') {
    $utf8_ansi2 = array(
    "\u00c0" =>"À",
    "A\\\\u0301" =>"Á",
    "\u00c1" =>"Á",
    "\u00c2" =>"Â",
    "\u00c3" =>"Ã",
    "A\\\\u0303" => "Ã",
    "\u00c4" =>"Ä",
    "\u00c5" =>"Å",
    "\u00c6" =>"Æ",
    "\u00c7" =>"Ç",
    "\u00c8" =>"È",
    "\u00c9" =>"É",
    "E\\\\u0301" =>"É",
    "\u00ca" =>"Ê",
    "E\\\\u0302" => "Ê",
    "\u00cb" =>"Ë",
    "\u00cc" =>"Ì",
    "I\\\\u0301" =>"Í",
    "\u00cd" =>"Í",
    "\u00ce" =>"Î",
    "\u00cf" =>"Ï",
    "\u00d1" =>"Ñ",
    "\u00d2" =>"Ò",
    "\u00d3" =>"Ó",
    "\u00d4" =>"Ô",
    "O\\\\u0302" =>"Ô",
    "O\u00d5" =>"Õ",
    "O\\\\u0303" =>"Õ",
    "\u00d6" =>"Ö",
    "\u00d8" =>"Ø",
    "\u00d9" =>"Ù",
    "\u00da" =>"Ú",
    "U\\\\u0301" => "Ú",
    "\u00db" =>"Û",
    "\u00dc" =>"Ü",
    "\u00dd" =>"Ý",
    "\u00df" =>"ß",
    "\u00e0" =>"à",
    "\u00e1" =>"á",
    "\u00e2" =>"â",
    "\u00e3" =>"ã",
    "a\\\\u0303"=>"ã",
    "\u00e4" =>"ä",
    "\u00e5" =>"å",
    "\u00e6" =>"æ",
    "\u00e7" =>"ç",
    "\u00e8" =>"è",
    "\u00e9" =>"é",
    "\u00ea" =>"ê",
    "e\\\\u0303e" => "ê",
    "\u00eb" =>"ë",
    "\u00ec" =>"ì",
    "\u00ed" =>"í",
    "i\\\\u0301" => "í",
    "\u00ee" =>"î",
    "\u00ef" =>"ï",
    "\u00f0" =>"ð",
    "\u00f1" =>"ñ",
    "\u00f2" =>"ò",
    "\u00f3" =>"ó",
    "\u00f4" =>"ô",
    "\u00f5" =>"õ",
    "\u00f6" =>"ö",
    "\u00f8" =>"ø",
    "\u00f9" =>"ù",
    "\u00fa" =>"ú",
    "u\\\\u0301" => "ú",
    "\u00fb" =>"û",
    "\u00fc" =>"ü",
    "\u00fd" =>"ý",
    "\u00ff" =>"ÿ",
    "\\\\u00aa" => "a");

    $cleanStr = strtr($valor, $utf8_ansi2);
    $cleanStr = stripslashes($cleanStr);
    $cleanStr = str_replace("\\n", "&lt;br&gt;", $cleanStr);
    $cleanStr = str_replace("\\r", "", $cleanStr);
    $cleanStr = str_replace("\"[{", "[{", $cleanStr);
    $cleanStr = str_replace("}]\"", "}]", $cleanStr);
    $cleanStr = str_replace("\\\"", "\"", $cleanStr);
    $cleanStr = str_replace("\\/", "/", $cleanStr);
    $cleanStr = str_replace("\\\\", "", $cleanStr);
    $cleanStr = str_replace("\\", "", $cleanStr);
    $cleanStr = str_replace("u00a7", " ", $cleanStr);
    $cleanStr = str_replace("ud83dudcd6", " ", $cleanStr);
    $cleanStr = str_replace("ud83dudce8", " ", $cleanStr);
    $cleanStr = str_replace("ud83dudca1", " ", $cleanStr);
    $cleanStr = str_replace("u27a2", " ", $cleanStr);
    $cleanStr = str_replace("u25ba", " ", $cleanStr);
    $cleanStr = str_replace("u2615ufe0f", " ", $cleanStr);
    $cleanStr = str_replace("u201c", "", $cleanStr);
    $cleanStr = str_replace("u201d", "", $cleanStr);
    $cleanStr = str_replace("u2022", "-", $cleanStr);
    $cleanStr = str_replace("u00b2", "2", $cleanStr);
    $cleanStr = str_replace("u2014", "-", $cleanStr);
    $cleanStr = str_replace("u25b6", "", $cleanStr);
    $cleanStr = str_replace("\"&lt", "&lt;", $cleanStr);
    $cleanStr = str_replace("&gt;\"", "&gt;", $cleanStr);

    return $cleanStr;
}

function printEachVideoForDebug($jsonFile){
  if(!file_exists($jsonFile)){
    exit("Erro, não foi encontrado o arquivo ".$jsonFile." na pasta do projeto.");
  }

  $jsonObject = file_get_contents($jsonFile);
  $jsonObject = Utf8_ansi($jsonObject);

  $jsonTemp = file_get_contents($jsonFile);
  $jsonTemp = Utf8_ansi($jsonTemp);

  $sortedArrayOfRecentVideos = json_decode($jsonTemp);
  $tempArray = [];
  //var_dump($sortedArrayOfRecentVideos);
  $i = 0;
  foreach ($sortedArrayOfRecentVideos as $key => $value){
    array_push($tempArray, $sortedArrayOfRecentVideos[$i]);
    $date = strtotime($value->{'publishedDate'});
    $tempArray[$i]->{'publishedDate'} = date('d/M/Y', $date)." às ".date('H:i', $date);

    $i++;
    if($i >= 100){
      break;
    }
  }
  //var_dump($tempArray);
  $jsonTemp = json_encode($tempArray);

  //save json for flutter app
  $fp = fopen('jsonForFlutter.json', 'w');
  fwrite($fp, pack("CCC",0xef,0xbb,0xbf));
  fwrite($fp, $jsonTemp);
  /*if (mb_check_encoding(file_get_contents($fp), 'UTF-8')) {
    printf("Esse arquivo foi salvo como utf-8");
  }*/
  fclose($fp);

  switch (json_last_error()) {
    case JSON_ERROR_NONE:
        //echo ' - No errors<br>';
    break;
    case JSON_ERROR_DEPTH:
        echo ' - Maximum stack depth exceeded';
    break;
    case JSON_ERROR_STATE_MISMATCH:
        echo ' - Underflow or the modes mismatch';
    break;
    case JSON_ERROR_CTRL_CHAR:
        echo ' - Unexpected control character found';
    break;
    case JSON_ERROR_SYNTAX:
        echo ' - Syntax error, malformed JSON';
    break;
    case JSON_ERROR_UTF8:
        echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
    break;
    default:
        echo ' - Unknown error';
    break;
  }
  $sortedArrayOfRecentVideos = json_decode($jsonObject);
  foreach ($sortedArrayOfRecentVideos as $key => $value){
    //print($value->{'channelName'}."--------".$value->{'thumbnailWidth'}."------".$value->{'thumbnailHeight'}."<br><br>");
    print("<div align='center'>");
    print("<a href=".$value->{'videoUrl'}.">");
    print("<img src=".$value->{'thumbnailUrl'}." width='210' height='118'></img>");
    print("</a>");
    print("<br>".$value->{'videoTitle'});
    print("<br>".$value->{'channelName'});
    //print("<br>".$value->{'publishedDate'});
    //var_dump($value->{'publishedDate'});
    $date = strtotime($value->{'publishedDate'});
    print("<br>Publicado em: ".date('d/M/Y', $date)." às ".date('H:i:s', $date));
    print("</div><br><br>");
    //$date->getTimestamp();
  }
}
?>
