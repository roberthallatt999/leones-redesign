import { defineConfig } from 'vite'
import { resolve } from 'path'
import { readdirSync } from 'fs'

// Auto-discover all HTML files in the root for multi-page build
const htmlFiles = readdirSync(__dirname)
  .filter(f => f.endsWith('.html'))
  .reduce((entries, file) => {
    const name = file.replace('.html', '')
    entries[name] = resolve(__dirname, file)
    return entries
  }, {})

export default defineConfig({
  root: '.',
  build: {
    outDir: 'dist',
    rollupOptions: {
      input: htmlFiles,
    },
  },
})
