let longsession=localStorage.getItem("kalenderLongSession");
if(longsession){
	longsession=JSON.parse(atob(longsession));
	setTimeout(() => {
		infoarea.innerText=" trying to resume long Session .... ";
		let formDataSend = new FormData();
		formDataSend.append("longsession", JSON.stringify(longsession));
		let xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				console.log("d",this.response)
				let myJson=false;
				try {
					myJson = JSON.parse(this.response);
				} catch (e) {
					infoarea.innerText="Fehler, konnte Antwort vom Server nicht verarbeiten: \n"+this.response;
					setTimeout(()=>{window.location = window.location.href;},50000000);
					return console.error(e);
				}
				
				
				if(myJson.status=="success"){
					infoarea.innerText="Session erfolgreich verlängert"+(window.sendLongSessionNoWindowReload?"":", lade in 3 sek neu  ");
					
					setTimeout(()=>{
						if(!window.sendLongSessionNoWindowReload){
							window.location = window.location.href;
						}else{
							infoarea.innerText="";
						}
						},3000);
					
					if(myJson.additionalMessage)
						infoarea.innerText=infoarea.innerText+"\n"+myJson.additionalMessage;
				}else{
					infoarea.innerText="Fehler "+myJson.status+" \n"+this.response;
				}
			}
			if (this.readyState == 4 && this.status != 200) {
				infoarea.innerText="Fehler "+this.status+": \n"+this.response;
			}
		};
		
		xhttp.open("POST", "/kalender/login.php?resumelongsession="+JSON.parse(atob(localStorage.getItem("kalenderLongSession")))["longSessionID"], true);
		xhttp.send(formDataSend);
	}, 500);
}
