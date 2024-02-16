//zl.js static class experiment
'use strict';

class zl
{
	static #privProperty = 'private var'; //mozilla dev network says the # should indicate a private field
	static pubProperty = 'public var';
	static deBuffer = '';

	static //ZL class initializer
	{
		console.log('ZL.js has initialized; trying to read a variable from class: ' + this.test());
	}

	static test() { return this.#privProperty; }

	static quip(message, regarding = '')
	{
		console.log('%cQuip: ' + regarding, 'color:blue;');
		console.log(message);
	}
}

41

zl.quip(zl.test(), "test quip");
console.log(zl.pubProperty);
console.log(zl.privProperty);   //10/18/22 - Chromium on Linux doesn't respect this and prints out the variable.
//console.log(privProperty);    //this causes the script to halt ( expected )