// LSL script generated: avatar_classroom.scoreboard.httpin_test.lslp Wed Aug 11 19:44:11 Pacific Daylight Time 2010
default {

    state_entry() {
        llRequestURL();
    }

 
    http_request(key id,string method,string body) {
        if ((method == URL_REQUEST_GRANTED)) {
            llSay(0,("URL: " + body));
        }
        else  if ((method == URL_REQUEST_DENIED)) {
            llSay(0,("Something went wrong, no url. " + body));
        }
        else  if ((method == "GET")) {
            llHTTPResponse(id,200,"Hello World!");
            llSay(0,("*********************************************************************************Received get request: " + body));
        }
        else  if ((method == "POST")) {
            llHTTPResponse(id,200,"Hello World!");
            llSay(0,("*********************************************************************************Received get request: " + body));
        }
        else  {
            llHTTPResponse(id,405,"Unsupported Method");
            llSay(0,("*********************************************************************************Received get request: " + body));
        }
    }
}
