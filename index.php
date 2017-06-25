<?PHP 
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *  PHP-anonymizer * PHP-анонимайзер * Версия 1.00, 2005-08-23
 *  
 *  Скрипт может быть использован для анонимного серфинга в интернете и экономии 
 *  траффика (при размещении на сервере у провайдера). Не требует установки и 
 *  настройки. Не использует временные файлы. Не требует разрешения на запись. 
 *  Поддержка post-запросов, basic-авторизации, download и upload файлов. 
 *  Работает при safe mode. Поддерживает только HTTP.
 * 
 *  Требования:  PHP4 c разрешенным fsockopen и конфигурацией как модуль Apache.
 *   
 *  Бесплатная загрузка: http://usfree.chat.ru
 *  Автор: Малыгин Александр
 *  Поддержите развитие программы: WebMoney R193471382669
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */ 
####################################### настройки ####################################
// кодировка сервера (раскомментируйте строку, если сервер поддерживает только одну кодировку)
// $charset_default="windows-1251";
// $charset_default="koi8-r";

// кодировка запросов (предпочитаемая кодировка страницы)
$charset_query="windows-1251";

// адрес по умолчанию
$url_default="http://yandex.ru";

// максимальное время работы скрипта
$time_limit=600;

// время кэширования (для отмены установите значение меньше 0)
$expires_default=3600;

// время установки cookie
$cookie_expires=3600;

// массив расширений для обязательного заголовка загрузки
$ras_array=array("zip","rar");

######################################################################################
// массивы необрабатываемых ссылок
// полное совпадение со ссылкой
$no_replace_all_array=array("http://","\\",".");
// совпадение с началом ссылки
$no_replace_left_array=array("+","#","mailto:","javascript:","ftp://","https://");

// максимальное время работы скрипта
if (!ini_get("safe_mode")){set_time_limit($time_limit);}

// кэширование
if ($expires_default>0){
header("Expires: ".gmdate('D, d M Y H:i:s',time()+$expires_default)." GMT");
}
if ($expires_default<0){
header("Expires: Thu, 01 Jan 1970 00:00:01 GMT");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
}

// получение и дешифровка адреса
$session_get=$HTTP_GET_VARS['session_new'];
if ($session_get){
$location_get=$session_get;
$strpos=strpos($session_get,"?");
if ($strpos && substr($session_get,0,7)!="http://"){
$session_get=substr($session_get,0,$strpos+1);
$location_get=decode($session_get);
$session_add=getenv('QUERY_STRING');
$strpos=strpos($session_add,"?");
$location_get=$location_get.substr($session_add,$strpos+1);
$pos1=strpos($location_get,"?");
$pos2=strpos($location_get,"&");
if (($pos1 && $pos2 && $pos2<$pos1) || (!$pos1 && $pos2)){$location_get=preg_replace("#&#si","?",$location_get,1);}
}
$location=$location_get;
}
$session_post=$HTTP_POST_VARS['session_new'];
if ($session_post){
$location_post=decode($session_post);
$location=$location_post;
}
$url_array=url($location,1);
$location=$url_array[0];
$location_code=code($location);

// начальная форма
if (!$location){
$content="<html>
<head>
<title>PHP-анонимайзер (".getenv('SERVER_NAME').")</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=windows-1251\">
</head>
<body>
<br><br>
<form action=\"\" method=\"POST\">
<div align=\"center\">Ссылка: <input type=\"Text\" name=\"session_new\" value=\"http://\">
<input type=\"Submit\" value=\"Вперед >>\">
</font>
</form>
</div>
</body>
</html>";
if ($charset_default){
header("Content-Type: text/html; charset=$charset_default");
$content=decode_charset($content,"windows-1251",$charset_default);
}
echo $content;
exit;
}

// получение referer
if ($HTTP_POST_VARS['referer_new']){$referer_new=decode($HTTP_POST_VARS['referer_new']);}
if ($HTTP_GET_VARS['referer_new']){$referer_new=$HTTP_GET_VARS['referer_new'];}
if (!$referer_new && getenv('HTTP_REFERER')){
$tmp=@parse_url(getenv('HTTP_REFERER'));
parse_str($tmp[query],$tmp);
$referer_new=$tmp[session_new];
if ($referer_new){
$strpos=strpos($referer_new,"?");
if ($strpos && substr($referer_new,0,7)!="http://"){
$find=substr($referer_new,0,$strpos+1);
$replace=decode($find);
$referer_new=str_replace($find,$replace,$referer_new);
}
}
}
if ($referer_new){
$url_array=url($referer_new,0);
$referer_new=$url_array[0];
}

// получение данных www-аутентификации
if ($PHP_AUTH_USER && $PHP_AUTH_PW){$auth_new=base64_encode("$PHP_AUTH_USER:$PHP_AUTH_PW");}

// данные о кодировке
$charset_new=$HTTP_POST_VARS['charset_new'];
if (!$charset_new){$charset_new=$charset_query;}

// запрос
$connection=connection($location);
$message=$connection[0];
$head=$connection[1];
$content=$connection[2];

// установка cookie
$tmp=explode(".",$host_new);
$host_cookie=$tmp[count($tmp)-2]."_".$tmp[count($tmp)-1];
if (stristr($head,"Cookie:")){
preg_match_all("#Set-Cookie: (.*?)=\"?(.*?)\"?(; expires=(.*?))?[;\r]#is",$head,$cookie_array);
$perem_array=$cookie_array[1];
$value_array=$cookie_array[2];
$expires_array=$cookie_array[4];
for ($i=0; $i<count($perem_array); $i++){
if ($expires_array[$i] && strtotime($expires_array[$i],"\n")){$expires_array[$i]=strtotime($expires_array[$i],"\n");} else {$expires_array[$i]=time()+$cookie_expires;}
setcookie ($host_cookie."[".$perem_array[$i]."]",$value_array[$i],$expires_array[$i]);
$HTTP_COOKIE_VARS[$host_cookie][$perem_array[$i]]=$value_array[$i];
}
}

// расширение файла
$content_ras=strtolower(substr($path_new,strrpos($path_new,".")+1));
if (!$content_ras || strlen($content_ras)>4){$content_ras="html";}

// получаем тип данных и кодировку
if (stristr($head,"Content-Type:")){
preg_match("#Content-Type: ([^\"';\s]+);?( charset=([^\"';\s]+))?[\s\r\n]#is",$head,$type);
$content_type=strtolower($type[1]);
$content_charset=strtolower($type[3]);
}
if (!$content_type){$content_type="text/html";}
for($i=0;$i<count($ras_array);$i++){
if ($content_ras==$ras_array[$i]){$content_type="application/octet-stream";}
}
if (!$content_charset){
preg_match("#<meta[\s]http-equiv=\"?'?Content-Type\"?'?.*?charset=(.*?)[\"'\s>]#is",$content,$content_charset);
$content_charset=strtolower($content_charset[1]);
}
if (!$content_charset){$content_charset=$charset_query;}

// заголовок www-аутентификации
if (stristr($head,"WWW-Authenticate:")){
preg_match("#WWW-Authenticate: (.*?)[\r\n]#is",$head,$auth);
header("WWW-Authenticate: $auth[1]");
header("HTTP/1.0 401 Unauthorized");
}

// обработка переадресаций
$refresh="";
if (preg_match("#Location: (.*?)[\s\r\n]#is",$head,$tmp)){
$refresh=parse($tmp[1]);
$refresh="http://".getenv('HTTP_HOST').getenv('SCRIPT_NAME')."?session_new=".code($refresh);
header("Location: $refresh");
}
preg_match_all("#<head>.*?<meta.*?http-equiv=.*?refresh.*?url=(.*?)[\'\" ].*?[>].*?</head>#is",$content,$refresh_array);
replace($refresh_array[0],$refresh_array[0],$refresh_array[1],"?session_new=","");

// длина файла
$content_len=strlen($content);
if (!$content_len){exit;}

// вывод файла
if ($content_type!="text/html" && $content_type!="text/plain" && $content_type!="application/x-javascript"){
if (strrchr($path_new, "/")){$filename=substr(strrchr($path_new, "/"), 1);} else {$filename=$path_new;}
if (preg_match("#(text|image).*?#si",$content_type)){
header("Content-Type: $content_type");
} else {
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=$filename");
header("Accept-Ranges: bytes");
header("Content-length: $content_len");
}
echo $content;
exit;
}

// перекодирование и заголовок кодировки
if ($charset_default){
$content_new=decode_charset($content,$content_charset,$charset_default);
if ($content_new==$content){
$head_charset=$content_charset;
} else {
$head_charset=$charset_default;
$content=$content_new;
}
} else {
$head_charset=$content_charset;
}
if ($head_charset){header("Content-Type: $content_type; charset=$head_charset");}

// содержимое без скриптов
$content_no_script=preg_replace("#<script[^>]*?[>].*?</script>#is","",$content);
// содержимое скриптов
preg_match_all("#<script[^>]*?[>].*?</script>#is",$content,$script_array);
if ($script_array[0]){$content_script=implode("\n",array_unique($script_array[0]));}
if ($content_ras=="js" || $content_type=="application/x-javascript"){$content_script=$content; $content_no_script="";}

// обработка base
preg_match("#<base[\s]*?href=[\"']?(.*?)[\"'\s>]#si",$content,$base_array); 
$base_href=$base_array[1];
if ($base_href){
$url_array=url($base_href,0);
$preffix_new=$url_array[7];
$content=preg_replace("#<base[\s\n]*?href=.*?[>]#si","",$content);
}

// обработка форм
preg_match_all("!(<form[\s>].*?\>|<form>)!is",$content,$form_array); 
$form_array=array_values(array_unique($form_array[0]));
for ($i=0;$i<count($form_array);$i++){
preg_match("! action[\s]*=[\s]*\"?'?(.*?)[\"\'\s>]!is",$form_array[$i],$tmp);
$action_array[$i]=$tmp[1];
if (!$action_array[$i]){$action_array[$i]=$location;}
if (stristr($form_array[$i],"post")){$method_new="POST";} else {$method_new="GET";}
$form_array_new[$i]=preg_replace("' action=.*?([\s>])'si","$1",$form_array[$i]);
$form_array_new[$i]=preg_replace("' method=.*?([\s>])'si","$1",$form_array_new[$i]);
$form_array_new[$i]=preg_replace("'<form'si","<form method=post",$form_array_new[$i]);
$session_code=code($action_array[$i]);
$replace="$form_array_new[$i]
<input type=\"hidden\" name=\"session_new\" value=\"$session_code\">
<input type=\"hidden\" name=\"referer_new\" value=\"$location_code\">
<input type=\"hidden\" name=\"method_new\" value=\"$method_new\">";
if ($charset_default){$replace.="\n<input type=\"hidden\" name=\"charset_new\" value=\"$content_charset\">";}
$content=str_replace($form_array[$i],$replace,$content);
}
preg_match_all("#\.action[\s]*=[\s]*[\"'](.*?)[\"']#is",$content,$script_action_array);
$script_action_replace=str_replace(".action",".session_new.value",$script_action_array[0]);
replace($script_action_array[0],$script_action_replace,$script_action_array[1],"","");

// обработка ссылок
preg_match_all("#(<a|link|area)[\s].*?href[\s]*=[\s]*\\\?\"?'?(.*?)\\\?[\"'\s>]#is",$content,$href_array);
replace($href_array[0],$href_array[0],$href_array[2],"?session_new=","");
preg_match_all("#<(img|embed|frame|iframe|input|script)[\s].*?src[\s]*=[\s]*\\\?\"?'?(.*?)\\\?[\"'\s>]#is",$content,$src_array);
replace($src_array[0],$src_array[0],$src_array[2],"?session_new=","");
preg_match_all("#\.src[\s]*=[\s]*[\"'](.*?)[\"']#is",$content,$script_src_array);
replace($script_src_array[0],$script_src_array[0],$script_src_array[1],"?session_new=","");
preg_match_all("#<(body|table|td)[\s].*?background[\s]*=[\s]*\"?'?(.*?)[\"'\s>]#is",$content,$background_array);
replace($background_array[0],$background_array[0],$background_array[2],"?session_new=","");
preg_match_all("#(location.href|document.location|document.url|parent.location)[\s]*=[\s]*[\"'](.*?)[\"']#is",$content,$location_array);
replace($location_array[0],$location_array[0],$location_array[2],"?session_new=","");
preg_match_all("#(window.open|location.assign)\([\"'](.*?)[\"',\s>\)]#is",$content,$window_array);
replace($window_array[0],$window_array[0],$window_array[2],"?session_new=","");
preg_match_all("#[\"'](http://.*?|http%3A%2F%2F.*?)[\"'\s\)>]#is",$content_script,$http_array);
replace($http_array[0],$http_array[0],$http_array[1],"?session_new=","");

// обработка cookie в скриптах
preg_match_all("#document.cookie[\s]*=[\s]*[\"'](.*?)[\"']#is",$content,$script_cookie_array);
$script_cookie_array=$script_cookie_array[1];
for ($i=0; $i<count($script_cookie_array); $i++){
preg_match("#(.*?)=(.*?)[;\s]#is",$script_cookie_array[$i],$perem_cookie_array);
$find=$perem_cookie_array[1];
if ($find){
$replace=$host_cookie."[".$find."]";
$replace=str_replace("$find=","$replace=",$script_cookie_array[$i]);
$find=$script_cookie_array[$i];
$content=str_replace($find,$replace,$content);
}
}

// обработка имени хоста в скриптах
$host_find=$host_new;
if (substr($host_find,0,4)=="www."){$host_find=substr($host_find,4);}
$host_proxy=getenv('HTTP_HOST');
preg_match_all("#[=(][\s]*[\"'](www\.)?".$host_find."/?[\"']#is",$content,$script_host_array);
$script_host_array=array_unique($script_host_array[0]);
for ($i=0;$i<count($script_host_array);$i++){
$find=$script_host_array[$i];
$replace=str_replace($host_new,$host_proxy,$script_host_array[$i]);
$content=str_replace($find,$replace,$content);
}

// echo "$message\n\n$head\n\n";

// вывод
echo $content;

################################# функция соединения ################################
function connection($location){
global $HTTP_POST_VARS,$HTTP_COOKIE_VARS,$HTTP_POST_FILES,$referer_new,$auth_new,$charset_default;

$url_array=url($location,0);
$scheme=$url_array[1];
$method=$url_array[2];
$host=$url_array[3];
$port=$url_array[4];
$path=$url_array[5];
$get=$url_array[6];
$referer=$referer_new;
$auth=$auth_new;
$charset=$charset_default;

// обработка и кодирование GET, POST
$post=mass($HTTP_POST_VARS,1,"\$perem=\$value&");
if ($method=="GET" && $post){$get="$get&$post"; $post="";}
if ($get){$get="?$get";}

// обработка и кодирование COOKIE
$tmp=explode(".",$host); $host_cookie=$tmp[count($tmp)-2]."_".$tmp[count($tmp)-1];
$mass=$HTTP_COOKIE_VARS[$host_cookie];
$cookie=mass($mass,1,"\$perem=\$value; ");
if (substr($cookie,-2)=="; "){$cookie=substr($cookie,0,-2);}

// обработка multipart/form-data
$type=getenv('CONTENT_TYPE');
if ($type && stristr($type,"multipart/form-data")){
preg_match("#boundary=(.*)#si",$type,$boundary);
$boundary=$boundary[1];
$post=mass($HTTP_POST_VARS,0,"--$boundary\r\nContent-Disposition: form-data; name=\"\$perem\"\r\n\r\n\$value\r\n");
// поддержка upload
$mass=$HTTP_POST_FILES;
if ($mass){
reset($mass);
for ($i=0; $i<count($mass); $i++){
$perem=key($mass);
$filename=$mass[$perem][name];
$filetype=$mass[$perem][type];
if ($filename){
global ${"$perem"};
$filecontent=implode("",file(${"$perem"}));
$post.="--$boundary\r\nContent-Disposition: form-data; name=\"$perem\"; filename=\"$filename\"\r\nContent-Type: $filetype\r\n\r\n$filecontent\r\n";
}
}
}
$post.="--$boundary--";
}

// формирование заголовка запроса
$message="$method $path$get HTTP/1.0\r\n";
$message.="Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/vnd.ms-excel, application/msword, application/x-shockwave-flash, */*\r\n";
if ($referer){$message.="Referer: $referer\r\n";}
$message.="Accept-Language: ru\r\n";
if ($charset){$message.="Accept-Charset: $charset\r\n";}
if ($auth){$message.="Authorization: Basic $auth\r\n";}
if ($post && $boundary){$message.="Content-Type: multipart/form-data; boundary=$boundary\r\n";}
if ($post && !$boundary){$message.="Content-Type: application/x-www-form-urlencoded\r\n";}
$message.="User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)\r\n";
$message.="Host: $host\r\n";
if ($post){$post="\r\n$post"; $message.="Content-length: ".strlen($post)."\r\n";}
$message.="Connection: close\r\n";
$message.="Cache-Control: no-cache\r\n";
$message.="Pragma: no-cache\r\n";
if ($cookie){$message.="Cookie: $cookie\r\n";}
if ($post){$message.="$post\r\n";}
$message.="\r\n";
//echo $message; // exit;

// запрос
$fp=fsockopen($host,$port,$errno,$errstr);
if (!$fp){echo "Нет связи с сервером. Повторите попытку позднее. $errno ($errstr).";}
fputs($fp,$message);
while(!feof($fp)){$fgets=fgets($fp,2048); if ($fgets=="\r\n" || $fgets=="\n"){break;} $head.=$fgets;}
while(!feof($fp)){$content.=fread($fp,2048);}
fclose($fp);
// echo $head;
// echo $content;

return array($message,$head,$content);
}

############################ функция обработки адреса ###############################
function url($location,$save){
if (!$location){return;}
global $url_default,$scheme_new,$method_new,$host_new,$port_new,$path_new,$preffix_new;
// установки по умолчанию
$url=@parse_url($url_default);
$scheme_default=$url[scheme];
$host_default=$url[host];
$path_default=$url[path];
$method_default="GET";
$port_default=80;
// разбор адреса
$url=preg_replace("#://?#si","://",$location,1);
$url=@parse_url($url);
$scheme=$url[scheme];
if (!$scheme){$scheme=$scheme_new;}
if (!$scheme){$scheme=$scheme_default;}
$method=$method_new;
if (!$method){$method=$method_new;}
if (!$method){$method=$method_default;}
$host=$url[host];
if (!$host){$host=$host_new;}
if (!$host){$host=$host_default;}
$port=$url[port];
if (!$port){$port=$port_new;}
if (!$port){$port=$port_default;}
$path=$url[path];
$path=str_replace("\\","/",$path);
$path="/$path";
$path=preg_replace("'/{2,100}'si","/",$path);
// обработка /./ и /../
for ($i=0;$i<10;$i++){$path=preg_replace("#/[^\./][^/]+/\.\.?/#si","/",$path);}
for ($i=0;$i<10;$i++){$path=preg_replace("#/\.\.?/#si","/",$path);}
$get=$url[query];
$get=get($get);
$preffix=$scheme."://".$host.substr($path,0,strrpos($path,"/"))."/";
$location=$scheme."://".$host.$path;
if ($get){$location.="?$get";}
// обновление переменных
if ($save){
$scheme_new=$scheme;
$method_new=$method;
$host_new=$host;
$port_new=$port;
$path_new=$path;
$preffix_new=$preffix;
$location_new=$location;
}
// вывод
return array($location,$scheme,$method,$host,$port,$path,$get,$preffix);
}
############################ функция обработки ссылок ###############################
function parse($url){
global $scheme_new,$host_new,$preffix_new;
// получение полного адреса
if (!$url || $url=="http://" || $url=="\\"){return $preffix_new;}
if (substr($url,0,1)=="/"){
$url=$scheme_new."://".$host_new.$url;
} else {
$tmp=@parse_url($url);
if ($tmp[scheme]!="http" && $tmp[scheme]!="https" && $tmp[scheme]!="ftp"){$url=$preffix_new.$url;}
}
return $url;
}
################################## функция кодирования ##############################
function code($str){
if (!$str){return;}
$str=parse($str);
$str=base64_encode($str);
$str=str_replace("=","$",$str);
$code="$str?";
return $code;
}
################################ функция декодирования ##############################
function decode($code){
if (!$code){return;}
if (substr($code,0,7)=="http://"){return $code;}
if (substr($code,-1)=="?"){$code=substr($code,0,-1);}
$code=str_replace("$","=",$code);
$str=base64_decode($code);
return $str;
}
################################# функция замены ссылок #############################
function replace($mass1,$mass2,$mass3,$left,$right){
global $content,$no_replace_all_array,$no_replace_left_array;
for ($i=0;$i<count($mass1);$i++){
$mass3[$i]=trim($mass3[$i]);
if ($mass3[$i]){
$sovp=0;
for ($j=0;$j<count($no_replace_all_array);$j++){if ($no_replace_all_array[$j]==$mass3[$i]){$sovp=1;}}
for ($j=0;$j<count($no_replace_left_array);$j++){if ($no_replace_left_array[$j]==substr($mass3[$i],0,strlen($no_replace_left_array[$j]))){$sovp=1;}}
if (!$sovp){
$replace=$mass3[$i];
$replace=$left.code($replace).$right;
$replace=str_replace($mass3[$i],$replace,$mass2[$i]);
$content=str_replace($mass1[$i],$replace,$content);
}
}
}
}

################################# функция обработки GET #############################
function get($get){
if (!$get){return;}
global $charset_default,$charset_new;
// декодирование HTML-мнемоник
$get=str_replace("&amp;","&",$get);
$get=str_replace("&quot;","\"",$get);
$get=str_replace("&#039;","'",$get);
$get=str_replace("&lt;","<",$get);
$get=str_replace("&gt;",">",$get);
// url-кодирование
preg_match_all("#=([^&;]+)#is",$get,$mass);
$mass=$mass[1];
for ($i=0; $i<count($mass); $i++){
$find=$replace=$mass[$i];
$replace=urldecode($replace);
if ($charset_default){$replace=decode_charset($replace,$charset_default,$charset_new);}
$replace=urlencode(stripslashes($replace));
$get=str_replace("=$find","=$replace",$get);
}
// декодирование символов
$get=str_replace("%3A",":",$get);
$get=str_replace("%40","@",$get);
return $get;
}

######################## поиск ключей многомерного массива #########################
function keys($mass,$c,$d){
global $a,$b;
$keys=array_keys($mass);
for($i=0;$i<count($keys);$i++){
$perem=$d[$c]=$b[$a][$c]=$keys[$i];
$value=$mass[$perem];
for($j=1;$j<$c;$j++){$b[$a][$j]=$d[$j];}
if (is_array($value)){$c++; keys($value,$c,$d); $c--;} else {$a++;}
}
}

############################ функция обработки POST/COOKIE ##########################
function mass($mass,$code,$repl){
if (!$mass){return;}
global $charset_default,$charset_new;
global $a,$b;
$a=0; $b="";
// поиск ключей массива
keys($mass,1,null);
// формирование запроса
for($i=0;$i<count($b);$i++){
$perem="";
$value=$mass;
for($j=1;$j<=count($b[$i]);$j++){
if ($j==1){$perem.=$b[$i][$j];} else {$perem.="[".$b[$i][$j]."]";}
$value=$value[$b[$i][$j]];
}
if ($perem!="session_new" && $perem!="referer_new" && $perem!="method_new" && $perem!="charset_new"){
$value=urldecode($value);
if ($charset_default){$value=decode_charset($value,$charset_default,$charset_new);}
if ($code){$value=urlencode(stripslashes($value));}
$new=str_replace("\$perem",$perem,$repl);
$new=str_replace("\$value",$value,$new);
$zapr.=$new;
}
}
// декодирование символов
$zapr=str_replace("%3A",":",$zapr);
$zapr=str_replace("%40","@",$zapr);
return $zapr;
}
######################### функция конвертирования кирилицы #########################
function decode_charset($content,$code,$decode){
if (!$content || !$code || !$decode || $code==$decode){return $content;}
$charset=array("koi8-r"=>"k","windows-1251"=>"w","iso8859-5"=>"i","x-cp866"=>"a","x-mac-cyrillic"=>"m","utf-8"=>"u");
$from=$charset[$code];
$to=$charset[$decode];
if (!$from || !$to){return $content;}
if ($from=="u"){
$content=convert_utf_string($content,"w");
$content=convert_cyr_string ($content,"w",$to);
} elseif ($to=="u"){
$content=convert_cyr_string ($content,$from,"w");
$content=convert_utf_string($content,"u");
} else {
$content=convert_cyr_string ($content,$from,$to);
}
return $content;
}
########################### функция конвертирования utf-8 ##########################
function convert_utf_string($str,$type){
// w - encodes from UTF to win ; u - encodes from win to UTF 
static $conv='';
if(!is_array($conv)){  
$conv=array();
for ($x=128;$x<=143; $x++){
$conv['utf'][]=chr(209).chr($x);
$conv['win'][]=chr($x+112);
}
for ($x=144; $x<=191; $x++){
$conv['utf'][]=chr(208).chr($x);
$conv['win'][]=chr($x+48);
}
$conv['utf'][]=chr(208).chr(129);
$conv['win'][]=chr(168);
$conv['utf'][]=chr(209).chr(145);
$conv['win'][]=chr(184);
}
if ($type=='w'){
return str_replace($conv['utf'],$conv['win'],$str );
} elseif ($type=='u'){
return str_replace ($conv['win'],$conv['utf'],$str);
} else {
return $str;
}
}
#####################################################################################

?>
