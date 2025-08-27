//configure htmx
if(htmx.config.timeout == "") { htmx.config.timeout = 30000; } //30 second http timeout if we didn't previously override this
htmx.includeIndicatorStyles = false;

//htmx global response error handler
htmx.on("htmx:responseError", function (event) { zl.hxError(event); })
htmx.on("htmx:timeout", function (event) { zl.hxError(event); })

//slowly fade the target div out on transmission to indicate something is being done
document.body.addEventListener('htmx:beforeRequest', function(evt)
{
	evt.detail.target.classList.add("htmx-fade-out");
	//causes unfortunate shifting
	//evt.detail.target.innerHTML = "<div class='htmx-spinner-container'>" + evt.detail.target.innerHTML + "<img src = '/zerolith/public/spinner.svg' class='htmx-spinner'></div>";
});

//remove the fade
document.body.addEventListener('htmx:afterRequest', function(evt)
{
	zl.quipD("removing htmx fadeout class.");
	evt.detail.target.classList.remove("htmx-fade-out");
	zl.quipD("fadeout class removed");
});
