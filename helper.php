<?php

$myglobals=array(
	"maxDescLength"=>(16*1024),
	"maxTitleLength"=>200,
	"maxLocationLength"=>(4*1024),
	"maxSourceIDLength"=>(200),
	"maxBildLength"=>(16*1024),
	"maxTagLength"=>(50),
	"maxTagAmount"=>(100)
);

function datetimeStringToStamp($dateString){
	return DateTime::createFromFormat("Y-m-d H:i:s",$dateString)->getTimestamp();
}
function dateStringToStamp($dateString){
	return DateTime::createFromFormat("Y-m-d",$dateString)->getTimestamp();
}

function checkLogin($force=true){
	if(isset($_SESSION["kalenterlogin"]) && $_SESSION["kalenterlogin"]){
		return $_SESSION["kalenterlogin"];
	}else{
		if($force){
			//echo "FORCING ".($force?"true":"false");debug_print_backtrace();
			http_response_code(401);//401: Unauthorized 	
			include (__DIR__."/login.php");
			die();
		}else{
			return false;
		}
	}
}

function reduceStringtoLength($input,$length){
	$input="".$input;
	if(strlen($input)>$length){
		$input=substr($input,0,$length);
	}
	return $input;
}
function sanaticeTagName($inputTag){
	global $myglobals;
	$inputTag=preg_replace('/[^a-z0-9_]/', '', strtolower($inputTag));
	return reduceStringtoLength($inputTag,$myglobals["maxTagLength"]);
}

function umlaute($text){ 
	$returnvalue="";
	return htmlspecialchars($text);
}

// Capture the start time of the script
$start_time = microtime(true);
// Global variable to store the logs
$debug_logs = [];
//permission and will to log
$doLog=null;
function debugLog($custom=false){
	global $doLog;
	global $userName;
	if($doLog===null){
		$doLog=isset($_GET["debugLog"])&&$userName;
		if($doLog){
			register_shutdown_function('drawTimeline');
		}
	}
	if(!$doLog)return;
	global $debug_logs, $start_time;
	
	// Get the backtrace, skipping the call to debugLog itself
	$backtrace = debug_backtrace();
	
	// Calculate the elapsed time since the start of the script in milliseconds
	$elapsed_time_ms = (microtime(true) - $start_time) * 1000;
	
	if($custom){
		$debug_logs[]=[
			'custom'=>$custom,
			'backtrace'=>$backtrace,
			'runtime_ms' => round($elapsed_time_ms, 2)
		];
		return;
	}
	
	// The caller will be the second item in the stack (first is the call to debugLog itself)
	if (isset($backtrace[1])) {
		$caller = $backtrace[1];
		$log_entry = [
			'function' => isset($caller['function']) ? $caller['function'] : 'global scope',
			'file' => isset($caller['file']) ? $caller['file'] : 'unknown file',
			'line' => isset($caller['line']) ? $caller['line'] : 'unknown line',
			'arguments' => isset($caller['args']) ? $caller['args'] : [],
			'runtime_ms' => round($elapsed_time_ms, 2)
		];

		// Append the log entry to the global log variable
		$debug_logs[] = $log_entry;
	}
}
function getDebugLogs() {
	global $debug_logs;
	return $debug_logs;
}
function getDebugLogsAsJson() {
	return json_encode(getDebugLogs(), JSON_PRETTY_PRINT);
}
function printDebugLogsAsComment() {
	debugLog("printDebugLogsAsComment");
	echo "<!-- \n". json_encode(getDebugLogs(), JSON_PRETTY_PRINT)."\n -->";
}

function drawTimeline() {
	debugLog("drawTimeline");
	$data=getDebugLogs();
	// SVG base settings
	$entryCount = count($data);
    // SVG settings
    $svgWidth = 1200;           // Total width of the SVG
    $barHeight = 2;             // Height of each bar
    $barSpacing = 0.2;            // Vertical spacing between bars
    $svgHeight = $entryCount * ($barHeight+$barSpacing) + 50; // Maximum height (for 8000 entries with 5px height + 2px spacing)
    $padding = 50;              // Padding around the SVG content

    // Determine the maximum runtime to scale the bars proportionally
    $maxRuntime = 0;
    foreach ($data as $entry) {
        if (isset($entry['runtime_ms'])) {
            $maxRuntime = max($maxRuntime, $entry['runtime_ms']);
        }
    }

    // Prevent division by zero
    if ($maxRuntime == 0) {
        $maxRuntime = 1;
    }

    // Calculate scaling factor for bar widths
    $usableWidth = $svgWidth - 2 * $padding;
    $scale = $usableWidth / $maxRuntime;

    // Start building the SVG
    $svg = "<svg width=\"$svgWidth\" height=\"$svgHeight\" xmlns=\"http://www.w3.org/2000/svg\" style=\"border:1px solid #ccc;\">\n";
    $svg .= "<style>
                .bar { fill: steelblue; }
                .bar:hover { fill: darkorange; }
                /* Optional: Add transition for smooth hover effect */
                .bar { transition: fill 0.2s; }
             </style>\n";

    // Iterate through each entry and create a bar
    foreach ($data as $index => $entry) {
        if (!isset($entry['runtime_ms'])) continue; // Skip entries without runtime

        $runtime = $entry['runtime_ms'];
        $barWidth = $runtime * $scale;
        $yPos = $padding/2 + $index * ($barHeight + $barSpacing);

        // Construct the tooltip content
        $descriptionParts = [];
        if (isset($entry['function'])) {
            $descriptionParts[] = "Function: {$entry['function']}";
        }
        if (isset($entry['file'])) {
            $descriptionParts[] = "File: {$entry['file']}";
        }
        if (isset($entry['line'])) {
            $descriptionParts[] = "Line: {$entry['line']}";
        }
        if (isset($entry['arguments'])) {
            $args = array_map(function($arg) {
                if (is_array($arg)) return json_encode($arg);
                elseif (is_bool($arg)) return $arg ? 'true' : 'false';
                else return (string)$arg;
            }, $entry['arguments']);
            $descriptionParts[] = "Args: " . implode(', ', $args);
        }
        if (isset($entry['custom'])) {
            $descriptionParts[] = "Custom: {$entry['custom']}";
        }
        $tooltip = htmlspecialchars(implode("; ", $descriptionParts), ENT_QUOTES);

        // Draw the bar with a tooltip
        $svg .= "<rect class=\"bar\" x=\"$padding\" y=\"$yPos\" width=\"$barWidth\" height=\"$barHeight\">
                    <title>$tooltip</title>
                 </rect>\n";
    }

    // Optional: Add a horizontal timeline axis
    $svg .= "<line x1=\"$padding\" y1=\"".($padding/2)."\" x2=\"$padding\" y2=\"" . ($padding/2 + $entryCount * ($barHeight + $barSpacing)) . "\" stroke=\"#000\" />\n";

    // Close the SVG tag
    $svg .= "</svg>";

	echo "<!-- hi -->".$svg;
}
function makeTag($id,$event){
	$return="";
	$textStartTime=$event["start"];
	$textEndTime=$event["ende"];
	$ganztags=false;
	if(explode(" ",$textStartTime)[0]==explode(" ",$textEndTime)[0]){
		if(explode(" ",$textStartTime)[1]=="00:00:00"&&explode(" ",$textEndTime)[1]=="23:59:59"){
			$ganztags=true;
		}
		$textEndTime=explode(" ",$textEndTime)[1];
	}
	$isPublic=isset($event["isPublic"])&&$event["isPublic"];
	$isImage=isset($event["bildURL"])&&$event["bildURL"];
	
	
	$tags="";
	if(isset($event["tags"])){
		foreach ($event["tags"] as $tag) {
			if($tag)
				$tags.='<a href="editTag.php?name='.umlaute($tag).'" target="_blank" class="mytag">'.
					umlaute($tag).
				'</a>';
		}
	}
	
	$return .= '<div class="eventFromList" style="border: black solid 1px;margin:0 0 2px;">';
		$return .= '<b>'.($ganztags?(explode(" ",$textStartTime)[0]." ganztags"):($textStartTime.'</b> bis <b>'.$textEndTime)).'</b>';
		$return .= ($isPublic?" <b>[ist öffentlich]</b>":"");
		$return .= '<div class="tagGroup">'.$tags.'</div><br/>';
		$return .= '<span class="eventDetailsTitle">'.umlaute($event["name"]).'</span>';
		$return .= 	'<a class="link" target="_blank" href="editEvent.php?eventID='.$id.'">
					bearbeiten
				</a>';
				$return .= '<br/>';
		if($isImage){
			$return .=('<img fill="red" src="'.$event["bildURL"].'" height="100px" width="100px"/>'."\n");
		}
		$mheight=min(250,max(40,5+20*substr_count($event["description"],"\n")));
		$return .= '<pre style="margin: 0px;width: 100%;display: inline-block;height: '.$mheight.'px;overflow: auto;font-size: 14px;font-family: Arial, sans-serif;">'.umlaute($event["description"]).'</pre>';
	$return .= '</div>';
	return $return;
}

?>