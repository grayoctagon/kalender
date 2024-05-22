<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start([
    'cookie_lifetime' => 86400
]);

if(isset($_POST["logout"])){
	$_SESSION["kalenterlogin"]=false;
	session_destroy();
	die("logged out <script>setTimeout(()=>{window.location = window.location.href;},5000);</script>");
}
if(isset($_SESSION["kalenterlogin"]) && $_SESSION["kalenterlogin"]){
	if(isset($_POST["changepw"])&&
		isset($_POST["oldpassword"])&&
		isset($_POST["new1password"])&&
		isset($_POST["new2password"])
		){
		$passw=$_POST["oldpassword"];
		$passNew1=$_POST["new1password"];
		$passNew2=$_POST["new2password"];
		if($passNew1!=$passNew2){
			die("old and new PWs must match! <script>setTimeout(()=>{window.location = window.location.href;},5000);</script>");
		}
		$usern=strtolower($_SESSION["kalenterlogin"]);
		
		$users=json_decode(file_get_contents(__DIR__."/data/users.json"),true);
		
		if(!isset($users["users"][$usern])){
			sleep(rand(1,3));
			die('User "'.$usern.'" not found, or PW wrong, code: 1. <script>setTimeout(()=>{window.location = window.location.href;},5000);</script>');
		}
		if(!isset($users["users"][$usern]["pwhash"])){
			sleep(rand(1,3));
			die('User "'.$usern.'" not found, or PW wrong, code: 2. <script>setTimeout(()=>{window.location = window.location.href;},5000);</script>');
		}
		$loadedHash=$users["users"][$usern]["pwhash"];
		if(password_verify($passw,$loadedHash)){
			$users["users"][$usern]["pwhash"]=password_hash($passNew1,PASSWORD_BCRYPT);
			$users["users"][$usern]["pwChgT"]=time();
			$users["users"][$usern]["pwChgH"]=date("Y-m-d H:i:s");
			$users["users"][$usern]["pwChgClient"]=array(
				"REMOTE_ADDR"=>$_SERVER['REMOTE_ADDR'],
				"REMOTE_Host"=>gethostbyaddr($_SERVER['REMOTE_ADDR']),
				"HTTP_CLIENT_IP"=>(isset($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:false),
				"HTTP_CLIENT_Host"=>(isset($_SERVER['HTTP_CLIENT_IP'])?gethostbyaddr($_SERVER['HTTP_CLIENT_IP']):false),
			);
			file_put_contents(__DIR__."/data/users.json",json_encode($users,JSON_PRETTY_PRINT),LOCK_EX);
			die('Change successfull Page will reload soon <script>setTimeout(()=>{window.location = window.location.href;},2000);</script>');
		}else{
			sleep(rand(2,4));
			die('User "'.$usern.'" not found, or PW wrong, code: 3. <script>setTimeout(()=>{window.location = window.location.href;},15000);</script>'.
			//'the Hash for the PW you entered would be "'.password_hash($passw,PASSWORD_BCRYPT).'"'.
			'');
		}
		die();
	}
	echo "<style>\n".file_get_contents(__DIR__."/style.css")."\n</style>"; 

	echo "logged in as \"".$_SESSION["kalenterlogin"]."\" \n<br/>".
	'<form action="" method="post">
		<input type="hidden" name="logout" value="logout">
		<button type="submit">Logout</button>
	</form>
	<br/>'.
	'change PW? <br/>
	<form action="" method="post">
		<input type="hidden" name="changepw" value="changepw" autocomplete="false">
		
		<label for="oldpassword"><b>old Password</b></label>
		<input type="password" placeholder="Enter old Password" name="oldpassword" minlength="12" required>
		<br/>
		<label for="new1password"><b>new Password</b></label>
		<input type="password" placeholder="Enter new Password" name="new1password" minlength="12" required autocomplete="false">
		<br/>
		<label for="new2password"><b>repeat new Password</b></label>
		<input type="password" placeholder="repeat new Password" name="new2password" minlength="12" required autocomplete="false">
		<br/>
		<button type="submit">change PW</button>
	</form>
	<br/>'.
	'';
	die();
}

if(isset($_GET["resumelongsession"])&&isset($_POST["longsession"])){
	$inputData=$_POST["longsession"];
	$inputData=json_decode($inputData,true);
	if($inputData && 
		isset($inputData["username"]) && 
		isset($inputData["longSessionID"]) && 
		isset($inputData["secretLongSessionKey"])
		){
		$usern=$inputData["username"];
		$users=json_decode(file_get_contents(__DIR__."/data/users.json"),true);
		if(!isset($users["users"][$usern])){
			sleep(rand(1,3));
			die('User "'.$usern.'" not found, or PW wrong, code: a1. ');
		}
		$user=$users["users"][$usern];
		if(!isset($user["longsessions"]) || !isset($user["longsessions"][$inputData["longSessionID"]])){
			sleep(rand(1,3));
			die('User "'.$usern.'" not found, or PW wrong, code: a2. ');
		}
		$longSession=$user["longsessions"][$inputData["longSessionID"]];
		
		if(!password_verify($inputData["secretLongSessionKey"],$longSession["hash"])){
			die('User "'.$usern.'" not found, or PW wrong, code: a3. ');
		}
		if(!$longSession["active"]){
			die("could not extend session, is expired");
		}
		$now=time();
		$ip=$_SERVER['REMOTE_ADDR'];
		$hostName=gethostbyaddr($ip);
		$longSession["count"]+=1;
		$longSession["lastTimeT"]=$now;
		$longSession["lastTimeH"]=date("Y-m-d H:i:s",$now);
		$longSession["recentIP"]=$ip;
		$longSession["recentHostNane"]=$hostName;
		$longSession["recentDevice"]=$_SERVER['HTTP_USER_AGENT'];
		$users["users"][$usern]["longsessions"][$inputData["longSessionID"]]=$longSession;
		file_put_contents(__DIR__."/data/users.json",json_encode($users,JSON_PRETTY_PRINT),LOCK_EX);
		http_response_code(200);
		$_SESSION["kalenterlogin"]=$usern;
		die(json_encode(array("status"=>"success")));
	}
	
	die("could not extend session");
}

if(isset($_POST["username"])&&isset($_POST["password"])){
	$usern=strtolower($_POST["username"]);
	$passw=$_POST["password"];
	$stayloggedin=isset($_POST["stayloggedin"]);
	$rememberas=isset($_POST["stayloggedin"])?($_POST["rememberas"]):"not set";
	if(strlen($passw)<12){
		sleep(rand(2,4));
		die('User "'.$usern.'" not found, or PW wrong, code: 0. <script>setTimeout(()=>{window.location = window.location.href;},5000);</script>');
	}
	$users=json_decode(file_get_contents(__DIR__."/data/users.json"),true);
	if(!isset($users["users"][$usern])){
		sleep(rand(1,3));
		die('User "'.$usern.'" not found, or PW wrong, code: 1. <script>setTimeout(()=>{window.location = window.location.href;},5000);</script>');
	}
	if(!isset($users["users"][$usern]["pwhash"])){
		sleep(rand(1,3));
		die('User "'.$usern.'" not found, or PW wrong, code: 2. <script>setTimeout(()=>{window.location = window.location.href;},5000);</script>');
	}
	$loadedHash=$users["users"][$usern]["pwhash"];
	if(password_verify($passw,$loadedHash)){
		$_SESSION["kalenterlogin"]=$usern;
		$logins=array();
		if(isset($users["users"][$usern]["successfullLogins"])){
			$logins=$users["users"][$usern]["successfullLogins"];
		}
		$ip=$_SERVER['REMOTE_ADDR'];
		$now=time();
		$hostName=gethostbyaddr($ip);
		if(!isset($logins[$ip])){
			$logins[$ip]=array(
				"firsttimeT"=>$now,
				"firsttimeH"=>date("Y-m-d H:i:s",$now),
				"count"=>0,
				"firstHostNane"=>$hostName,
				"firstDevice"=>$_SERVER['HTTP_USER_AGENT']
			);
		}
		$logins[$ip]["count"]+=1;
		$logins[$ip]["lastTimeT"]=$now;
		$logins[$ip]["lastTimeH"]=date("Y-m-d H:i:s",$now);
		$logins[$ip]["recentHostNane"]=$hostName;
		$logins[$ip]["recentDevice"]=$_SERVER['HTTP_USER_AGENT'];
		
		$sendToClient=false;
		if($stayloggedin){
			$longSessionID=date("Y-m-d H:i:s",$now).".".rand(1000,9999);
			$longSessionKey="key_".hash('sha256', ''.bin2hex(random_bytes(256)));
			$longSessionHash=password_hash($longSessionKey,PASSWORD_BCRYPT);
			
			if(!isset($users["users"][$usern]["longsessions"])){
				$users["users"][$usern]["longsessions"]=array();
			}
			$users["users"][$usern]["longsessions"]["$longSessionID"]=array(
				"hash"=>$longSessionHash,
				"count"=>1,
				"active"=>true,
				"rememberas"=>$rememberas,
				"firsttimeT"=>$now,
				"firsttimeH"=>date("Y-m-d H:i:s",$now),
				"firstHostNane"=>$hostName,
				"firstIP"=>$ip,
				"firstDevice"=>$_SERVER['HTTP_USER_AGENT'],
				"lastTimeT"=>$hostName,
				"lastTimeH"=>$hostName,
				"recentHostNane"=>$hostName,
				"recentDevice"=>$_SERVER['HTTP_USER_AGENT']
			);
			$sendToClient=array(
				"longSessionID"=>$longSessionID,
				"username"=>$usern,
				"rememberas"=>$rememberas,
				"firsttimeH"=>date("Y-m-d H:i:s",$now)
			);
			$sendToClient["secretLongSessionKey"]=$longSessionKey;
		}
		
		if(count(array_keys($logins))>100){
			$oldestT=$now;
			$oldestID=0;
			foreach ($logins as $id => $val) {
				if($val["lastTimeT"]<$oldestT){
					$oldestID=$id;
				}
			}
			unset($logins[$oldestID]);
		}
		
		$users["users"][$usern]["successfullLogins"]=$logins;
		file_put_contents(__DIR__."/data/users.json",json_encode($users,JSON_PRETTY_PRINT),LOCK_EX);
		http_response_code(200);
		die('Login successfull Page will reload soon <script>localStorage.setItem("kalenderLongSession","'.base64_encode(json_encode($sendToClient)).'");setTimeout(()=>{window.location = window.location.href;},2000);</script>');
	}else{
		sleep(rand(2,4));
		die('User "'.$usern.'" not found, or PW wrong, code: 3. <script>setTimeout(()=>{window.location = window.location.href;},15000);</script>'.
		'the Hash for the PW you entered would be "'.password_hash($passw,PASSWORD_BCRYPT).'"'.
		'');
	}
}

function getBrowserName($u_agent=false){//https://stackoverflow.com/a/28107399
	if(!$u_agent)
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
	$bname = 'Unknown';
	$platform = 'Unknown';
	$version= "";

	//First get the platform?
	if (preg_match('/SMART-TV|SMARTTV|SmartHub/i', $u_agent)) {
		$platform = 'SMART-TV';
	}elseif (preg_match('/iPhone/i', $u_agent)) {
		$platform = 'iPhone';
	}elseif (preg_match('/iPad/i', $u_agent)) {
		$platform = 'iPad';
	}elseif (preg_match('/iPod/i', $u_agent)) {
		$platform = 'iPod';
	}elseif (preg_match('/Samsung\s/i', $u_agent)) {
		$platform = 'Samsung';
	}elseif (preg_match('/Android/i', $u_agent)) {
		$platform = 'Android';
	}elseif (preg_match('/webOS/i', $u_agent)) {
		$platform = 'webOS';
	}elseif (preg_match('/linux/i', $u_agent)) {
		$platform = 'Linux';
	}elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
		$platform = 'Mac';
	}elseif (preg_match('/windows|win32/i', $u_agent)) {
		$platform = 'Windows';
	}

	// Next get the name of the useragent yes seperately and for good reason
	if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)){
	$bname = 'Internet Explorer';
	$ub = "MSIE";
	}elseif(preg_match('/Firefox/i',$u_agent)){
	$bname = 'Mozilla Firefox';
	$ub = "Firefox";
	}elseif(preg_match('/OPR/i',$u_agent)){
	$bname = 'Opera';
	$ub = "Opera";
	}elseif(preg_match('/SamsungBrowser/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
	$bname = 'SamsungBrowser';
	$ub = "SamsungBrowser";
	}elseif(preg_match('/Chrome/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
	$bname = 'Chrome';
	$ub = "Chrome";
	}elseif(preg_match('/Safari/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
	$bname = 'Apple Safari';
	$ub = "Safari";
	}elseif(preg_match('/Netscape/i',$u_agent)){
	$bname = 'Netscape';
	$ub = "Netscape";
	}elseif(preg_match('/Edge/i',$u_agent)){
	$bname = 'Edge';
	$ub = "Edge";
	}elseif(preg_match('/Trident/i',$u_agent)){
	$bname = 'Internet Explorer';
	$ub = "MSIE";
	}

	// finally get the correct version number
	$known = array('Version', $ub, 'other');
	$pattern = '#(?<browser>' . join('|', $known).')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
	if (!preg_match_all($pattern, $u_agent, $matches)) {
	// we have no matching number just continue
	}
	// see how many we have
	$i = count($matches['browser']);
	if ($i != 1) {
	//we will have two since we are not using 'other' argument yet
	//see if version is before or after the name
	if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
		$version= $matches['version'][0];
	}else {
		$version= $matches['version'][1];
	}
	}else {
	$version= $matches['version'][0];
	}

	// check if we have a number
	if ($version==null || $version=="") {$version="?";}
	
	return "$bname on $platform" ;
	return implode(" ",array(
	'userAgent' => $u_agent,
	'name'      => $bname,
	'version'   => $version,
	'platform'  => $platform,
	'pattern'    => $pattern
	));
}

?><!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
	<?php echo file_get_contents(__DIR__."/style.css"); ?>
</style>
<script>
<?php echo "".file_get_contents(__DIR__."/sendLongSession.js")."";  ?>
</script>
</head>
<body>
<h2>Login</h2>
<form action="" method="post">
	<div class="container">
		<label for="username"><b>Username</b></label>
		<input type="text" placeholder="Enter Username" name="username" minlength="4" required>

		<label for="password"><b>Password</b></label>
		<input type="password" placeholder="Enter Password" name="password" minlength="12" required>
		
		
		<label for="stayloggedin"><b>stay logged in</b></label>
		<input type="checkbox" name="stayloggedin" autocomplete="false">
		
		<label for="rememberas"><b> and remember as: </b></label>
		<input type="text" placeholder="Enter Name for Device" value="<?php 
		$useragent = htmlspecialchars(getBrowserName().date(" Y-m"));
		echo $useragent;
		?>" name="rememberas" minlength="4" required style="max-width: 60%;width: 300px;">
		
		<br/>
		<b id="infoarea" style="background-color: aqua;"></b>
		
		<button type="submit">Login</button>
	</div>
</form>
</body>
</html>