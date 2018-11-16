 <?
  require_once ($_SERVER['DOCUMENT_ROOT']."/settings.php");
  require_once "Snoopy.class.php";

  $url = $sitehost.'/bbs/rss.php?k='.$_GET['k'].'&b='.$_GET['b'];

  $snoopy= new snoopy;
  $snoopy->fetch($url);
  $txt=str_replace("&","&amp;",$snoopy->results);
  echo $txt;
?>