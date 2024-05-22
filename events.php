<?php 
$eventsDir=__DIR__."/"."events/";


date_default_timezone_set('Europe/Vienna');
session_start([
    'cookie_lifetime' => 86400
]);
$userName=checkLogin();


function checkLogin(){
	if(isset($_SESSION["kalenterlogin"]) && $_SESSION["kalenterlogin"]){
		return $_SESSION["kalenterlogin"];
	}else{
		return false;
	}
}
function getEvent($eName){
	global $eventsDir;
	if(!file_exists($eventsDir.$eName)){
		return false;
	}
	$fullObj=json_decode(file_get_contents($eventsDir.$eName),true);
	if(checkLogin())
		return $fullObj;
	if(isset($fullObj["isPublic"])&&$fullObj["isPublic"])
		return $fullObj;
	$return=array(
		"start"=>$fullObj["start"],
		"ende"=>$fullObj["ende"],
		"name"=>"",
		"description"=>"(ausgeblendet)"
	);
	return $return;
}
function getEventsOn($day){
	$allEvents=get2AllEvents();
	$ret=array();
	$dayStartT=datetimeStringToStamp($day." 00:00:00");
	$dayEndT=datetimeStringToStamp($day." 23:59:59");
	foreach ($allEvents as $id => $event) {
		if(isset($event["start"])&&isset($event["ende"])){
			$s=datetimeStringToStamp($event["start"]);
			$e=datetimeStringToStamp($event["ende"]);
			if( 
				($s>=$dayStartT && $s<=$dayEndT) ||
				($e>=$dayStartT && $e<=$dayEndT)||
				($s<=$dayStartT && $e>=$dayEndT)
			){
				$ret[$id]=$event;
			}
		}
	}
	uasort($ret, function ($a, $b) {
		if(!isset($a["start"])){
			return -1;
		}
		if(!isset($b["start"])){
			return 1;
		}
		return $a["start"] <=> $b["start"];
	});
	return $ret;
}
function get2AllEvents(){
	$eNames=getAllEventIDs();
	$events=array();
	foreach ($eNames as $eName) {
		$events[$eName]=getEvent($eName);
	}
	return $events;
}
function getAllEventIDs(){
	global $eventsDir;
	$files=array();
	$ingore=[".",".."];
	foreach (scandir($eventsDir) as $entry) {
		if(is_file($eventsDir.$entry)&&!in_array($entry,$ingore) && $entry==("".intval($entry))){
			$files[]=$entry;
		}
	}
	return $files;
}






function datetimeStringToStamp($dateString){
	return DateTime::createFromFormat("Y-m-d H:i:s",$dateString)->getTimestamp();
}
function dateStringToStamp($dateString){
	return DateTime::createFromFormat("Y-m-d",$dateString)->getTimestamp();
}

?>