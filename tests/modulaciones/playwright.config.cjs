const { defineConfig } = require('playwright/test');
module.exports = defineConfig({
    testDir: '../browser', timeout: 30000, workers: 1,
    use: { headless: true, launchOptions: { args: ['--disable-dev-shm-usage'] } },
    outputDir: '/scratch/playwright', reporter: 'line'
});
