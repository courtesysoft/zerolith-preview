// ------------ shortcuts and functions used in zerolith ------------- //
//DEPRECATED 02/01/2024 - DS

function zl_handleHXerror(event)
{
	console.log(event);
	if(document.readyState == "complete")
	{
		var errHTML;
		if(event.type == "htmx:timeout") { errHTML = "Request timeout. Try refreshing the page."; }
		else if(event.type == "htmx:responseError") { errHTML = "Request had error code: " + event.detail.xhr.status; }

		//send the error to the target thing
		event.detail.target.innerHTML = errHTML;
	}
	else { zl_echo("There was a request error early in page rendering that couldn't be caught."); }
}

function zl_echo(line){ console.log("zl: " + line); }

//probably an inefficient way to do things. But it's nice to write HTML instead of JS..
function zl_htmlToObject(html)
{
	var temp= document.createElement('div');
	temp.innerHTML = html;
	return temp.firstChild;
}

//shorthand
function zl_ID(id)
{
	var elem = document.getElementById(id);
	if (elem === null) { zl_echo("DOM ID: " + id + " wasn't found."); }
	return elem;
}

//facilitates zui::windowAction
function zl_IDdelete(id) { zl_ID(id).remove(); }
function zl_IDshow(id, display= 'inline-block') { zl_ID(id).style.display = display; }
function zl_IDhide(id) { zl_ID(id).style.display = "none"; }

function zl_classes(className) { return document.getElementsByClassName(className); }

function zl_ajaxReplaceDiv(URL, div)
{
	var xhr = new XMLHttpRequest();
	xhr.open('GET', URL);
	xhr.onload = function()
	{
		if (xhr.status === 200) { zl_ID(div).innerHTML = xhr.responseText; }
		else { zl_echo('zl_ajaxReplaceDiv() had issue with URL:' + URL + "\n" + xhr.status); }
	};
	xhr.send();
}

function zl_ajaxRequest(URL)
{
	var xhr = new XMLHttpRequest();
	xhr.open('GET', URL, false);
	xhr.send();
	if (xhr.status === 200) { return(xhr.responseText); }
	else { zl_echo('zl_ajaxRequest had issue with URL:' + URL + "\n" + xhr.status); return; }
}

//hide the side navigation bar
function zl_hideNav() { $(".button-collapse").sideNav('hide'); } //console.log('closed');

//Activate draggability of a div by a child div.
function addDrag(divToMove, divToGrab)
{
  divToMove = zl_ID(divToMove); divToGrab = zl_ID(divToGrab);
  var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

  divToGrab.onmousedown = dragStart;
  function dragStart(e)
  {
    e = e || window.event;
    e.preventDefault();
    // get the mouse cursor position at startup
    pos3 = e.clientX; pos4 = e.clientY;
    divToGrab.style.cursor='move';
    document.onmouseup = dragStop;
    document.onmousemove = moveElement;
  }

  function moveElement(e)
  {
    e = e || window.event;
    e.preventDefault();

    // calculate the new cursor position:
    pos1 = pos3 - e.clientX; pos2 = pos4 - e.clientY;
    pos3 = e.clientX; pos4 = e.clientY;
    // set the element's new position:
    divToMove.style.top = (divToMove.offsetTop - pos2) + "px";
    divToMove.style.left = (divToMove.offsetLeft - pos1) + "px";
    //divToMove.style.right = "unset"; //divToMove.style.bottom = "unset";
  }

  //stop moving when mouse button is released
  function dragStop()
  {
    document.onmouseup = null; document.onmousemove = null;
    divToGrab.style.cursor='default';
  }

  //zl_echo(divToMove + " can now be dragged by " + divToGrab + ".");
}