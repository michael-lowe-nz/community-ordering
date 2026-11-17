export const titleCase = (s) => s.trim().replace(/\s+/g, ' ').replace(/(^|\s)\S/g, (c) => c.toUpperCase());
export const rnd = (n) => Math.round(n * 100) / 100;
export const half = (n) => {
    const w = Math.floor(n + 1e-9);
    return n - w > 0.01 ? (w || '') + '½' : String(w);
};
export const keyOf = (o) => o.name + '|' + (o.variant || '');
export const stepOf = (item) => (item && item.unit ? item.unit.step : 1);
export const startOf = (item) => (item && item.unit ? item.unit.start : 1);
export const plural = (n, word) => n + ' ' + word + (n === 1 ? '' : 's');
