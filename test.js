const puppeteer = require('puppeteer');

const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

(async () => {
    const browser = await puppeteer.launch({ headless: true });
    const page = await browser.newPage();
    
    await page.goto('http://192.168.192.236/admin/config.php?display=trunks', { waitUntil: 'networkidle2' });
    await delay(5000); // Additional wait time

    // Take a screenshot for debugging
    await page.screenshot({ path: 'screenshot_before_login.png' });

    // Wait for login elements
    try {
        await page.waitForSelector('#username', { timeout: 60000 });
        await page.waitForSelector('#password', { timeout: 60000 });
        await page.waitForSelector('button[type="submit"]', { timeout: 60000 });
    } catch (error) {
        console.error('Error waiting for selectors:', error);
        await page.screenshot({ path: 'screenshot_after_error.png' });
        console.log(await page.content()); // Output page content for debugging
        await browser.close();
        return;
    }

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
