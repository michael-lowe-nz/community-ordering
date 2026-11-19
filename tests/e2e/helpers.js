/**
 * Waits until Alpine has started and set up the page's components.
 *
 * Buttons and inputs driven by Alpine render before its script runs, so on a
 * slow page a click or keystroke can land before anything listens for it.
 */
export async function waitForAlpine(page) {
    await page.waitForFunction(() => {
        const component = document.querySelector('[x-data]');

        return Boolean(window.Alpine && component && component._x_dataStack);
    });
}
