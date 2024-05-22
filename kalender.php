<?php
$jear=date("Y");
$month=date("n");

$selected_day=0;

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
$eventsView=array();
{
	global $eventsAll;
	global $eventsView;
	global $jear;
	global $month;
	global $selected_day;
	include(__DIR__."/events.php");
	$eventsAll=getAllEvents();
	if($selected_day!=0){
		$eventsView=getEventsOn($eventsAll,$jear,$month,$selected_day);
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
	$day["opacity"]=0.5;
	$day["events"]=(getEventsOn($eventsAll,$day["jear"],$day["month"],$day["number"]));
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
	$day["opacity"]=1;
	$day["events"]=(getEventsOn($eventsAll,$day["jear"],$day["month"],$day["number"]));
	
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
	$day["opacity"]=0.5;
	$day["events"]=(getEventsOn($eventsAll,$day["jear"],$day["month"],$day["number"]));
	
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
			echo('<text x="'.($x+1).'" y="'.($y+13).'" font-family="Arial" font-size="12">'.$day["number"].'</text>'."\n");
			
			//events
			$eventCount=count($day["events"]);
			$eventNum=0;
			foreach($day["events"] as $ev){
				$str1="";
				$str2="";
				if($eventNum<4){
					$str1=$ev["uhrzeit_stunde"].":".$ev["uhrzeit_minute"];
					$str2=umlaute($ev["name"]);
				}else if($eventNum==4){
					$str1='.. '.($eventCount-($eventNum))." more Events";
				}
				echo('<text x="'.($x+3).'" y="'.($y+13+13+($eventNum*14)).'" font-family="Arial" font-size="10">'.$str1.'</text>'."\n");
				//echo('<text x="'.($x+30).'" y="'.($y+13+13+($eventNum*14)).'" font-family="Arial" font-size="7">'.$str2.'</text>'."\n");
				echo('<foreignObject x="'.($x+30).'" y="'.($y+13+7+($eventNum*14)).'" width="70" height="8">'.
					'	<div style="width: 70;height: 8px;overflow: hidden;font-size: 8px;white-space: nowrap;font-family:\'Arial\';" title="'.$str1.'  '.$str2.'">'.$str2.'</div>'.
					'</foreignObject>');
				
				$eventNum++;
			}
		echo('</g>');
		echo('</a>');
		$i++;
	}
}
function drawEvents(){
	global $eventsView;
	$i=0;
	$xstart=50;
	$ystart=50;
	foreach($eventsView as $e){
		$x=$xstart;
		$y=$ystart+$i*100;
		echo('<g class="event" opacity="1">');
		echo('<text x="'.($x+20).'" y="'.($y+13).'" font-family="Arial" font-size="16" font-weight="bold">'.$e["uhrzeit_stunde"].":".$e["uhrzeit_minute"].'</text>'."\n");
		echo('<text x="'.($x+100).'" y="'.($y+15).'" font-family="Arial" font-size="16">'.umlaute($e["name"]).'</text>'."\n");
		echo('<image xlink:href="'.$e["bildURL"].'" x="'.$x.'" y="'.($y+20).'" height="79px" width="79px"/>'."\n");
		echo('<rect class="border" name="" x="'.$x.'" y="'.$y.'" width="699" height="99" />'."\n");
		
		echo('<a class="link" target="_blank" href="editEvent.php?eventID='.$e["id"].'" onclick="openLinkN(\'editEvent.php?eventID='.$e["id"].'\')">');
			echo('<text x="'.($x+600).'" y="'.($y+13).'" font-family="Arial" font-size="12">bearbeiten</text>'."\n");
		echo('</a>');
		
		/*
		echo('<switch>'.
			'<g requiredFeatures="http://www.w3.org/Graphics/SVG/feature/1.2/#TextFlow">'.
			'	<textArea width="200" height="300">'.$e["beschreibung"].'</textArea>'.
			'</g>'.
			'<foreignObject width="200" height="300">'.
			'	<textArea xmlns="http://www.w3.org/1999/xhtml" style="width: 200px;height: 300px">'.$e["beschreibung"].'</textArea>'.
			'</foreignObject>'.
		'</switch>');*/
		echo('<foreignObject x="'.($x+90).'" y="'.($y+25).'" width="500" height="70">'.
			'	<div style="width: 500px;height: 70px;overflow: auto;font-size: 14px;">'.$e["beschreibung"].'</div>'.
			'</foreignObject>');
		echo('</g>');
		$i++;
	}
}


function umlaute($text){ 
	$returnvalue="";
	return htmlspecialchars($text);
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
	.event .border{
		stroke-width:1px;
		stroke:rgba(0, 0, 0, 0.1);
		fill:rgba(0, 0, 0, 0);
	}
	.day .border{
		stroke-width:1px;
		stroke:black;
		fill:rgba(0, 0, 0, 0);
		cursor: pointer;
	}
	.day:hover .border{
		stroke:red;
	}
	.selectedday .border{
		stroke:green;
		fill:rgba(0, 0, 0, 0.2);
	}
	.link{
		stroke:black;
		stroke-width:0.5px;
		cursor: pointer;
	}
	.link:hover{
		stroke:blue;
		color:blue;
	}
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
</head>
<body>
	<?php 
		$width=800;
		$height=((count($myDays)/7)+1)*100;//600 oder 700
		echo('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" >'."\n");
			
			echo('<rect x="1" y="1" width="'.($width-2).'" height="'.($height-2).'" stroke="black" stroke-width="1px" fill="none"/>'."\n");
			
			
			echo('<text x="'.(350).'" y="'.(40).'" font-family="Arial" font-size="14">'.$note.'</text>'."\n");
			//blaetterbuttons
			echo('<a class="link" href="'.$letztesMonatLink.'" onclick="openLink(\''.$letztesMonatLink.'\')"><text x="'.(10).'" y="'.(40).'" font-family="Arial" font-size="14">&lt; '.$letztesMonatText.'</text></a>'."\n");
			echo('<a class="link" href="'.$naechstesMonatLink.'" onclick="openLink(\''.$naechstesMonatLink.'\')"><text x="'.($width-90).'" y="'.(40).'" font-family="Arial" font-size="14">'.$naechstesMonatText.'&gt;</text></a>'."\n");
			//Titel
			echo('<text x="'.(150).'" y="'.(40).'" font-family="Arial" font-size="35">'.$aktuellesMonatText.'</text>'."\n");
			//timestamp:
			echo('<text x="'.($width-140).'" y="'.($height-3).'" font-family="Arial" font-size="8">generiert am '.date('j.n.Y').' um '.date('H:i:s').'</text>'."\n");
			//wochentage
			echo('<text x="'.(50+25).'" y="'.(65).'" font-family="Arial" font-size="15">'."Montag".'</text>'."\n");
			echo('<text x="'.(150+25).'" y="'.(65).'" font-family="Arial" font-size="15">'."Dinstag".'</text>'."\n");
			echo('<text x="'.(250+25).'" y="'.(65).'" font-family="Arial" font-size="15">'."Mittwoch".'</text>'."\n");
			echo('<text x="'.(350+15).'" y="'.(65).'" font-family="Arial" font-size="15">'."Donnerstag".'</text>'."\n");
			echo('<text x="'.(450+25).'" y="'.(65).'" font-family="Arial" font-size="15">'."Freitag".'</text>'."\n");
			echo('<text x="'.(550+25).'" y="'.(65).'" font-family="Arial" font-size="15">'."Samstag".'</text>'."\n");
			echo('<text x="'.(650+25).'" y="'.(65).'" font-family="Arial" font-size="15">'."Sonntag".'</text>'."\n");
			drawDays();
		echo('</svg>'."\n");
		
		
		echo('<br />'."\n");
		
		$eventCount=count($eventsView);
		$width=800;
		$height=($eventCount)*100+50;
		echo('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" >'."\n");
				$eventCount=count($eventsView);
				
				//Titel
				$eventSufix=" am ".$selected_day.".".$month.".".$jear;
				$eventText=$eventCount." Events".$eventSufix;
				if($eventCount==1)$eventText="1 Event".$eventSufix;
				if($eventCount==0)$eventText=" keine Events".$eventSufix;
				if($selected_day==0)$eventText=" kein Tag gewaehlt";
				echo('<text x="'.(10).'" y="'.(40).'" font-family="Arial" font-size="35">'.$eventText.'</text>'."\n");
				
				//addButton
				echo('<a class="link" target="_blank" href="editEvent/?id=0"><text x="'.($width-145).'" y="'.(15).'" font-family="Arial" font-size="14">neues Event erstellen</text></a>'."\n");
				
				drawEvents();
		echo('</svg>'."\n");
	?>
	
</body>
</html>