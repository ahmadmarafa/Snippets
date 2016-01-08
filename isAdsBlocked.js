function isAdsBlocked(callback)
{
	var ads = null ; 
	document.onreadystatechange = function(evt){
		if(document.readyState === "interactive"){
			ads = document.createElement("div") ;
			ads.id = "adsContainer" ;
			document.body.appendChild(ads) ;
		}
	
	}
	window.onload = function(){
		setTimeout(function(){
			if( getComputedStyle(ads).getPropertyValue("display").indexOf("none") > -1 )
			{
				callback(true) ;
				return ;
			}
			callback(false) ;
		} , 500) ;
	} ;
}
