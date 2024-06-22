/*
 |--------------------------------------------------------------------------
 | Browser-sync config file
 |--------------------------------------------------------------------------
 |
 | For up-to-date information about the options:
 |   http://www.browsersync.io/docs/options/
 |
 | There are more options than you see here, these are just the ones that are
 | set internally. See the website for more info.
 |
 |
 */

const fs = require('fs');

const pkgPath = './package.json';
const pkgContents = fs.readFileSync(pkgPath, 'utf8');
const pkg = JSON.parse(pkgContents);

 module.exports = {
    "files": pkg.config.browsersync.files.watch,
    "watch": true,
    "watchOptions": {
      "usePolling": true,
      "interval": 500,
    },
    "server": false,
    "proxy": pkg.config.browsersync.proxyUrl,
    "port": pkg.config.browsersync.port,
    "open": "false",
    "https": {
        key: pkg.config.browsersync.sslKey,
        cert: pkg.config.browsersync.sslCert,
    },
    "snippetOptions": {
      rule: {
        match: /<\/body>/i, // Inject the script before the closing </body> tag
        fn: function (snippet, match) {
          return `
            <script>
              // This script appends a timestamp query parameter to each request to force cache busting.
              document.addEventListener('DOMContentLoaded', function() {
                const links = document.querySelectorAll('link[rel="stylesheet"], script[src]');
                links.forEach(link => {
                  if (link.href) {
                    link.href += (link.href.includes('?') ? '&' : '?') + 'nocache=' + (new Date()).getTime();
                  } else if (link.src) {
                    link.src += (link.src.includes('?') ? '&' : '?') + 'nocache=' + (new Date()).getTime();
                  }
                });
              });
            </script>
          ` + snippet + match;
        }
      }
    },
    "middleware": [
      function (req, res, next) {
        res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate'); // Set headers to prevent caching
        res.setHeader('Pragma', 'no-cache');
        res.setHeader('Expires', '0');
        next();
      }
    ]
};