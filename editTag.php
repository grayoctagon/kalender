<?php
date_default_timezone_set('Europe/Vienna');
session_start([
	'cookie_lifetime' => 86400
]);
include(__DIR__."/events.php");
$userName=checkLogin(true);
$tagName=false;
if(isset($_GET["name"])){
	$tagName=sanaticeTagName($_GET["name"]);
	if(!$tagName)$tagName=false;
}
$showElements=isset($_GET["showElements"]);
// TODO : escape so wie anderswo
?><html>
<head>
	<title>Kalender</title>
	<style>
	<?php echo file_get_contents(__DIR__."/style.css"); ?>
	</style>
	<script>
			<?php 
			//echo "".file_get_contents(__DIR__."/main.js").""; 
			?>
	</script>
</head>
<body class="darkmodeOption">
<?php

echo '<a href="?name='.$tagName.($showElements?"":"&showElements").'">'.($showElements?"hide":"show").' Elements with tag "'.$tagName.'"?</a>';

if($showElements){
	$eventsToView=getTaggedEvents($tagName);
	$eventCount=count(array_keys($eventsToView));
	$eventText=$eventCount." Events";
	if($eventCount==1)$eventText="1 Event";
	if($eventCount==0)$eventText=" keine Events";
	echo '<h1>'.$eventText.($tagName?" mit dem Tag $tagName":" ohne Tag ").' </h1>';
	echo '<div class="eventList">';
	foreach ($eventsToView as $id => $event) {
		$textStartTime=$event["start"];
		$textEndTime=$event["ende"];
		$isPublic=isset($event["isPublic"])&&$event["isPublic"];
		$isImage=isset($event["bildURL"])&&$event["bildURL"];
		
		
		echo '<div class="eventFromList" style="border: black solid 1px;margin:0 0 2px;">';
			echo '<b>'.$textStartTime.'</b> bis <b>'.$textEndTime.'</b>';
			echo ($isPublic?"<b>[ist öffentlich]</b>":"");
			echo '<div class="tagGroup"></div><br/>';
			echo '<span class="eventDetailsTitle">'.umlaute($event["name"]).'</span>';
			echo 	'<a class="link" target="_blank" href="editEvent.php?eventID='.$id.'">
						bearbeiten
					</a>';
			echo '<br/>';
			if($isImage){
				echo('<img fill="red" src="'.$event["bildURL"].'" height="100px" width="100px"/>'."\n");
			}
			echo '<pre style="margin: 0px;width: 700px;display: inline-block;height: 100px;overflow: auto;font-size: 14px;font-family: Arial, sans-serif;">'.umlaute($event["description"]).'</pre>';
		echo '</div>';
	}
	echo '</div>';
}















?>

</body>
</html>