<?php 
$eventsDir=__DIR__."/"."events/";

include(__DIR__."/helper.php");

date_default_timezone_set('Europe/Vienna');
if(!isset($_SESSION)) 
session_start([
    'cookie_lifetime' => 86400
]);
$userName=checkLogin(false);


function getEvent($eName){
	debugLog();
	global $eventsDir;
	if(!file_exists($eventsDir.$eName)){
		return false;
	}
	$fullObj=json_decode(file_get_contents($eventsDir.$eName),true);
	if(checkLogin(false))
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
	debugLog();
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
$myCacheForGet2AllEvents=null;
function get2AllEvents(){
	debugLog();
	global $myCacheForGet2AllEvents;
	if($myCacheForGet2AllEvents!==null)return $myCacheForGet2AllEvents;
	$eNames=getAllEventIDs();
	$events=array();
	foreach ($eNames as $eName) {
		$events[$eName]=getEvent($eName);
	}
	$myCacheForGet2AllEvents=$events;
	return $events;
}
function sortElementsByStart(&$elements){
	uasort($elements, fn($a, $b) => strcmp($a["start"], $b["start"]));
	return $elements;
}

function getTaggedEvents($tag=false){
	if($tag=="")$tag=false;
	$allEvents=get2AllEvents();
	$toReturn=array();
	foreach ($allEvents as $id=>$event) {
		$hasTags=false;
		$found=false;
		if(isset($event["tags"]))
		foreach ($event["tags"] as $value) {
			if($value&&$value!=""){
				$hasTags=true;
				if($value==$tag)
					$found=true;
			}
		}
		if($found){
			$toReturn[$id]=$event;
		}else{
			if($tag===false && !$hasTags ){
				$toReturn[$id]=$event;
			}
		}
	}
	return $toReturn;
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



?>