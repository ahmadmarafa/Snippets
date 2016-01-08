//original http://www.w3schools.com/js/js_cookies.asp
var cookie = function(){
    return {
        set:function(name , value , time , path){
            var d = new Date() ;
			d.setTime(d.getTime()+(time*24*60*60*1000));
			var expires = "expires="+d.toGMTString();
			document.cookie = name+"="+value+";"+expires;
        } ,
        get:function(name){
            var ret = {} ;
			var ca = document.cookie.split(';');
			for(var i=0; i<ca.length; i++) 
			{
				data = ca[i].split('=');
				data[0] = data[0].trim() ;
				data[1] = data[1].trim();
				if(data[0] == name) {
					return data[1] ;
				}else{
					ret[data[0]] = data[1] ;
				}
			}
			return name == "" || name == "*" ? ret : null;
            
        },
        isset:function(name , value){
            return document.cookie.indexOf(name+"=") > -1            
        },
        unset:function(name){
            document.cookie = name+"=; expires=Thu, 01 Jan 1970 00:00:00 UTC";
        }
    };
}();
