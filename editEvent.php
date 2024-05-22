<?php
date_default_timezone_set('Europe/Vienna');
session_start([
    'cookie_lifetime' => 86400
]);
$userName=checkLogin();

$myglobals=array(
	"maxDescLength"=>(16*1024),
	"maxTitleLength"=>200,
	"maxLocationLength"=>(4*1024),
	"maxSourceIDLength"=>(200),
	"maxBildLength"=>(16*1024),
	"maxTagLength"=>(50),
	"maxTagAmount"=>(100)
);

$eventID=0;
$eventObj=false;
$welcomeText="Hallo $userName, du erstellst ein neues Element ";
$startdateViaParam=false;
if(isset($_GET["eventID"])){
	$eventID=intval($_GET["eventID"]);
	$welcomeText= "Hallo $userName, du bearbeitest das Event mit der id $eventID";
}else{
	if(isset($_GET["j"])&&isset($_GET["m"])&&isset($_GET["d"])){
		$startdateViaParam=date("Y-m-d",datetimeStringToStamp($_GET["j"]."-".$_GET["m"]."-".$_GET["d"]." 09:00:00"));;
	}
}
$eventObj=getEvent($eventID);
if($startdateViaParam){
	$eventObj["start"]=$startdateViaParam." 09:00:00";
	$eventObj["ende"]=$startdateViaParam." 17:00:00";
}

if(isset($_GET["saveEvent"]) && isset($_GET["eventID"]) && $_POST["form"]){
	$mytime=time()*1000+rand(100,999);
	$returnObj=array("status"=>"undefined error","eventID"=>"$eventID","createdNew"=>false,"additionalMessage"=>"");
	$file=__DIR__."/"."events/".$eventID;
	$oldObj=false;
	$form = json_decode($_POST["form"],true);
	if(isset($form["tags"])){
		$form["tags"]=explode(",",("".$form["tags"]));
	}else{
		$form["tags"]=array();
	}
	$newElement=false;
	if(!$eventID || !is_file($file)){
		$eventID=$mytime;
		$returnObj["eventID"]=$eventID;
		$returnObj["createdNew"]=true;
		$newElement=sanaticeEvent($form,false);
		
		$newElement["createdBy"]=$userName;
		$newElement["createdT"]=$mytime;
		$newElement["createdH"]=date("Y-m-d H:i:s",$mytime/1000);
	}else{
		$oldObj=getEvent($eventID);
		$newElement=sanaticeEvent($form,$oldObj);
	}
	$newElement["lastChangedBy"]=$userName;
	$newElement["lastChangedT"]=$mytime;
	$newElement["lastChangedH"]=date("Y-m-d H:i:s",$mytime/1000);
	
	$file=__DIR__."/"."events/".$eventID;
	//echo json_encode($form);
	
	
	if(file_put_contents($file,json_encode($newElement,JSON_PRETTY_PRINT),LOCK_EX)){
		$returnObj["status"]="success";
		if($oldObj){
			$changeText=getWriteableDifferenceAsText($oldObj,$newElement);
			$returnObj["additionalMessage"]="Changed: ".$changeText;
			if($changeText!="\nnothing changed"){
				$changeObj=getWriteableDifferenceObj($oldObj,$newElement);
				addToVerlauf("changeEvent",array("eventID"=>$eventID,"changes"=>$changeObj));
			}
		}else{
			addToVerlauf("createEvent",array("eventID"=>$eventID,"obj"=>$newElement));
		}
	}else{
		http_response_code(500);
		$returnObj["status"]="error while writing File";
	}
	header('Content-type: application/json');
	die(json_encode($returnObj,JSON_PRETTY_PRINT));
}else{
	//echo $welcomeText;
}

echo "<style>\n".file_get_contents(__DIR__."/style.css")."\n</style>"; 

function addOnlyToVerlauf($data){
	file_put_contents(__DIR__."/data/verlauf".date("Y").".json", json_encode($data).",\n", FILE_APPEND | LOCK_EX);
}
function addToVerlauf($kindOfChange,$data){
	addOnlyToVerlauf(array(
		"who"=>checkLogin(),
		"when"=>date("Y-m-d H:i:s"),
		"what"=>$kindOfChange,
		"data"=>$data,
	));
}

function sanaticeEvent($inputUserChanges,$recentEvent=false){
	global $myglobals;
	if(!$recentEvent){
		$recentEvent=getBlankEvent();
	}
	$writableAttrs=getWriteableAttributes();
	foreach ($writableAttrs as $writableAtr) {
		if(isset($inputUserChanges[$writableAtr])){
			$value=$inputUserChanges[$writableAtr];
			switch ($writableAtr) {
				case 'isPublic':
					$value=$value?true:false;
					break;
				case 'description':
					$value=reduceStringtoLength($value,$myglobals["maxDescLength"]);
					break;
				case 'name':
					$value=reduceStringtoLength($value,$myglobals["maxTitleLength"]);
					break;
				case 'location':
					$value=reduceStringtoLength($value,$myglobals["maxLocationLength"]);
					break;
				case 'bildURL':
					$value=reduceStringtoLength($value,$myglobals["maxBildLength"]);
					break;
				case 'sourceID':
					$value=reduceStringtoLength($value,$myglobals["maxSourceIDLength"]);
					break;
				case 'start':
					$value=reduceStringtoLength($value,strlen("2024-01-01 00:00:00"));
					$value= date("Y-m-d H:i:s",datetimeStringToStamp($value));
					break;
				case 'ende':
					$value=reduceStringtoLength($value,strlen("2024-01-01 00:00:00"));
					$value= date("Y-m-d H:i:s",datetimeStringToStamp($value));
					break;
				case 'tags':
					$neueTags=array();
					$myCount=0;
					if(is_array($value)){
						foreach ($value as $tag) {
							if($myCount<$myglobals["maxTagAmount"]){
								$neueTags[]=reduceStringtoLength($tag,$myglobals["maxTagLength"]);
							}
							$myCount++;
						}
					}
					$value=$neueTags;
					//$value=reduceStringtoLength($value,$myglobals["maxSourceIDLength"]);
					//TODO
					break;
			}
			$recentEvent[$writableAtr]=$value;
		}
	}
	return $recentEvent;
	//TODO
	
}

function reduceStringtoLength($input,$length){
	$input="".$input;
	if(strlen($input)>$length){
		$input=substr($input,0,$length);
	}
	return $input;
}

function getWriteableAttributes(){
	return array_keys(getEvent(false));
}

function getWriteableDifferenceAsText($oldEvent,$newEvent){
	$changes=getWriteableDifferenceObj($oldEvent,$newEvent);
	return changesToText($changes);
}

function changesToText($changes){
	if(count(array_keys($changes))==0)
		return "\nnothing changed";
	$text="";
	foreach ($changes as $atr => $changeEl) {
		if($atr=="tags"){
			if(count($changeEl["removedTags"])>0){
				$text.="\nremovedTags:\"".implode('", "',$changeEl["removedTags"]) .'"';
			}
			if(count($changeEl["addedTags"])>0){
				$text.="\naddedTags:\"".implode('", "',$changeEl["addedTags"]) .'"';
			}
		}else{
			$text.="\n$atr changed from \"".htmlspecialchars($changeEl["old"]).'" to "'.htmlspecialchars($changeEl["new"]).'" ';
		}
	}
	//TODO Tags
	return $text;
}

function getWriteableDifferenceObj($oldEvent,$newEvent){
	$writeableAttributes=getWriteableAttributes();
	$changes=array();
	foreach ($writeableAttributes as $atr) {
		if( !(!isset($oldEvent[$atr])&&!isset($newEvent[$atr])) ){
			if( !isset($oldEvent[$atr])||!isset($newEvent[$atr])){
				if(isset($oldEvent[$atr])){
					$changes[$atr]=array("old"=>((gettype($oldEvent[$atr])=="string")?$oldEvent[$atr]:json_encode($oldEvent[$atr])),"new"=>null);
				}else{
					$changes[$atr]=array("old"=>null,"new"=>((gettype($newEvent[$atr])=="string")?$newEvent[$atr]:json_encode($newEvent[$atr])));
				}
			}else if($atr=="tags"){
				$oldTags=$oldEvent[$atr];
				$newTags=$newEvent[$atr];
				$addedTags=array();
				$removedTags=array();
				$unchangedTags=array();
				foreach ($oldTags as $ot) {
					$found=false;
					foreach ($newTags as $nt) {
						if($ot==$nt)$found=true;
					}
					if($found){
						$unchangedTags[]=$ot;
					}else{
						$removedTags[]=$ot;
					}
				}
				foreach ($newTags as $nt) {
					$found=false;
					foreach ($oldTags as $ot) {
						if($ot==$nt)$found=true;
					}
					if(!$found){
						$addedTags[]=$nt;
					}
				}
				if(count($addedTags)!=0||count($removedTags)){
					$changes[$atr]=array("addedTags"=>$addedTags,"removedTags"=>$removedTags,"unchangedTags"=>$unchangedTags);
				}
			}else if($oldEvent[$atr] != $newEvent[$atr]){
				$changes[$atr]=array("old"=>$oldEvent[$atr],"new"=>$newEvent[$atr]);
			}
		}
	}
	return $changes;
}

function checkLogin(){
	if(isset($_SESSION["kalenterlogin"]) && $_SESSION["kalenterlogin"]){
		return $_SESSION["kalenterlogin"];
	}else{
		http_response_code(401);//401: Unauthorized 	
		include (__DIR__."/login.php");
		die();
	}
}
function getEvent($eventID=false){
	$file=__DIR__."/"."events/".$eventID;
	$return=getBlankEvent();
	if($eventID&&file_exists($file)&&is_file($file)){
		$loaded=json_decode(file_get_contents($file),true);
		foreach ($return as $key => $value) {
			if(isset($loaded[$key]))
				$return[$key]=$loaded[$key];
		}
	}
	return $return;
}
function getBlankEvent(){
	return array(
		"start"=>date("Y-m-d H:00:00"),
		"ende"=>date("Y-m-d H:00:00",time()+60*60),
		"name"=>"neuesEvent",
		"description"=>"...",
		"tags"=>array(),
		"location"=>"",
		"bildURL"=>"",
		"isPublic"=>false,
		"sourceID"=>"",
		"createdBy"=>"",
		"createdT"=>"",
		"createdH"=>""
	);
}

function datetimeStringToStamp($dateString){
	return DateTime::createFromFormat("Y-m-d H:i:s",$dateString)->getTimestamp();
}
function dateStringToStamp($dateString){
	return DateTime::createFromFormat("Y-m-d",$dateString)->getTimestamp();
}

?><!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>
	let eventID=<?php echo intval($eventID); ?>;
	let maxTagLength=<?php echo intval($myglobals["maxTagLength"]); ?>;
	let maxTagAmount=<?php echo intval($myglobals["maxTagAmount"]); ?>;
	<?php echo file_get_contents(__DIR__."/editEvent.js"); ?>
</script>
<style>
	<?php echo file_get_contents(__DIR__."/style.css"); ?>
</style>
</head>
<body>
<a href="/kalender">&larr; zur&uuml;ck zum Kalender</a><br/>
<?php echo "<h2>$welcomeText</h2>"; ?>
<!--<h2>edit Element</h2>-->
<form action="" method="post">
	<div class="container">
		
		
		<label for="isPublic"><b>
			isPublic
		</b></label>
		<input type="checkbox" <?php echo $eventObj["isPublic"]?"checked":"" ?> placeholder="Event isPublic" name="isPublic">
		<br/>
		<i>wenn nicht, wird ohne Anmeldung nur start und enddatum angezeigt</i>
		<br/><br/>
		
		
		<label for="name"><b>
			Event Name
		</b></label>
		<input type="text" value="<?php echo htmlspecialchars($eventObj["name"]) ?>" placeholder="Event Name" name="name" minlength="2" maxlength="<?php echo $myglobals["maxTitleLength"]; ?>" required>
		
		
		<label for="start"><b>
			Start Datum und Zeit
		</b></label>
		<button onclick="setTimes('00:00:00','23:59:59')" type="button" class="normal">
			ganztägig
		</button>
		<button onclick="setTimes('09:00:00','17:00:00')" type="button" class="normal">
			<b>09</b>:00-<b>17</b>:00
		</button>
		<button onclick="setTimes('18:00:00','22:00:00')" type="button" class="normal">
			<b>18</b>:00-<b>22</b>:00
		</button>
		<input type="text" value="<?php echo htmlspecialchars($eventObj["start"]) ?>" placeholder="Start Datum und Zeit" name="start" id="start" pattern="^\d\d\d\d-(\d)?\d-(\d)?\d \d\d:\d\d:\d\d$" >
		
		
		<label for="ende"><b>
			End Datum und Zeit
		</b></label>
		<button onclick="copyDay()" type="button" class="normal">
			copy day
		</button>
		<input type="text" value="<?php echo htmlspecialchars($eventObj["ende"]) ?>" placeholder="End Datum und Zeit" name="ende" id="ende" pattern="^\d\d\d\d-(\d)?\d-(\d)?\d \d\d:\d\d:\d\d$" >
		
		
		<label for="description"><b>
			Beschreibung
		</b></label>
		<br/>
		<textarea type="text" placeholder="Beschreibung" name="description" rows="3" maxlength="<?php echo $myglobals["maxDescLength"]; ?>"><?php echo htmlspecialchars($eventObj["description"]) ?></textarea>
		<br/>
		
		
		<label for="location"><b>
			location
		</b></label>
		<input type="text" value="<?php echo htmlspecialchars($eventObj["location"]) ?>" placeholder="Event location" name="location" maxlength="<?php echo $myglobals["maxLocationLength"]; ?>">
		
		
		<label for="bildURL"><b>
			bildURL
		</b></label>
		<input type="text" value="<?php echo htmlspecialchars($eventObj["bildURL"]) ?>" placeholder="Event bildURL" name="bildURL" maxlength="<?php echo $myglobals["maxBildLength"]; ?>">
		
		
		<label for="tags"><b>
			Tags:
		</b></label>
		<input id="tagInput" type="text" onblur="reRenderTags()" oninput="redrawTags()" value="<?php echo htmlspecialchars(implode(",",$eventObj["tags"])) ?>" placeholder="Tags,Taggs,Taaags" name="tags" maxlength="<?php echo $myglobals["maxTagAmount"]*$myglobals["maxTagLength"]; ?>" pattern="[a-zA-Z0-9_,]+$">
		Vorschau: <div id="tagarea"></div>
		
		<button type="button" id="saveBtn" onclick="saveEvent()">speichern</button>
		<span id="outputtext"></span>
	</div>
</form>
</body>
</html>