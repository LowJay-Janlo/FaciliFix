Readable sources (the web server does not serve this folder).

  src/css/base.css    -> css/base.css     shared: top bar, buttons, notices, page layout
  src/css/app.css     -> css/app.css      all admin pages (dashboard, facilities, forms, room table, dialogs)
  src/css/public.css  -> css/public.css   login and landing page
  src/js/*.js         -> js/*.js          page scripts

The files the pages load (css/ and js/) are minified copies. After editing a file in src/,
rebuild its copy, for example:

  npx esbuild src/css/app.css --minify --outfile=css/app.css
  npx esbuild src/js/facilities.js --minify --format=iife --outfile=js/facilities.js
