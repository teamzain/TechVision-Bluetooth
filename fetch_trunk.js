const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ headless: true });
    const page = await browser.newPage();
    await page.goto('http://192.168.192.236/admin/config.php?display=trunks');

    // Log in
    await page.type('#username', 'admin');
    await page.type('#password', 'Aa-112233');
    await page.click('button[type="submit"]');
    await page.waitForNavigation();

    // Extract trunk names (adjust the selector to match your page)
    const trunks = await page.evaluate(() => {
        const elements = document.querySelectorAll('.trunk-name-class'); // Replace with actual class name
        return Array.from(elements).map(element => element.textContent.trim());
    });

    // Output the trunk names
    trunks.forEach(trunk => console.log(trunk));

    await browser.close();
})();
