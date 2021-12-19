let mix = require('laravel-mix');

mix
	.js('js/app.js', 'dist').vue()
	.sass('css/app.scss', 'dist').options({
		processCssUrls: false
	})
	.sourceMaps()
;