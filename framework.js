Element.prototype.is = Element.prototype.matchesSelector || Element.prototype.webkitMatchesSelector;

function ajax(url, callback, data) {
	var method = data ? 'post' : 'get',
		xhr = new XMLHttpRequest;
	xhr.open(method, url, true);
	xhr.onreadystatechange = function(e) {
		if ( this.readyState == 4 ) {
			callback.call(this, this.responseText, e);
		}
	};
	xhr.send(data);
}
