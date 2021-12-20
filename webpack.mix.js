let mix = require('laravel-mix');

mix
	.setPublicPath('public/')
	.js('resources/js/app.js', 'public/js').vue()
	.sass('resources/sass/app.scss', 'public/css').options({
		processCssUrls: false
	})
	.sourceMaps()
;