<?php
date_default_timezone_set('Europe/Vienna');
session_start([
	'cookie_lifetime' => 86400
]);
include(__DIR__."/helper.php");

$userName=checkLogin();

//file_put_contents(, json_encode($data).",\n", FILE_APPEND | LOCK_EX);
$welcomeText="Verlauf Übersicht";
$selectedYear=false;
$loggedYears=getYearNumbersFromFiles();
if(
	isset($_GET["historyYear"]) && 
	in_array(intval($_GET["historyYear"]),$loggedYears)
	){
	$selectedYear=intval($_GET["historyYear"]);
}else if(count($loggedYears)==1){
	$selectedYear=$loggedYears[0];
}
$content="";
if(!$selectedYear){
	$content= "select Year:<br>";
	foreach ($loggedYears as $year) {
		$content.= '<a href="?historyYear='.$year.'">'.$year.'</a><br>';
	}
}else{
	$welcomeText="Verlauf $selectedYear";
	$data=json_decode("[".file_get_contents(__DIR__."/data/verlauf".$selectedYear.".json")."false]",true);
	foreach ($data as $logEntry) {
		if($logEntry&&isset($logEntry["what"])&&
			$logEntry["what"]=="createEvent"){
			
			$content.='<h3 style="margin-bottom: 0;">'.
			"on ".$logEntry['when'].
			" ".$logEntry['who'].
			" created event ".
			'<a href="editEvent.php?eventID='.$logEntry['data']["eventID"].'" target="_blank">'.
				$logEntry['data']["eventID"].
			'</a>'.
			"</h3>";
			$content.= makeTag($logEntry['data']["eventID"], $logEntry['data']["obj"]);
		}else
		if($logEntry&&isset($logEntry["what"])&&
			$logEntry["what"]=="changeEvent"){
			$content.='<h3 style="margin-bottom: 0;">'.
				"on ".$logEntry['when'].
				" ".$logEntry['who'].
				" changed event ".
				'<a href="editEvent.php?eventID='.$logEntry['data']["eventID"].'" target="_blank">'.
					$logEntry['data']["eventID"].
				'</a>'.
				"</h3>";
			foreach ($logEntry['data']["changes"] as $key => $value) {
				if($key=="tags"){
					//addedTags":["homeoffice__"],"removedTags":["homeoffice"],"unchangedTags":[]
					$content.='<div class="changeEntry">';
					if(count($value["addedTags"])>0){
						$tagText="";
						foreach ($value["addedTags"] as $tag) {
							if($tag)
							$tagText.='<a href="editTag.php?name='.$tag.'" target="_blank" class="mytag">'.
								$tag.
								'</a>';
						}
						if($tagText){
							$content.='<b>addedTags:</b> '.$tagText;
						}
					}
					if(count($value["removedTags"])>0){
						$tagText="";
						foreach ($value["removedTags"] as $tag) {
							if($tag)
							$tagText.='<a href="editTag.php?name='.$tag.'" target="_blank" class="mytag">'.
								$tag.
								'</a>';
						}
						if($tagText){
							$content.='<b>removedTags:</b> '.$tagText;
						}
					}
					$content.=	"</div>";
				}else if($key=="isPublic"){
					$content.='<div class="changeEntry">'.
						"changed <b>$key</b> ".
						"from <b>".($value["old"]?"public":"private")."</b> ".
						"to <b>".($value["new"]?"public":"private")."</b> ".
						"</div>";
				}else{
					$old=umlaute($value["old"]);
					$new=umlaute($value["new"]);
					if( str_contains($old,"\n")){
						$old='from: <pre class="hilightAble" style="margin: 0;">'.$old."</pre>";
					}else{
						$old='from <span class="hilightAble">'.$old."</span>";
					}
					if(str_contains($new,"\n")){
						$new='to: <pre class="hilightAble" style="margin: 0;">'.$new."</pre>";
					}else{
						$new='to <span class="hilightAble">'.$new."</span>";
					}
					$content.='<div class="changeEntry">'.
						"changed <b>$key</b> ".
						"$old ".
						"$new ".
						"</div>";
				}
			}
		}
	}
}


function getYearNumbersFromFiles() {
	//this function was made with chat GPT 4o on 29.10.2024
	$directory=__DIR__."/data";
	// Initialize an empty array to store the year numbers
	$years = [];
	
	// Regular expression pattern for matching "verlauf<Jahreszahl>.json"
	$pattern = '/^verlauf(\d{4})\.json$/';

	// Open the directory
	if ($handle = opendir($directory)) {
		// Loop through each file in the directory
		while (false !== ($file = readdir($handle))) {
			// Check if the file name matches the pattern
			if (preg_match($pattern, $file, $matches)) {
				// Extract the year number
				$year = (int)$matches[1];
				
				// Check if the year is in the range 1990 - 3000
				if ($year >= 1990 && $year <= 3000) {
					$years[] = $year;
				}
			}
		}
		// Close the directory
		closedir($handle);
	}

	// Return the array of year numbers
	return $years;
}
?><!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>
</script>
<style>
	<?php echo file_get_contents(__DIR__."/style.css"); ?>
</style>
</head>
<body>
<a href="/kalender">&larr; zur&uuml;ck zum Kalender</a><br/>
<?php echo "<h2>$welcomeText</h2>"; ?>
<!--<h2>edit Element</h2>-->
<div class="container">
	<?php echo $content; ?>
</div>
</body>
</html>