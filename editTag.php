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

echo '<h1> '.($tagName?"bearbetiten des Tags \"$tagName\" ":" Elemente ohne Tag ").' </h1>';

echo '<a href="?name='.$tagName.($showElements?"":"&showElements").'">'.($showElements?"hide":"show").' Elements with tag "'.$tagName.'"?</a>';

if($showElements){
	$eventsToView=getTaggedEvents($tagName);
	sortElementsByStart($eventsToView);
	$eventCount=count(array_keys($eventsToView));
	$eventText=$eventCount." Events";
	if($eventCount==1)$eventText="1 Event";
	if($eventCount==0)$eventText=" keine Events";
	echo '<h2>'.$eventText.($tagName?" mit dem Tag $tagName":" ohne Tag ").' </h2>';
	echo '<div class="eventList">';
	foreach ($eventsToView as $id => $event) {
		echo makeTag($id, $event);
	}
	echo '</div>';
}















?>

</body>
</html>