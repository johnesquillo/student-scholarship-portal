// Keep navigation and success messages working when browser storage is unavailable.
window.portalStorage = {
    getItem(key) {
        try { return window.localStorage.getItem(key); } catch (error) { return null; }
    },
    setItem(key, value) {
        try { window.localStorage.setItem(key, value); } catch (error) { /* Session-only preference. */ }
    }
};
