This document is not complete - 1/13/2024 - DS

### What is zl.css?

zl.css is a atomic CSS library written in pure CSS, written as a shorter alternative to Tailwind with a smaller featureset, and most suitable for backend business applications. It includes a theme system, settable in a .css configuration file.

In spirit, it's a design system built by a designer-programmer for programmers.


### What we needed, aka, why we built it:

- The ability to use the CSS framework to progressively enhance an existing application and not conflict with existing CSS classes, which requires pseudo-namespacing.
- A lighter atomic CSS approach, only covering the basics and leaving the fine decoration to regular CSS - covering 80% of the common needs is fine.
- As few moving parts as possible - which naturally leads to a Pure CSS approach.
- As short of a learning curve as possible - relating class names to base HTML/CSS concepts instead of abstractions.
- Unit scales that require little to no memorization or referencing - they should be predictable.
- A swappable palette system that would later make implementing dark mode mostly a palette swap.
- A balance between the shortest possible class names and readability so that our code wouldn't get as cluttered while using atomic CSS.
- We wanted the configuration file that easily functions as a 'style guide' that affects the look of the framework. How wordpress/web builders do this was a big inspiration.
- We wanted a responsive column system that was as easy to code as advanced website builders make it. We design applications as desktop-first, and this allows us to very efficiently add mobile phone and tablet compatibility.
- A modular file structure, separated by roles ( framework, palette, controls, config file ). This optimizes readability and hackability. 4000 line CSS files are not fun to work on!


### How does it compare to...

Bootstrap:

1. zl.css is roughly equivalent to bootstrap in terms of functionality; and some features are more elegant / shorter / clearer.
2. Unlike bootstrap, zl.css doesn't impose unexpected paddings/margin/formatting. This allows more manual control.
3. zl.css has much better theme and palette control.
4. Code written with zl.css tends to be a little shorter than bootstrap.
5. The combined CSS files of zl.css is roughly 100kb unminified where bootstrap is 50kb minified.
6. zl.css names it's tags with HTML/CSS concepts; we think this is clearer.
7. zl.css' numeric scale is easier to memorize and tinker with in an IDE that has good CSS autocompletion.

Tailwind:

1. Tailwind's size, gzipped, is over 10x the size of zl.css.
2. Tailwind encourages using atomic CSS for everything, which can produce huge strings of classes in divs, negating it's benefits down the line. zl.css has a more limited set of functionality to discourage this; we don't think you should use atomic CSS for everything. The way we use it is, utility classes for general styling, css files for very specific or rare styling. This results in ~10% of the handwritten CSS we used to write before.
3. Tailwind uses various scales that are varied and often abstract. zl.css uses literal and much more consistent numeric scales.
4. Tailwind's default palette has a limited dynamic range, some inconsistencies in scale, and isn't fully perceptually adjusted ( Material colors have the same problem, which our palette is based on )
5. Tailwind has a compile step.


### Limitations of zl.css

1. We don't have automatic compilation of atomic styles to cut down HTML size right now. We have short class names as a compromise.
2. Compared to Tailwind, we sacrifice a big range of possible features and CSS processing abilities by not using JS.
3. There is no minified version yet. ( benefits are very minimal - so this is TBD )


### How do i use it?

See zl.css.md, and the demo html file for examples of it's use.