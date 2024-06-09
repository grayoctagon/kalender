
let defaultSettings={
	firstCall:new Date().toISOString().slice(0, 19).replace('T', ' '),
	lastsaved:false,
	darkmode:false
};

document.addEventListener("DOMContentLoaded", function(){
    init();
});

function init(){
	addEventListener("storage", (event) => {readClientSettings();});
	readClientSettings();
}

function toggleDarkmode(){
	window.kalenderClientSettings.darkmode=!window.kalenderClientSettings.darkmode;
	saveClientSettings();
	guiDarkmode();
}

function guiDarkmode(){
	darkmodeindicator.innerHTML="darkmode: "+(window.kalenderClientSettings.darkmode?"on":"off");
	Array.from(document.getElementsByClassName("darkmodeOption")).forEach(element => {
		if(window.kalenderClientSettings.darkmode){
			element.classList.add("darkmodeActive");
		}else{
			element.classList.remove("darkmodeActive");
		}
	});
}

function readClientSettings(){
	let current=defaultSettings;
	let read=localStorage.getItem("kalenderClientSettings");
	if(read){
		read=JSON.parse(read);
		current=applyUnsetKeys(read,defaultSettings);
	}else{
		localStorage.setItem("kalenderClientSettings",JSON.stringify(defaultSettings));
	}
	window.kalenderClientSettings=current;
	guiDarkmode();
	return current;
}

function saveClientSettings(){
	let current=window.kalenderClientSettings;
	current.lastsaved=new Date().toISOString().slice(0, 19).replace('T', ' ');
	localStorage.setItem("kalenderClientSettings",JSON.stringify(current));
}

function applyUnsetKeys(base, additionalAttributes){
	let baseKeys=Object.keys(base);
	Object.keys(additionalAttributes).forEach(key => {
		if(!baseKeys.includes(key)){
			base[key]=additionalAttributes[key];
		}else if(typeof base[key] == "object"){
			if(Array.isArray(base[key])){
				//Todo hard
			}else{
				//Todo recursivity for objects
			}
		}
	});
	return base;
}

