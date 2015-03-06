String.prototype.escaped = function () {
	return this.replace(/&/gim, "&amp;").replace(/</gim, "&lt;").replace(/>/gim, "&gt;").replace(/"/gim, "&quot;").replace(/'/gim, "&#39;");
}