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

?>