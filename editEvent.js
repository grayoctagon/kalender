

function saveEvent(){
	let formData=new FormData(document.querySelector('form'));
	let object = {};
	formData.forEach(function(value, key){
		object[key] = value;
	});
	
	let formDataSend = new FormData();
	formDataSend.append("form", JSON.stringify(object));
	
	let preSaveText=""+saveBtn.innerText;
	saveBtn.innerText="saving ...";
	
	let xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4) {
			saveBtn.innerText=preSaveText;
		}
		if (this.readyState == 4 && this.status == 200) {
			console.log("d",this.response)
			let myJson=false;
			try {
				myJson = JSON.parse(this.response);
			} catch (e) {
				outputtext.innerText="Fehler, konnte Antwort vom Server nicht verarbeiten: \n"+this.response;
				return console.error(e);
			}
			
			
			if(myJson.status=="success"){
				outputtext.innerText="erfolgreich gespeichert ";
				if(myJson.createdNew)
					outputtext.innerHTML="erfolgreich erstellt mit ID: "+myJson.eventID+" "+
											'<a href="?eventID='+myJson.eventID+'">neu erstelltes Event öffnen</a>';
				if(myJson.additionalMessage)
					outputtext.innerText=outputtext.innerText+"\n"+myJson.additionalMessage;
			}else{
				outputtext.innerText="Fehler "+myJson.status+" \n"+this.response;
			}
		}
		if (this.readyState == 4 && this.status != 200) {
			outputtext.innerText="Fehler "+this.status+" beim Speichern: \n"+this.response;
		}
	};
	
	xhttp.open("POST", "?saveEvent&eventID="+eventID, true);
	xhttp.send(formDataSend);
	console.log([formDataSend,object,formData]);
}

function redrawTags(){
	console.log("redrawTags");
	let newTags=getTagObjects();
	let nHtml="";
	newTags.forEach(element => {
		if(!element)return;
		nHtml+=`<a href="editTag.php?name=`+element+`" target="_blank" class="mytag">
					`+element+`
				</a>`;
	});
	tagarea.innerHTML=nHtml;
}
function reRenderTags(){
	console.log("reRenderTags");
	let newTags=getTagObjects();
	tagInput.value=newTags.join(",");
	redrawTags();
}
function getTagObjects(){
	let tags=(""+tagInput.value).split(",");
	let newTags=[];
	let myCount=0;
	tags.forEach(t=>{
		if(myCount<maxTagAmount)
			newTags.push(t.substring(0,maxTagLength).replaceAll(/[^a-z0-9_]/gm, '').toLowerCase());
		myCount++;
	});
	return newTags;
}

function setTimes(st=false,en=false){
	let s=start.value;
	let e=ende.value;
	if(st)
		start.value=s.split(" ")[0]+" "+st;
	if(en)
		ende.value=s.split(" ")[0]+" "+en;
}


function copyDay(){
	let s=start.value;
	let e=ende.value;
	
	console.log([s,e]);
	console.log([s.split(" "),e.split(" ")]);
	e=s.split(" ")[0]+" "+e.split(" ")[1];
	console.log([s,e]);
	
	ende.value=e;
}

setTimeout(() => {
	redrawTags();
}, 1000);
