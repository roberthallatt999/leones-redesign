const fs = require('fs');
const path = require('path');

// Correctly load the package.json contents
const pkgPath = './package.json';
const pkgContents = fs.readFileSync(pkgPath, 'utf8');
const pkg = JSON.parse(pkgContents);

const htmlFilePath = pkg.config.vars.html.template;
const cssDirPath = pkg.config.paths.dist.css;

fs.readFile(htmlFilePath, 'utf8', (err, htmlContent) => {
  if (err) {
    console.error('Error reading the HTML file:', err);
    return;
  }

// Assuming cssFileName is something like "custom.css" from package.json
const baseFileName = pkg.config.vars.css.file.replace('.css', ''); // Removes the extension

// Construct a regex to match "custom-{version}.css"
// This assumes the version is a sequence of digits (\d+)
const versionedCssFileNameRegex = new RegExp(`${baseFileName}-\\d+\\.css`);

// Use this regex to find the versioned filename in the HTML content
const versionedCssFileNameMatch = htmlContent.match(versionedCssFileNameRegex);

if (versionedCssFileNameMatch) {
  const versionedCssFileName = versionedCssFileNameMatch[0];
  const originalCssFileName = pkg.config.vars.css.file; // Use the filename from package.json
  const versionedCssFilePath = path.join(cssDirPath, versionedCssFileName);
  const originalCssFilePath = path.join(cssDirPath, originalCssFileName);
  
    // Rename the versioned CSS file back to original filename
    fs.rename(versionedCssFilePath, originalCssFilePath, (err) => {
      if (err) {
        console.error('Error renaming the CSS file:', err);
        return;
      }

      // Update the HTML content to reference the original CSS filename
      const updatedHtmlContent = htmlContent.replace(versionedCssFileName, originalCssFileName);

      // Write the updated HTML back to the file
      fs.writeFile(htmlFilePath, updatedHtmlContent, 'utf8', (err) => {
        if (err) {
          console.error('Error writing the updated HTML file:', err);
          return;
        }
        console.log('Version control removed from custom.css link.');
      });
    });
  } else {
    console.log('No versioned custom.css file found in HTML.');
    console.log(' ');
  }
});