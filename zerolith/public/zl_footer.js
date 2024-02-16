//configure htmx
htmx.config.timeout = 5000; //5 second http timeout
htmx.includeIndicatorStyles = false;

//htmx global response error handler
htmx.on("htmx:responseError", function (event) { zl_handleHXerror(event); })
htmx.on("htmx:timeout", function (event) { zl_handleHXerror(event); })

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
