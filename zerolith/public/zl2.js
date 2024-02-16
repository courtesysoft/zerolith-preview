//Zerolith Javascript library V2.
//06-14-2023 - v0.5 - Experimental full rewrite to static class.
//07-10-2023 - v0.55 - Fixed escape key triggering
//02-01-2024 - v0.6 - Add delete/show/hide

class zl
{
	static debug = true;
	static debugWarn = true;
	static domLoaded = false;

	//class initialization
	static init()
	{
		zl.quipD("zl.js has been initialized.");
		window.addEventListener("DOMContentLoaded", (event) =>
		{
			this.domLoaded = true;
			zl.quipD("zl.js DOM Content has Loaded.");
		});
	}

	//just like in PHP :)
	static quipD(line) { if(zl.debug) { console.log("ℹ️ " + line); } }

	//emit a warning ( if warnings turned on )
	static quipWarn(line, dontWarn = false) { if(zl.debugWarn && dontWarn) { console.warn("🐞 " + line); } }

	//because it's shorter and we like PHP
	static echo(line){ console.log("%c ZL: " + line, 'background: #000; color:#0F0;'); }

	//refresh the entire page
	static refreshPage(){ location.reload(); }

	//turn a piece of HTML into an object; useful for mutating existing html and popping it back into the DOM
	static htmlToObject(html)
	{
		var temp = document.createElement('div');
		temp.innerHTML = html;
		return temp.firstChild;
	}

	//fun shortcuts, facilitates zui::windowAction
	static deleteID(id) { zl.getID(id).remove(); }
	static deleteSelectors(selector) { zl.getSelectors(selector).forEach(e => e.remove()); }

	static showID(id, newDisplayType = 'inline-block') { zl.getID(id).style.display = newDisplayType; }
	static showSelectors(selector, newDisplayType = 'inline-block') { zl.getSelectors(selector).forEach(e => e.style.display = newDisplayType); }

	static hideID(id) { zl.getID(id).style.display = "none"; }
	static hideSelectors(selector) { zl.getSelectors(selector).forEach(e => e.style.display = "none"); }

	//---------- gets -----------

	//get an element by ID
	static getID(ID)
	{
		let elem = document.getElementById(ID);
		if (elem === null) { zl.quipWarn("DOM ID: " + ID + " wasn't found."); }
		return elem;
	}

	//return a single element by class ( returns first element if multiple )
	static getClass(className)
	{
		let elems = document.getElementsByClassName(className);
		if (elems === null) { zl.quipWarn("DOM class: [" + className + "] doesn't have an element."); }
		return elems[0];
	}

	//return an array of elements by class
	static getClasses(className)
	{
		let elems = document.getElementsByClassName(className);
		if (elems === null) { zl.quipWarn("DOM class: [" + className + "] doesn't have any elements."); }
		return elems;
	}

	//return the first element from a selector search.
	static getSelector(selector)
	{
		let elems = document.querySelector(selector);
		if (elems === null) { zl.quipWarn("DOM selector: [" + selector + "] doesn't have an element."); }
		return elems[0];
	}

	//return all items from a selector search ( array )
	static getSelectors(selector)
	{
		let elems = document.querySelectorAll(selector);
		if (elems === null) { zl.quipWarn("DOM selector: [" + selector + "] doesn't have any elements."); }
		return elems;
	}

	// -- general purpose stuff

	//Activate draggability of a div by a child div.
	static addDrag(divToMove, divToGrab)
	{
	  divToMove = zl.getID(divToMove); divToGrab = zl.getID(divToGrab);
	  var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

	  divToGrab.onmousedown = dragStart;
	  function dragStart(e)
	  {
		//attempt to unset margin auto positioning in modal - currently not working..
		let currentPos = divToGrab.getBoundingClientRect();
		divToMove.style.top = (currentPos.x) + "px";
	    divToMove.style.left = (currentPos.y) + "px";
		divToMove.setAttribute("style", "margin:unset;");

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
	  }

	  //stop moving when mouse button is released
	  function dragStop()
	  {
	    document.onmouseup = null; document.onmousemove = null;
	    divToGrab.style.cursor='default';
	  }

	  zl.quipD(divToMove + " can now be dragged by " + divToGrab + ".");
	}

	//------ HTMX related -------//


	static hxError(event)
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
		else { zl.quipWarn("There was a request error early in page rendering that couldn't be caught."); }
	}


	//------ Zpage related features. If this section grows, break it into zpage.js -------/


	//hide the side navigation bar
	static hideNav() { $(".button-collapse").sideNav('hide'); } //console.log('closed');

	//------ ZUI related functions. If this ection grows, break it out into zui.js -------//

	//for zui::readMore
	static readMore(name)
	{
	  var dots = zl_ID("zl_RM_" + name + "_dot");
	  var moreText = zl_ID("zl_RM_" + name + "_more");
	  var btn = zl_ID("zl_RM_" + name + "_button");

	  if(dots.style.display === "none")
	  { dots.style.display = "inline"; btn.innerHTML = "expand_circle_down"; moreText.style.display = "none"; }
	  else
	  { dots.style.display = "none"; btn.innerHTML = "expand_less"; moreText.style.display = "inline"; }
	}

	//close ZL modal
	static modalClose(submodal = false, reason = "", callFunc = "")
	{
		if(!submodal) { zl.quipD("Modal(primary) closing - " + reason); var dialog = zl.getID("zl_modal"); }
		else { zl.quipD("Modal(sub) closing. - " + reason); var dialog = zl.getID("zl_modal_sub"); }

		if(callFunc != "") { window[functionToCall](); } // call the function name sent as a string.
		dialog.close(); dialog.remove();
	}

	//open a ZL modal
	//todo: allow submodals; needs some hooking up.
	//multiple dialogs in action: https://css-tricks.com/some-hands-on-with-the-html-dialog-element/
	//known bugs: won't open/reopen ultra rapidly when using the close button; may have to do with hanging events
	static modalOpen(hxURL, modalTitle = "Untitled New Modal", callFuncOnClose = "", submodal = false)
	{
		function modalDomIsLoaded()
		{
			if(!submodal) { var dialogName = "zl_modal"; } else { var dialogName = zl.getID("zl_modal_sub"); }
			let dialogInnerName = dialogName + "_inner";

			//create the outer dialog element.
			var dialog = document.createElement("dialog");
			dialog.setAttribute("id", dialogName);
			dialog.style.opacity = 0; //starts out invisible so we can measure it's size.
			document.body.appendChild(dialog); //ends up at bottom of body tag

			//create title bar
			let titleBar = document.createElement("div");
			titleBar.className = "zl_modal_title";
			titleBar.id = "zl_modal_titlebar";
			titleBar.innerHTML = modalTitle;

			//create closeButton in title bar
			let closeButton = document.createElement('closeButton');
			closeButton.innerHTML = '<div class="zl_right"><span class="zlt_fakeButton"><i class="zlt_micon white">close</i></div>';
			titleBar.appendChild(closeButton);

			//add content div
			let content = document.createElement("div");
			content.className = dialogInnerName;

			zl.quipD("Modal Opening. Waiting for AJAX content.");
			dialog.appendChild(titleBar);
			dialog.appendChild(content);
			dialog.showModal();

			//Gradually scales in. Thanks chatGPT
			function animateDiv(eleID)
			{
				zl.quipD("Modal AJAX content received. Animating.");

				let steps = 10;       //define how many steps ( hence limiting framerate )
				let durationMS = 200; //define total duration of animation
				let ele = zl.getID(eleID);
				let eleInner = zl.getClass(eleID + "_inner");
				let initialWidth = ele.clientWidth;
				let initialHeight = ele.clientHeight;

				//ok, let's get on it already.
				for(let step = 1; step <= steps; step++)
				{
					setTimeout(() =>
					{
					    ele.style.width = ((initialWidth / steps) * step) + 'px';
					    ele.style.height = ((initialHeight / steps) * step) + 'px';
					    ele.style.opacity = ((1 / steps) * step);

						if(step == steps) //..and we're done!
						{
							//cleanup after animation
							ele.style.width = null; ele.style.height = null;
							eleInner.style.overflow = "auto";
							//zl.addDrag(dialogName,"zl_modal_titlebar"); //currently broken
						}
					}, (durationMS / steps) * step);
				}
			}

			htmx.ajax('GET', hxURL, '.' + dialogInnerName).then(() => { animateDiv(dialogName); });

			function clickCloseButton(evt) //close and remove the event handler
			{
				this.removeEventListener('click', clickCloseButton);
				this.removeEventListener('click', clickOutside);
				document.removeEventListener('keydown', escKey);
				zl.modalClose(false,"button", callFuncOnClose);
			}

			function clickOutside(evt)
			{
				if(evt.target.tagName == "DIALOG")
				{
					//close and remove the event handler
					this.removeEventListener('click', clickCloseButton);
					this.removeEventListener('click', clickOutside);
					document.removeEventListener('keydown', escKey);
					zl.modalClose(false,"outside click", callFuncOnClose);
				}
			}

			function escKey(evt) //escape key hit
			{
				if (evt.key === "Escape" || evt.keyCode === 27)
				{
					this.removeEventListener('click', clickCloseButton);
					this.removeEventListener('click', clickOutside);
					document.removeEventListener('keydown', escKey);
					zl.modalClose(false,"escape key", callFuncOnClose);
				}
			}

			//Close modal when the close button or outside of the modal is clicked.
			closeButton.addEventListener('click', clickCloseButton);
			dialog.addEventListener('click', clickOutside);
			document.addEventListener('keydown', escKey)
		}

		//wait for the DOM to load so that this modal can load immediately upon page load
		if(zl.domLoaded) { modalDomIsLoaded(); }
		else { document.addEventListener('DOMContentLoaded', modalDomIsLoaded); }
	}
}

zl.init();