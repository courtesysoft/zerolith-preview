//Zerolith Javascript library V2.
//06-14-2023 - v0.5 - Experimental full rewrite to static class.
//07-10-2023 - v0.55 - Fixed escape key triggering
//02-01-2024 - v0.6 - Add delete/show/hide and other functions from zl.js
//11-08-2024 - v0.7 - Add <zui-select> custom element

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
			zl.defineCustomElements();
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
	//Bug warning: this version doesn't retain the existing position for whatever reason :(
	static addDrag(divToMove, divToGrab)
	{
		let toMoveName = divToMove;
		let toGrabName = divToGrab;
		divToMove = zl.getID(divToMove);
		divToGrab = zl.getID(divToGrab);
		var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

		divToGrab.onmousedown = dragStart;

		function dragStart(e)
		{
			//attempt to unset margin auto positioning in modal - currently not working..
			let currentPos = divToGrab.getBoundingClientRect();
			console.log(currentPos);
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

			zl.quipD('dragstart');
		}

		function moveElement(e)
		{
			e = e || window.event;
			e.preventDefault();

			// calculate the new cursor position:
			pos1 = pos3 - e.clientX;
			pos2 = pos4 - e.clientY;
			pos3 = e.clientX;
			pos4 = e.clientY;
			// set the element's new position:
			divToMove.style.top = (divToMove.offsetTop - pos2) + "px";
			divToMove.style.left = (divToMove.offsetLeft - pos1) + "px";
		}

		//stop moving when mouse button is released
		function dragStop(e)
		{
			document.onmouseup = null;
			document.onmousemove = null;
			divToGrab.style.cursor='default';
			zl.quipD('dragstop');
		}

	  zl.quipD(toMoveName + " can now be dragged by " + toGrabName + ".");
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
	static modalClose(submodal = false, reason = "", callFuncOnClose = "")
	{
		if(!submodal) { zl.quipD("Modal(primary) closing - " + reason); var dialog = zl.getID("zl_modal"); }
		else { zl.quipD("Modal(sub) closing. - " + reason); var dialog = zl.getID("zl_modal_sub"); }

		if(callFuncOnClose != "") { window[callFuncOnClose](); } // call the function name sent as a string.
		dialog.close(); dialog.remove();
	}

	//open a ZL modal
	//todo: allow submodals; needs some hooking up, submodals in action: https://css-tricks.com/some-hands-on-with-the-html-dialog-element/
	//known bugs: won't open/reopen ultra rapidly when using the close button; may have to do with hanging events
	//known bugs: if request times out, modal greys out screen instead of showing error message
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

			//Gradually scales in from 0px/0px size. Thanks chatGPT!
			function animateDiv(eleID)
			{
				zl.quipD("Modal AJAX content received. Animating.");

				let steps = 10;       //define how many steps ( hence limiting framerate )
				let durationMS = 200; //define total duration of animation
				let ele = zl.getID(eleID);
				let eleInner = zl.getClass(eleID + "_inner");

				//measure the size of the content while it's hidden
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
							addDrag(dialogName,"zl_modal_titlebar");
							//currently broken by margin:auto; in dialog element

							//zl.addDrag(dialogName,"zl_modal_titlebar");
							//^-- this version not working
						}
					}, (durationMS / steps) * step);
				}
			}

			//do HTMX ajax request and execute animation function above
			htmx.ajax('GET', hxURL, '.' + dialogInnerName).then(() => { animateDiv(dialogName); });

			function clickCloseButton(evt) //close and remove the event handler
			{
				this.removeEventListener('click', clickCloseButton);
				this.removeEventListener('click', clickOutside);
				document.removeEventListener('keydown', escKey);
				zl.modalClose(false,"button", callFuncOnClose);
			}

			function clickOutside(evt) //clicked outside of modal ( close it )
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
				if(evt.key === "Escape" || evt.keyCode === 27)
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

	// Define custom elements, should only be called by init()
	static defineCustomElements()
	{
		/* 
		Custom <select> supporting images in options
		Interactivity and accessibility should be near-identical to native <select>

		TODO: fix minor bugs in selected option when value of selected option is changed client-side
		TODO: test in Safari

		Example:
			<zui-select name="custom">
				<option value="">--</option>
				<option value="test1" data-icon="https://randomfox.ca/images/61.jpg">test1</option>
				<option value="test2" data-icon="https://randomfox.ca/images/84.jpg">test2</option>
				<option value="test3" data-icon="https://randomfox.ca/images/76.jpg" selected>test3</option>
			</zui-select>
		*/
		if(!customElements.get('zui-select'))
		{
			const elementInternalsHasBrowserSupport = 'ElementInternals' in window && 'form' in ElementInternals.prototype

			customElements.define('zui-select', class ZuiSelect extends HTMLElement {
				// Setup form association
				static formAssociated = elementInternalsHasBrowserSupport

				// Observe attributes we care about changing so we can update our state accordingly
				static observedAttributes = ['required']

				// Define private fields
				__internals
				__selectedDisplay
				__optionsDropdown
				__value
				__observer
				__searchTerm = ''
				__searchDebounce

				constructor() {
					super()
					this.attachShadow({ mode: 'open' })
					
					if(elementInternalsHasBrowserSupport) {
						this.__internals = this.attachInternals()
					} else {
						// Polyfill required methods of ElementInternals
						const el = this
						this.__internals = new class {
							get form() { return el.closest('form') }
							setFormValue(value) { this.__input.value = value; }
							setValidity() { /* stub, this is automatic */ }
						}
					}

					// Setup accessibility and tab functionality
					this.setAttribute('role', 'combobox')
					this.setAttribute('aria-expanded', false)
					this.setAttribute('aria-controls', 'dropdown')
					this.tabIndex = 0

					// Setup event listeners on the element itself
					// This can't be done in connectedCallback or it'll duplicate them if the element is moved client-side
					this.addEventListener('click', this.handleClick.bind(this))
					this.addEventListener('focusout', this.handleFocusOut.bind(this))
					this.addEventListener('keydown', this.handleKeydown.bind(this))

					// Setup mutation observer for updating changed <option>s
					// NOTE: This is a big source of accidental loops / crashes when changing how the component updates its internals. Be careful!
					this.__observer = new MutationObserver(() => this.updateOptions(true))
					this.__observer.observe(this, { 
						subtree: true, 
						childList: true, 
						characterData: true ,
						attributeFilter: ['value', 'label', 'disabled', 'data-icon']
					})
				}

				connectedCallback() {
					// Create shadow DOM contents
					this.shadowRoot.innerHTML = `
						<div id="selected" part="option selected" aria-hidden="true"></div>
						<ul id="dropdown" part="dropdown" role="listbox"></ul>
						<style>${ZuiSelect.__stylesheet}</style>
					`
					if(!elementInternalsHasBrowserSupport) {
						this.__internals.__input = document.createElement('input')
						this.__internals.__input.setAttribute('id', this.getAttribute('id') ?? this.getAttribute('name') ?? (()=>{throw 'zui-select missing both name and ID'})())
						this.__internals.__input.setAttribute('name', this.getAttribute('name') ?? this.getAttribute('id') ?? (()=>{throw 'zui-select missing both name and ID'})())
						if(this.hasAttribute('disabled')) this.__internals.__input.setAttribute('disabled', '')
						if(this.hasAttribute('required')) this.__internals.__input.setAttribute('required', '')
						this.__internals.__input.setAttribute('hidden', '')
						this.insertAdjacentElement('afterend', this.__internals.__input)
					}

					// Store references to important elements for functionality
					this.__selectedDisplay = this.shadowRoot.getElementById('selected')
					this.__optionsDropdown = this.shadowRoot.getElementById('dropdown')

					this.updateOptions(false)           // Create options from the light DOM <option> tags
					this.selectDefault()                // Select the correct option based on initial state
					this.__updateValidation()           // Update validation state
					this.form = this.__internals.form   // Expose the associated form the way builtin form elements do

					this.__internals.__input?.addEventListener('invalid', ev => { 
						ev.preventDefault()
						this.focus()
						this.dataset.reportValidity = ''
					})
				}

				// The current form element value (read-only)
				get value() { return this.__value }

				// The currently selected option
				// Setting this will un-select all other options and highlight the selected option
				get selectedOption() { return this.shadowRoot.querySelector('li[data-selected]') }
				set selectedOption(optionElement) {
					this.__optionsDropdown.querySelectorAll('li[data-selected]').forEach(el => {
						el.removeAttribute('data-selected')
						el.setAttribute('aria-current', false) // Note that aria-selected means highlighted and aria-current means selected, yes it's weird
					})
					optionElement.setAttribute('data-selected', '')
					optionElement.setAttribute('aria-current', true)

					this.__value = optionElement.dataset.value
					this.__internals.setFormValue(optionElement.dataset.value)
					this.__updateValidation() // Update validation state

					this.__selectedDisplay.innerHTML = optionElement.innerHTML
					this.highlightedOption = optionElement

					this.dispatchEvent(new Event('change', { bubbles: true }))
				}

				// Set selected option to last with selected attr, or to first if none selected
				// This handles both defaulting to the first option, and preventing multiple selections on load
				selectDefault() {
					this.selectedOption = 
						[...this.__optionsDropdown.querySelectorAll('li[data-selected]')].pop() 
						?? this.__optionsDropdown.querySelector('li')
				}

				// The currently highlighted option, either by hover or arrow key selection
				// Setting this will un-highlight all other options
				get highlightedOption() { return this.shadowRoot.querySelector('li[data-highlighted]') ?? this.selectedOption }
				set highlightedOption(optionElement) {
					this.__optionsDropdown.querySelectorAll('li[data-highlighted]').forEach(el => {
						el.removeAttribute('data-highlighted')
						el.setAttribute('aria-selected', false) // Note that aria-selected means highlighted and aria-current means selected, yes it's weird
						el.part.remove('highlighted')
					})
					optionElement?.setAttribute('data-highlighted', '')
					optionElement?.setAttribute('aria-selected', true)
					optionElement?.part.add('highlighted')
				}

				// Toggle the state of the options dropdown, or pass a boolean to force open/closed
				get open() { return this.__optionsDropdown.part.contains('open') }
				toggleDropdown(openState) {
					this.setAttribute('aria-expanded', this.__optionsDropdown.part.toggle('open', openState))
				}

				// Convert <option> tags in the light DOM to custom options in shadow DOM
				updateOptions(updateSelected) {
					let newElements = []

					for(let childElement of this.children) {
						// Convert <option> elements
						if(childElement.tagName === 'OPTION') {
							newElements.push(this.__updateOption(childElement))
						}

						// Convert <hr> elements
						if(childElement.tagName === 'HR') {
							newElements.push(document.createRange().createContextualFragment(`
								<hr part="option-separator" aria-hidden="true">
							`))
						}

						// Convert <optgroup> elements
						if(childElement.tagName === 'OPTGROUP') {
							if(!childElement.hasAttribute('label')) { console.error('Unlabeled <optgroup> in <zui-select>', childElement) }

							let el = document.createRange().createContextualFragment(`
								<ul role="group" part="option-group ${childElement.hasAttribute('disabled') ? 'disabled' : ''}" 
										aria-disabled="${childElement.hasAttribute('disabled')}"
										aria-label="${childElement.getAttribute('label') ?? '- Unlabeled Group -'}"
								>
									<span part="option-group-label" aria-hidden="true">${childElement.getAttribute('label') ?? '- Unlabeled Group -'}</span>
								</ul>
							`)
							
							let group = el.querySelector('ul')
							for(let groupedElement of childElement.children) {
								if(groupedElement.tagName !== 'OPTION') { continue }
								group.append(this.__updateOption(groupedElement))
							}
							newElements.push(el)
						}
					}

					// Load the converted elements into shadow DOM
					this.__optionsDropdown.replaceChildren(...newElements)

					// Handle changing the value of the selected option
					if(updateSelected && !this.__optionsDropdown.querySelector(`li[data-value="${this.value}"]`)) {
						this.selectedOption = this.__optionsDropdown.querySelector('li[data-value]')
					}

					// Update icon column state
					// This works by setting a CSS variable to null when the column should show, and 0px when it shouldn't
					// The icon column uses this to determine its width, and falls back to --icon-size if not set (null)
					//
					let iconColumn = this.__optionsDropdown.querySelector('img:not([src=""])') ? null : '0px'
					this.__selectedDisplay.style.setProperty('--icon-column', iconColumn)
					this.__optionsDropdown.style.setProperty('--icon-column', iconColumn)
				}

				// Update an option from its source <option> by value, or create a new element
				__updateOption(optionElement) {
					// Update the existing option in place if applicable, matching by value
					// TODO: change this so it works better if the value is what changed
					let existingElement = this.__optionsDropdown.querySelector(`li[data-value="${optionElement.value}"]`)
					if(existingElement) {
						existingElement.firstElementChild.src = optionElement.dataset.icon ?? this.dataset.iconFallback ?? ''
						existingElement.lastElementChild.textContent = optionElement.getAttribute('label') ?? optionElement.textContent
						existingElement.setAttribute('aria-label', optionElement.getAttribute('label') ?? optionElement.textContent)
						existingElement.part.toggle('disabled', optionElement.hasAttribute('disabled'))
						if(this.selectedOption === existingElement) this.selectedOption = existingElement
						return existingElement
					}

					// Otherwise create a new option and return it
					let newElement = document.createRange().createContextualFragment(`
						<li 
								role="option" part="option ${optionElement.hasAttribute('disabled') ? 'disabled' : ''}"
								data-value="${optionElement.value}" 
								${optionElement.hasAttribute('selected') ? 'data-selected' : ''} 
								aria-selected="false"
								aria-current="${optionElement.hasAttribute('selected')}"
								aria-disabled="${optionElement.hasAttribute('disabled')}"
								aria-label="${optionElement.getAttribute('label') ?? optionElement.textContent}"
						>
							<img src="${optionElement.dataset.icon ?? this.dataset.iconFallback ?? ''}" part="option-icon" role="presentation">
							<span part="option-label" aria-hidden="true">${optionElement.getAttribute('label') ?? optionElement.textContent}</span>
						</li>
					`).firstElementChild

					newElement.addEventListener('mouseover', () => this.highlightedOption = newElement)
					return newElement
				}

				// Clicking on the combobox toggles the list
				// Clicking on an option selects it and closes the list
				handleClick(event) {
					let target   = event.explicitOriginalTarget ?? event.composedPath()[0]
					let targetLi = target?.closest('li[data-value]')
					let targetSD = target?.closest('#selected')

					if(!this.open) {                   // If closed, open
						this.toggleDropdown(true)
					} else if(targetLi) {              // If open and clicked option, select and close
						this.selectedOption = targetLi
						this.toggleDropdown(false)
					} else if(targetSD) {              // If open and clicked outer box, close
						this.toggleDropdown(false)
					}                                  // Else, ignore
				}

				// Tabbing away selects highlighted option and closes list
				// Enter has the same functionality, see handleKeydown
				handleFocusOut(event) {
					if(this.open) {
						this.toggleDropdown(false)
						this.selectedOption = this.highlightedOption
						setTimeout(() => this.focus())   // Steal focus back to match default <select> behavior
					}
				}

				// All other keyboard interactions
				handleKeydown(event) {
					// Alt + Arrow Up/Down toggles list without changing selection
					if(['ArrowDown','ArrowUp'].includes(event.code) && event.altKey) { this.toggleDropdown(); return }

					// Type to select first matching option, or repeatedly tap the first letter to cycle through matching options
					let searchKey = event.key.toUpperCase()
					let matchesKey = key => o => o.getAttribute('aria-label').toUpperCase().startsWith(key)

					if(/^[A-Z0-9]$/.test(searchKey) || (this.__searchTerm.length && searchKey.length === 1)) {
						this.__searchTerm += searchKey
						clearTimeout(this.__searchDebounce)
						this.__searchDebounce = setTimeout(() => this.__searchTerm = '', 1000)

						let mode = this.open ? 'highlighted' : 'selected'
						let cycle = new Set(this.__searchTerm.split('')).size === 1

						if(cycle) {
							this.__shiftOption(mode, 1, true, matchesKey(searchKey))
						} else {
							if(!matchesKey(this.__searchTerm)(this[`${mode}Option`])) {
								this.__shiftOption(mode, 1, true, matchesKey(this.__searchTerm))
							}
						}
					}

					if(this.open) {
						// Escape closes list
						if(event.code === 'Escape') { this.toggleDropdown(false) }

						// Enter selects highlighted option and closes list
						// Tabbing away has the same functionality, see handleFocusOut
						if(event.code === "Enter") {
							this.selectedOption = this.highlightedOption
							this.toggleDropdown(false)
						}

						// Arrow Up/Down on open list selects prev/next option, wrapping
						if(event.code === 'ArrowUp') { this.__shiftOption('highlighted', -1, true) }
						if(event.code === 'ArrowDown') { this.__shiftOption('highlighted', 1, true) }
					} else {
						// Space opens list
						if(event.code === 'Space') { this.toggleDropdown(true) }

						// Arrow Up/Down on closed list selects prev/next option, non-wrapping
						if(event.code === 'ArrowUp') { this.__shiftOption('selected', -1, false) }
						if(event.code === 'ArrowDown') { this.__shiftOption('selected', 1, false) }
					}
				}

				// Shift the selection or highlight to the nearest matching option in a given direction
				// • mode is either 'selected' or 'highlighted'
				// • direction is -1 for previous or 1 for next
				// • wrap is a bool of whether to wrap to the top/bottom of the list if no match found prior
				// • predicate is a function called for each option to check if it meets additional criteria 
				// 
				__shiftOption(mode, direction, wrap = false, predicate = ()=>true) {
					// Collate list of option elements; all lis that have a value and are not disabled nor disabled by parent
					let optionList   = [...this.__optionsDropdown.querySelectorAll(':not([part*="disabled"]) > li:not([part*="disabled"])')]
					let startOption  = this[`${mode}Option`]
					let currentIndex = optionList.indexOf(startOption)

					do {
						currentIndex += direction
						if(!optionList[currentIndex]) {
							if(wrap) currentIndex = direction === 1 ? 0 : optionList.length-1
							else { currentIndex -= direction; break }
						}
						if(predicate(optionList[currentIndex])) break
					} while(currentIndex !== optionList.indexOf(startOption))

					return this[`${mode}Option`] = optionList[currentIndex]
				}

				// Handle form state updates
				formResetCallback() {
					this.selectedOption = this.__optionsDropdown.querySelector('li[data-value]')
				}
				formStateRestoreCallback(value) {
					this.selectedOption = this.__optionsDropdown.querySelector(`li[data-value="${value}"]`)
				}

				// Handle attribute updates
				attributeChangedCallback(name, oldValue, newValue) {
					if(name === 'required') { this.__updateValidation() }
				}
				__updateValidation() {
					this.__internals.setValidity({ valueMissing: this.hasAttribute('required') && !this.value }, 'Please select an option in the list.')
					if(!elementInternalsHasBrowserSupport) {
						if(this.__internals.__input?.checkValidity()) delete this.dataset.invalid
						else this.dataset.invalid = ''
					}
				}

				// Internal stylesheet
				static __stylesheet = `
					/* Minimal reset */
					*, ::after, ::before { position: relative; box-sizing: border-box; }

					/* Styles applying to the <zui-select> element itself */
					:host {
						--icon-size: 24px;   /* W/H of option icons and default W of icon column if applicable */

						position: relative;
						display: inline-grid;
						grid-template-columns: 1fr 14px;
						gap: 10px;
						align-items: center;
						background: white;
						border: 1px solid currentColor;
						font-family: system-ui, sans-serif;
						font-size: .85em;
						cursor: default;
						user-select: none;
					}
					:host::after {
						content: '';
						display: block;
						width: 6px;
						height: 6px;
						border-right: 1px solid currentColor;
						border-bottom: 1px solid currentColor;
						transform: rotate(45deg) translateY(-2px);
					}
					:host([disabled]),
					:host::part(disabled) {
						color: #777;
						pointer-events: none;
						filter: grayscale();
					}
					
					/* Validity polyfill styles */
					:host([data-report-validity][data-invalid]) {
						outline: 2px solid red;
					}
					
					/* Styles applying to the options dropdown */
					:host::part(dropdown) {
						display: block;
						list-style: none;
						position: absolute;
						top: calc(100% + 1px);
						left: -1px;
						z-index: 99999;
						margin: 0;
						padding: 0;
						width: max-content;
						min-width: calc(100% + 2px);
						background: #fff;
						border: 1px solid #ccc;
						clip-path: rect(0 0 0 auto); /* Hide without removing from accessibility tree */
					}
					:host::part(dropdown open) {
						clip-path: unset;
					}

					/* Styles applying to individual options, including the selected option display */
					:host::part(option) {
						display: grid;
						grid-template-columns: var(--icon-column, var(--icon-size)) 1fr;   /* Hide/show icon column if no icons present */
						align-items: center;
						height: var(--icon-size);
						overflow-x: clip;
						white-space: nowrap;
					}
					:host::part(option highlighted) {
						background: #eee;
					}
					:host::part(option selected) {
						min-width: 0;
					}
					:host::part(option-icon) {
						width: var(--icon-size);
						height: var(--icon-size);
						object-fit: cover;
					}
					img[src=""] {
						visibility: hidden;
					}
					:host::part(option-label) {
						padding-inline: 5px;
					}

					/* Styles applying to <optgroup>s */
					:host::part(option-group) {
						display: block;
						padding-left: 20px;
					}
					:host::part(option-group-label) {
						display: block;
						margin-left: -10px;
						height: var(--icon-size);
						font-weight: bold;
					}

					/* Styles applying to <hr>s */
					:host::part(option-separator) {
						border: 0;
						height: 1px;
						background: #ccc;
					}
				`
			})
		}
	}
}

zl.init();