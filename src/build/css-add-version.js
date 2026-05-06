const fs = require('fs');
const path = require('path');

const pkgPath = './package.json';
const pkgContents = fs.readFileSync(pkgPath, 'utf8');
const pkg = JSON.parse(pkgContents);

const htmlFilePath = pkg.config.vars.html.template;
const cssDirPath = pkg.config.paths.dist.css;
const cssFileName = pkg.config.vars.css.file;
const version = Date.now(); // Simple versioning using timestamp

fs.readFile(htmlFilePath, 'utf8', (err, htmlContent) => {
  if (err) {
    console.error('Error reading the HTML file:', err);
    return;
  }

  const versionedCssFileName = cssFileName.replace('.css', `-${version}.css`);
  const versionedCssFilePath = path.join(cssDirPath, versionedCssFileName);
  const originalCssFilePath = path.join(cssDirPath, cssFileName);

  // Match either the unversioned filename (e.g. "custom.css") OR a previously
  // versioned filename (e.g. "custom-1234567890.css") in the HTML, so we can
  // update the link tag regardless of prior state.
  const baseFileName = cssFileName.replace('.css', '');
  const escapedBase = baseFileName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const cssRefRegex = new RegExp(`${escapedBase}(?:-\\d+)?\\.css`);
  const previousMatch = htmlContent.match(cssRefRegex);

  // Rename the freshly-built CSS file to its versioned name
  fs.rename(originalCssFilePath, versionedCssFilePath, (err) => {
    if (err) {
      console.error('Error renaming the CSS file:', err);
      return;
    }

    // Delete the previously referenced versioned file (if any) so orphans
    // don't accumulate in the css/ directory.
    const cleanupOrphan = (cb) => {
      if (!previousMatch || previousMatch[0] === cssFileName || previousMatch[0] === versionedCssFileName) {
        return cb();
      }
      const orphanPath = path.join(cssDirPath, previousMatch[0]);
      fs.unlink(orphanPath, (unlinkErr) => {
        if (unlinkErr && unlinkErr.code !== 'ENOENT') {
          console.warn('Could not remove previous CSS file:', orphanPath, unlinkErr.message);
        }
        cb();
      });
    };

    cleanupOrphan(() => {
      const updatedHtmlContent = previousMatch
        ? htmlContent.replace(cssRefRegex, versionedCssFileName)
        : htmlContent;

      fs.writeFile(htmlFilePath, updatedHtmlContent, 'utf8', (err) => {
        if (err) {
          console.error('Error writing the updated HTML file:', err);
          return;
        }
        if (!previousMatch) {
          console.warn(`Warning: no "${cssFileName}" or versioned reference found in ${htmlFilePath}; HTML left unchanged.`);
        }
        console.log('CSS file versioned and HTML updated.');
      });
    });
  });
});