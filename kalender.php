<?php
$jear=date("Y");
$month=date("n");
include(__DIR__."/events.php");

$selected_day=0;
$selected_date=0;

if(isset($_GET["j"])&&isset($_GET["m"])){
	$j=intval($_GET["j"]);
	$m=intval($_GET["m"]);
	if($j>1970&&$j<2200){
		if($m>0&&$m<13){
			$jear=$j;
			$month=$m;
		}
	}
}
if(isset($_GET["d"])){
	$d=intval($_GET["d"]);
	if($d>0&&$d<32){
		$selected_day=$d;
		$selected_date=date('Y-m-d',dateStringToStamp($jear."-".$month."-".$selected_day));
	}
}

$letztesMonatText="";
$letztesMonatInt=0;
$letztesMonatJahr=0;
$letztesMonatLink="";
$letztesMonatStrtotime="";
if($month<=1){
	$letztesMonatText=getMonthText(12)."\n".($jear-1);
	$letztesMonatLink="?m=12&j=".($jear-1);
	$letztesMonatInt=12;
	$letztesMonatStrtotime=($jear-1)."-12";
	$letztesMonatJahr=$jear-1;
}else{
	$letztesMonatText=getMonthText($month-1);
	$letztesMonatInt=$month-1;
	$letztesMonatLink="?m=".($month-1)."&j=".($jear);
	$letztesMonatStrtotime="$jear-".($month-1);
	$letztesMonatJahr=$jear;
}


$aktuellesMonatText=getMonthText($month)." ".$jear;






$note="";
if($userName){
	$note="angemeldet als $userName ";
}else{
	$note="nicht angemeldet";
}







$aktuellesMonatStrtotime="$jear-$month";

$naechstesMonatText="";
$naechstesMonatInt=0;
$naechstesMonatJahr=0;
$naechstesMonatLink="";
$naechstesMonatStrtotime="";
if($month>=12){
	$naechstesMonatText=getMonthText(1)."\n".($jear+1);
	$naechstesMonatLink="?m=1&j=".($jear+1);
	$naechstesMonatInt=1;
	$naechstesMonatStrtotime=($jear+1)."-1";
	$naechstesMonatJahr=($jear+1);
}else{
	$naechstesMonatText=getMonthText($month+1);
	$naechstesMonatInt=$month+1;
	$naechstesMonatLink="?m=".($month+1)."&j=".($jear);
	$naechstesMonatStrtotime="$jear-".($month+1);
	$naechstesMonatJahr=($jear);
}

//### events
$eventsAll=array();
$eventsToView=array();
{
	global $eventsAll;
	global $eventsToView;
	global $jear;
	global $month;
	global $selected_day;
	$eventsAll=get2AllEvents();
	if($selected_day!=0){
		$eventsToView=getEventsOn("$jear-$month-$selected_day");
	}
}



$myDays=array();

//tage vom letzten monat die noch angezeigt werden
$wochentag=date('N', strtotime("last day of $letztesMonatStrtotime"));//1 (for Monday) through 7 (for Sunday)
$montatstag=date('j', strtotime("last day of $letztesMonatStrtotime"));//Day of the month without leading zeros  1 to 31

if($wochentag==7){
	$wochentag=0;
}
while($wochentag>0){
	$day=array();
	$day["number"]=$montatstag-($wochentag-1);
	$day["month"]=$letztesMonatInt;
	$day["jear"]=$letztesMonatJahr;
	$day["date"]=date("Y-m-d",dateStringToStamp($day["jear"].'-'.$day["month"].'-'.$day["number"]));
	$day["opacity"]=0.5;
	$day["events"]=getEventsOn($day["date"]);
	$myDays[]=$day;
	
	
	$wochentag--;
}
//aktuelles Monat
$letzterTag=date('j', strtotime("last day of $aktuellesMonatStrtotime"));//Day of the month without leading zeros  1 to 31
$i=1;
while($i<=$letzterTag){
	$day=array();
	$day["number"]=$i;
	$day["month"]=$month;
	$day["jear"]=$jear;
	$day["date"]=date("Y-m-d",dateStringToStamp($day["jear"].'-'.$day["month"].'-'.$day["number"]));
	$day["opacity"]=1;
	$day["events"]=getEventsOn($day["date"]);
	
	$myDays[]=$day;
	
	
	$i++;
}

//naechstes Monat
$wochentag=date('N', strtotime("last day of $aktuellesMonatStrtotime"));//1 (for Monday) through 7 (for Sunday)
$draw=7-$wochentag;
if($wochentag==7){
	$draw=0;
}
$i=1;
while($i <= $draw){ 
	$day=array();
	$day["number"]=$i;
	$day["month"]=$naechstesMonatInt;
	$day["jear"]=$naechstesMonatJahr;
	$day["date"]=date("Y-m-d",dateStringToStamp($day["jear"].'-'.$day["month"].'-'.$day["number"]));
	$day["opacity"]=0.5;
	$day["events"]=getEventsOn($day["date"]);
	
	$myDays[]=$day;
	
	
	$i++;
	$wochentag--;
}


function drawDays(){
	global $myDays;
	global $selected_day;
	global $jear;
	global $month;
	$i=0;
	$xstart=50;
	$ystart=70;
	foreach($myDays as $day){
		$x=$xstart+($i%7)*100;
		$y=$ystart+floor($i/7)*100;
		$url='?j='.$day["jear"].'&m='.$day["month"].'&d='.$day["number"];
		echo('<a href="'.$url.'" onclick="openLink(\''.$url.'\')">');
		$addClass="";
		if($selected_day==$day["number"]&&$jear==$day["jear"]&&$month==$day["month"]){
			$addClass=" selectedday";
		}
		echo('<g class="day'.$addClass.'" opacity="'.$day["opacity"].'" overflow="hidden">');
			echo('<rect class="border" name="day'.$i.'" x="'.$x.'" y="'.$y.'" width="99" height="99" />'."\n");
			echo('<text x="'.($x+1).'" y="'.($y+13).'" font-family="Arial" font-size="12" class="textDayNum">'.$day["number"].'</text>'."\n");
			
			//events
			$eventCount=count($day["events"]);
			$eventNum=0;
			foreach($day["events"] as $id=>$ev){
				$str1="";
				$str2="";
				if($eventNum<7){
					//$str1=$ev["uhrzeit_stunde"].":".$ev["uhrzeit_minute"];
					$str1="";
					if(str_starts_with($ev["start"],$day["date"])){
						$str1=date('H:i',datetimeStringToStamp($ev["start"]));
					}else{
						$str1="...";
					}
					$str1.="-";
					if(str_starts_with($ev["ende"],$day["date"])){
						$str1.=date('H:i',datetimeStringToStamp($ev["ende"]));
					}else{
						$str1.="...";
					}
					if($str1=="00:00-23:59"){
						$str1="<i>ganztags</i>";
					}
					$str2=umlaute($ev["name"]);
				}else if($eventNum==7){
					$str1='.. '.($eventCount-($eventNum))." more Events";
				}
				//echo('<text x="'.($x+3).'" y="'.($y+13+13+($eventNum*14)).'" font-family="Arial" font-size="10">'.$str1.'</text>'."\n");
				//echo('<text x="'.($x+30).'" y="'.($y+13+13+($eventNum*14)).'" font-family="Arial" font-size="7">'.$str2.'</text>'."\n");
				echo('<foreignObject x="'.($x+2).'" y="'.($y+13+7+($eventNum*10)).'" width="98" height="10">'.
					'<div class="eventText" style="width: 98;height: 10px;overflow: hidden;font-size: 8px;white-space: nowrap;font-family:\'Arial\';" title="'.$str1.'  '.$str2.'">'.
						'<b class="eventOverviewTime">'.$str1.'</b> '.$str2.
					'</div>'.
					'</foreignObject>');
				
				$eventNum++;
			}
		echo('</g>');
		echo('</a>');
		$i++;
	}
}
function drawEvents(){
	global $eventsToView;
	global $selected_date;
	$i=0;
	$xstart=50;
	$ystart=50;
	foreach($eventsToView as $id=>$e){
		$x=$xstart;
		$y=$ystart+$i*100;
		
		
		$textStartTime=minimalTimeDateTitle("$selected_date",$e["start"]);
		$textEndTime=minimalTimeDateTitle("$selected_date",$e["ende"]);
		
		$isPublic=isset($e["isPublic"])&&$e["isPublic"];
		
		$isImage=isset($e["bildURL"])&&$e["bildURL"];
		echo('<g class="event" opacity="1">');
		if($isImage){
			echo('<image fill="red" xlink:href="'.$e["bildURL"].'" x="'.$x.'" y="'.($y+20).'" height="79px" width="79px"/>'."\n");
		}
		echo('<rect class="border" name="" x="'.$x.'" y="'.$y.'" width="699" height="99" />'."\n");
		
		echo('<a class="link" target="_blank" href="editEvent.php?eventID='.$id.'" onclick="openLinkN(\'editEvent.php?eventID='.$id.'\')">');
			echo('<text x="'.($x+600).'" y="'.($y+13).'" font-family="Arial" font-size="12">bearbeiten</text>'."\n");
		echo('</a>');
		
		/*
		echo('<switch>'.
			'<g requiredFeatures="http://www.w3.org/Graphics/SVG/feature/1.2/#TextFlow">'.
			'	<textArea width="200" height="300">'.$e["description"].'</textArea>'.
			'</g>'.
			'<foreignObject width="200" height="300">'.
			'	<textArea xmlns="http://www.w3.org/1999/xhtml" style="width: 200px;height: 300px">'.$e["description"].'</textArea>'.
			'</foreignObject>'.
		'</switch>');*/
		
		$times='<b>'.$textStartTime.'</b> bis <b>'.$textEndTime.'</b>';
		if("$textStartTime"=="00:00"&&"$textEndTime"=="23:59:59"){
			$times='<b><i>ganztags</i></b>';
		}else{
			//echo '<script>console.log(["'.$textStartTime.'","'.$textEndTime.'"])</script>';
		}
		$tags="";
		if(isset($e["tags"])){
			//$tags="". json_encode($e["tags"]);
			foreach ($e["tags"] as $tag) {
				if($tag)
					$tags.='<a href="editTag.php?name='.$tag.'" target="_blank" class="mytag">'.
						$tag.
					'</a>';
			}
		}
		
		echo('<foreignObject x="'.($x+1).'" y="'.($y).'" width="500" height="95">'.
			'<div style="width: 500px;height: 42px;overflow: auto;font-size: 18px;font-family: Arial, sans-serif;">'.
				$times.
				($isPublic?"<b>[ist öffentlich]</b>":"").
				"&nbsp;<div class=\"tagGroup\">$tags</div>".
				'<br/>'.
			'</div>'.
			'</foreignObject>');
		echo('<foreignObject x="'.($x+($isImage?90:0)).'" y="'.($y+23).'" width="'.($isImage?600:700).'" height="80">'.
			'<div style="width: '.($isImage?600:700).'px;height: 21px;overflow: auto;font-size: 18px;font-family: Arial, sans-serif;">'.
				'<span class="eventDetailsTitle">'.
					umlaute($e["name"]).
				'</span>'.
			'</div>'.
			'<div style="width: '.($isImage?600:700).'px;height: 53px;overflow: auto;font-size: 14px;font-family: Arial, sans-serif;">'.	
				str_replace("\n","<br/>",umlaute($e["description"])).
			'</div>'.
			'</foreignObject>');
		echo('</g>');
		$i++;
	}
}

function minimalTimeDateTitle($selected_date,$dateTime){
	if(str_starts_with($dateTime,"$selected_date")){
		$dateTime=substr($dateTime,strlen($selected_date));
	}
	if(str_ends_with($dateTime,":00")){
		$dateTime=substr($dateTime,0,-3);
	}
	$dateTime=trim($dateTime);
	return $dateTime;
}

function getMonthText($month){
	switch($month.""){
		case "1":
			return "J&auml;nner";break;
		case "2":
			return "Februar";break;
		case "3":
			return "M&auml;rz";break;
		case "4":
			return "April";break;
		case "5":
			return "Mai";break;
		case "6":
			return "Juni";break;
		case "7":
			return "Juli";break;
		case "8":
			return "August";break;
		case "9":
			return "September";break;
		case "10":
			return "Oktober";break;
		case "11":
			return "November";break;
		case "12":
			return "Dezember";break;
	}
	return "null";
}
?>
<html>
<head>
	<title>Kalender</title>
	<style>
	<?php echo file_get_contents(__DIR__."/style.css"); ?>
	</style>
	<script type="text/javascript">
		function openLink(url){
			location.assign(url);
			return false;
		}
		function openLinkN(url){
			window.open(url,'_blank');
			return false;
		}
	</script>
	<script>
		<?php 
		if(!$userName){
			echo "".file_get_contents(__DIR__."/sendLongSession.js").""; 
		}
		echo "".file_get_contents(__DIR__."/main.js").""; 
		?>
	</script>
</head>
<body class="darkmodeOption">
	<?php 
		if(!$userName){
			echo '<b id="infoarea" style="background-color: aqua;"></b><br/>'; 
		}
		$width=800;
		$height=((count($myDays)/7)+1)*100;//600 oder 700
		echo('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" >'."\n");
			
			echo('<rect x="1" y="1" width="'.($width-2).'" height="'.($height-2).'" class="borderRect" stroke-width="1px" fill="none"/>'."\n");
			
			
			echo('<text x="'.(420).'" y="'.(40).'" font-family="Arial" font-size="14" class="ntext textNote">'.$note.'</text>'."\n");
			echo('<text x="'.(420).'" y="'.(20).'" font-family="Arial" font-size="14" '.
			'class="link ntext" '.
				'id="darkmodeindicator" onclick="toggleDarkmode()">'.
					"darkmode: ".
				'</text>'."\n");
			//blaetterbuttons
			echo('<a class="link" href="'.$letztesMonatLink.'" onclick="openLink(\''.$letztesMonatLink.'\')"><text x="'.(10).'" y="'.(40).'" font-family="Arial" font-size="14">&lt; '.$letztesMonatText.'</text></a>'."\n");
			echo('<a class="link" href="'.$naechstesMonatLink.'" onclick="openLink(\''.$naechstesMonatLink.'\')"><text x="'.($width-90).'" y="'.(40).'" font-family="Arial" font-size="14">'.$naechstesMonatText.'&gt;</text></a>'."\n");
			//Titel
			echo('<text x="'.(150).'" y="'.(40).'" font-family="Arial" font-size="35" class="ntext textAktuellesMonat">'.$aktuellesMonatText.'</text>'."\n");
			//timestamp:
			echo('<text x="'.($width-275).'" y="'.($height-10).'" font-family="Arial" font-size="16" class="ntext">generiert am '.date('d.m.Y').' um '.date('H:i:s').'</text>'."\n");
			//wochentage
			echo('<text x="'.(50+25).'" y="'.(65).'" font-family="Arial" font-size="15" class="textWeekday ntext">'."MonaTag".
				'<!-- Hinweis: "MonaTag" ist kein Tippfehler. Es ist absichtlich so geschrieben, als Zeichen der Wertschätzung für meine Freundin Mona. -->'.
				'</text>'."\n");
			echo('<text x="'.(150+25).'" y="'.(65).'" font-family="Arial" font-size="15" class="textWeekday ntext">'."Dienstag".'</text>'."\n");
			echo('<text x="'.(250+25).'" y="'.(65).'" font-family="Arial" font-size="15" class="textWeekday ntext">'."Mittwoch".'</text>'."\n");
			echo('<text x="'.(350+15).'" y="'.(65).'" font-family="Arial" font-size="15" class="textWeekday ntext">'."Donnerstag".'</text>'."\n");
			echo('<text x="'.(450+25).'" y="'.(65).'" font-family="Arial" font-size="15" class="textWeekday ntext">'."Freitag".'</text>'."\n");
			echo('<text x="'.(550+25).'" y="'.(65).'" font-family="Arial" font-size="15" class="textWeekday ntext">'."Samstag".'</text>'."\n");
			echo('<text x="'.(650+25).'" y="'.(65).'" font-family="Arial" font-size="15" class="textWeekday ntext">'."Sonntag".'</text>'."\n");
			drawDays();
		echo('</svg>'."\n");
		
		
		echo('<br />'."\n");
		
		$eventCount=count(array_keys($eventsToView));
		$width=800;
		$height=($eventCount)*100+50;
		echo('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" >'."\n");
				$eventCount=count(array_keys($eventsToView));
				
				//Titel
				$eventSufix=" am ".$selected_day.".".$month.".".$jear;
				$eventText=$eventCount." Events".$eventSufix;
				if($eventCount==1)$eventText="1 Event".$eventSufix;
				if($eventCount==0)$eventText=" keine Events".$eventSufix;
				if($selected_day==0)$eventText=" kein Tag gewaehlt";
				echo('<text x="'.(10).'" y="'.(40).'" font-family="Arial" font-size="35">'.$eventText.'</text>'."\n");
				
				//addButton
				$setCreateDate="";
				if(isset($_GET["j"])&&isset($_GET["m"])){
					$setCreateDate="?j=".$_GET["j"]."&m=".$_GET["m"];
					if(isset($_GET["d"])){
						$setCreateDate.="&d=".$_GET["d"];
					}else{
						$setCreateDate.="&d=01";
					}
				}
				echo('<a class="link" target="_blank" href="editEvent.php'.$setCreateDate.'"><text x="'.($width-145).'" y="'.(15).'" font-family="Arial" font-size="14">neues Event erstellen</text></a>'."\n");
				
				drawEvents();
		echo('</svg>'."\n");
	?>
	
</body>
</html>