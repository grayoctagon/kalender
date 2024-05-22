<?php 
$eventsDir=__DIR__."/"."events/";
function getEventString($eName){
	global $eventsDir;
	if(!file_exists($eventsDir.$eName)){
		return false;
	}
	return file_get_contents($eventsDir.$eName);
	
}
function getEvent($eName){
	return json_decode(getEventString($eName),true);
	
}
function checkEvent(&$e){
	if(!isset($e["id"])){
		return false;
	}
	$existiert=eventExists($e["id"]);
	if(!($e["id"]=="0"||$existiert)){
		return false;
	}
	if($e["id"]=="0"){
		$e["id"]=round(microtime(true) * 1000);
	}
	if(!isset($e["beschreibung"])){
		$e["beschreibung"]="";
	}
	if(!isset($e["bildURL"])){
		$e["bildURL"]="";
	}
	if(!isset($e["name"])){
		$e["name"]="";
	}
	if(isset($e["datum"])){
		$dt=new DateTime($e["datum"]);
		$dt->setTimeZone(new DateTimeZone('Europe/Berlin'));
		$e["datum_jahr"]=$dt->format('Y');
		$e["datum_monat"]=$dt->format('m');
		$e["datum_tag"]=$dt->format('d');
	}else{
		return false;
	}
	if(isset($e["uhrzeit"])){
		$dt=new DateTime($e["uhrzeit"]);
		$dt->setTimeZone(new DateTimeZone('Europe/Berlin'));
		$e["uhrzeit_stunde"]=$dt->format('H');
		$e["uhrzeit_minute"]=$dt->format('i');
		//$e["datum_test2"]=$dt->format('Y-m-d H:i:s');
	}else{
		return false;
	}
	return true;
}
function getEventsOn($events,$y,$m,$d){
	$y=intval($y);
	$m=intval($m);
	$d=intval($d);
	$ret=array();
	for($i=count($events)-1;$i>=0;$i--) {
		$e=$events[$i];
		$ey=-1;
		$em=-1;
		$ed=-1;
		if(isset($e['datum_jahr']))
			$ey=intval($e["datum_jahr"]);
		if(isset($e["datum_monat"]))
			$em=intval($e["datum_monat"]);
		if(isset($e["datum_tag"]))
			$ed=intval($e["datum_tag"]);
		
		if($ey==$y)
		if($em==$m)
		if($ed==$d)
			$ret[]=$e;
	}
	//sortieren
	$ret2=array();
	for($i0=count($ret)-1;$i0>=0;$i0--) {
		$smalest=24;
		for($i=count($ret)-1;$i>=0;$i--) {
			$std=intval($ret[$i]["uhrzeit_stunde"]);
			if($std<$smalest)
				$smalest=$std;
		}
		for($i=count($ret)-1;$i>=0;$i--) {
			$std=intval($ret[$i]["uhrzeit_stunde"]);
			if($std==$smalest){
				$ret2[]=$ret[$i];
				array_splice($ret, $i,1);
				
				break;
			}
		}
	}
	
	return $ret2;
}
function setEvent(){
	global $eventsDir;
	$event=file_get_contents("php://input");
	$event=json_decode($event,true);
	if($event==null){
		http_response_code(400);
		echo("fehler, json fehler");
		return;
	}else{
		if(!isset($event["event"])){
			http_response_code(400);
			echo("fehler, json fehler, event nicht gesetzt");
			return;
		}else{
			$event=$event["event"];
			if(!checkEvent($event)){
				http_response_code(400);
				echo("fehler, json fehler, event entspricht nicht den Anforderungen\n\n alle Felder ausgefuellt?");
				return;
			}else{
				if(writeEvent($event)){
					echo("erfolgreich gespeichert :)\n".$event["id"]);
				}else{
					http_response_code(400);
					echo("fehler beim speichern");
				}
				
			}
		}
	}
}
function deleteEvent(){
	global $eventsDir;
	$event=file_get_contents("php://input");
	$event=json_decode($event,true);
	if($event==null){
		http_response_code(400);
		echo("fehler, json fehler");
		return;
	}else{
		if(!isset($event["event"])){
			http_response_code(400);
			echo("fehler, json fehler, event nicht gesetzt");
			return;
		}else{
			$event=$event["event"];
			if(!isset($event["id"])){
				http_response_code(400);
				echo("fehler, json fehler, event-id fehlt");
				return;
			}else{
				if(!eventExists($event["id"])){
					http_response_code(500);
					echo("FEHLER, event". $event["id"] ." existiert nicht");
					return;
				}
				$eventID=$event["id"];
				$pfad=$eventsDir.$eventID;
				$pfad2=$eventsDir."../deletedEvents/".$eventID."";
				if(rename ($pfad,$pfad2)){
					echo("erfolgreich in den papierkorb verschoben\n".$eventID);
					return;
				}else{
					http_response_code(500);
					echo("FEHLER, from \n".$pfad."\r\nto \n".realpath($pfad2));
					return;
				}
			}
		}
	}
}
function eventExists($eventID){
	global $eventsDir;
	return file_exists($eventsDir.$eventID);
}
function writeEvent($event){
	if(!isset($event["id"]))return false;
	return writeEventString($event["id"],json_encode($event));
}
function writeEventString($eventID,$eventSring){
	global $eventsDir;
	$pfad=$eventsDir.$eventID;
				
	//$output=json_encode($objekt);
	$textdatei = fopen ($pfad, "w");
	fwrite($textdatei, $eventSring);
	fclose($textdatei);
	return true;
}
function getAllEvents(){
	$eNames=getAllEventNames();
	$events=array();
	foreach ($eNames as $eName) {
		$events[]=getEvent($eName);
	}
	return $events;
}
function getAllEventNames(){
	global $eventsDir;
	$files=array();
	$ingore=[".",".."];
	foreach (scandir($eventsDir) as $entry) {
		//if (is_file($dir.$entry)){
		
		if(!in_array($entry,$ingore))//if(str_ends_with($entry,".obj"))
				$files[]=$entry;
		//}
	}
	return $files;
}

?>