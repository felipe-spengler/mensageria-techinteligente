const puppeteer = require('@wppconnect-team/wppconnect/node_modules/puppeteer-core');

(async () => {
  console.log('Attempting launch...');
  try {
    const browser = await puppeteer.launch({
      executablePath: '/usr/bin/chromium',
      headless: 'new',
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu'
      ]
    });
    console.log('SUCCESS! Browser launched.');
    await browser.close();
  } catch (e) {
    console.error('Launch failed:');
    console.error(e);
  }
})();
