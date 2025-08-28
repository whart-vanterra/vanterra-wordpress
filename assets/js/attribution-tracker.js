(function() {
    'use strict';

    const VT_Attribution = {
        cookiePrefix: 'vt_',
        firstTouchExpiry: 30,
        lastTouchExpiry: 1,
        
        trackingParams: [
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_adgroup',
            'gclid', 'msclkid', 'fbclid', 'ttclid', 'yclid', 'li_fat_id',
            'gbraid', 'wbraid', 'ga_client_id'
        ],

        setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            const isSecure = window.location.protocol === 'https:' ? ';Secure' : '';
            document.cookie = `${name}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/;SameSite=Lax${isSecure}`;
        },

        deleteCookie(name) {
            const isSecure = window.location.protocol === 'https:' ? ';Secure' : '';
            document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;SameSite=Lax${isSecure}`;
        },

        getCookie(name) {
            const nameEQ = name + '=';
            const cookies = document.cookie.split(';');
            for (let cookie of cookies) {
                cookie = cookie.trim();
                if (cookie.indexOf(nameEQ) === 0) {
                    return decodeURIComponent(cookie.substring(nameEQ.length));
                }
            }
            return null;
        },

        tryGetLocalStorage(key) {
            try {
                return window.localStorage.getItem(key);
            } catch (e) {
                return null;
            }
        },

        trySetLocalStorage(key, value) {
            try {
                window.localStorage.setItem(key, value);
            } catch (e) {
                // ignore storage errors (private mode, disabled, quota, etc.)
            }
        },

        tryGetSessionStorage(key) {
            try {
                return window.sessionStorage.getItem(key);
            } catch (e) {
                return null;
            }
        },

        trySetSessionStorage(key, value) {
            try {
                window.sessionStorage.setItem(key, value);
            } catch (e) {
                // ignore storage errors
            }
        },

        generateClientId() {
            return 'vt_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        getGaClientIdFromCookies() {
            // GA/GA4 client ID is typically in the _ga cookie as GAx.x.CLIENTID_PART1.CLIENTID_PART2
            // We will attempt to parse it safely.
            const allCookies = document.cookie.split(';').map(c => c.trim());
            const gaCookie = allCookies.find(c => c.startsWith('_ga='));
            if (gaCookie) {
                const value = decodeURIComponent(gaCookie.split('=')[1] || '');
                const parts = value.split('.');
                // Expect at least 4 parts like GA1.1.1234567890.1234567890
                if (parts.length >= 4) {
                    const clientId = parts.slice(-2).join('.');
                    return clientId;
                }
                // Some setups might store only the client id
                if (parts.length >= 2) {
                    return parts.slice(-2).join('.');
                }
                if (value) return value;
            }

            // GA4 can also create cookies like _ga_<MEASUREMENT_ID>; generally not the raw client id,
            // but in case a proxy writes it, try a generic fallback by finding first _ga_* cookie.
            const gaAnyCookie = allCookies.find(c => /^_ga_/.test(c.split('=')[0] || ''));
            if (gaAnyCookie) {
                const v = decodeURIComponent(gaAnyCookie.split('=')[1] || '');
                // Try the same last-two-parts heuristic
                const parts = v.split('.');
                if (parts.length >= 2) return parts.slice(-2).join('.');
                if (v) return v;
            }
            return null;
        },

        getOrCreateSessionId() {
            let sid = this.tryGetSessionStorage(this.cookiePrefix + 'session_id');
            if (!sid) {
                sid = this.generateClientId();
                this.trySetSessionStorage(this.cookiePrefix + 'session_id', sid);
            }
            return sid;
        },

        getUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const params = {};
            
            this.trackingParams.forEach(param => {
                const value = urlParams.get(param);
                if (value) {
                    params[param] = value;
                }
            });
            
            return params;
        },

        getAttributionData() {
            const params = this.getUrlParams();
            const timestamp = new Date().toISOString();
            const existingClientId = this.getCookie(this.cookiePrefix + 'client_id') || this.tryGetLocalStorage(this.cookiePrefix + 'client_id');
            const clientId = existingClientId || this.generateClientId();
            const sessionId = this.getOrCreateSessionId();

            // Try to populate GA client id from cookies if not present via URL
            if (!params.ga_client_id) {
                const gaCid = this.getGaClientIdFromCookies();
                if (gaCid) {
                    params.ga_client_id = gaCid;
                }
            }

            if (!this.getCookie(this.cookiePrefix + 'client_id')) {
                this.setCookie(this.cookiePrefix + 'client_id', clientId, this.firstTouchExpiry);
            }
            if (!this.tryGetLocalStorage(this.cookiePrefix + 'client_id')) {
                this.trySetLocalStorage(this.cookiePrefix + 'client_id', clientId);
            }

            return {
                ...params,
                landing_page: window.location.href,
                referrer: document.referrer || 'direct',
                touch_time: timestamp,
                client_id: clientId,
                session_id: sessionId
            };
        },

        trackFirstTouch() {
            const existingFirstTouch = this.getCookie(this.cookiePrefix + 'first_touch_time') || this.tryGetLocalStorage(this.cookiePrefix + 'first_touch_time');
            
            if (!existingFirstTouch) {
                const attributionData = this.getAttributionData();
                
                Object.keys(attributionData).forEach(key => {
                    this.setCookie(
                        this.cookiePrefix + 'first_' + key, 
                        attributionData[key], 
                        this.firstTouchExpiry
                    );
                });
                // Mark first touch recorded
                this.setCookie(this.cookiePrefix + 'first_touch_time', attributionData.touch_time, this.firstTouchExpiry);
                this.trySetLocalStorage(this.cookiePrefix + 'first_touch_time', attributionData.touch_time);
                // Store a JSON snapshot in localStorage for resilience
                this.trySetLocalStorage(this.cookiePrefix + 'first_snapshot', JSON.stringify(attributionData));
            }
        },

        trackLastTouch() {
            const attributionData = this.getAttributionData();

            // Build a canonical list of keys to maintain for "last_" scope
            const canonicalKeys = [
                // dynamic tracking params
                ...this.trackingParams,
                // fixed fields that are always set
                'landing_page', 'referrer', 'touch_time', 'client_id', 'session_id'
            ];

            canonicalKeys.forEach(key => {
                const value = attributionData.hasOwnProperty(key) ? attributionData[key] : undefined;
                const cookieName = this.cookiePrefix + 'last_' + key;
                if (value === undefined || value === null || value === '') {
                    // If this param is absent for this touch, clear any previous last-touch value
                    this.deleteCookie(cookieName);
                } else {
                    this.setCookie(cookieName, value, this.lastTouchExpiry);
                }
            });
            // Keep a short-lived snapshot of last touch for forms rendered without cookie access
            this.trySetSessionStorage(this.cookiePrefix + 'last_snapshot', JSON.stringify(attributionData));
        },

        getAllAttributionData() {
            const data = {};
            const cookies = document.cookie.split(';');
            
            cookies.forEach(cookie => {
                cookie = cookie.trim();
                if (cookie.startsWith(this.cookiePrefix)) {
                    const [name, value] = cookie.split('=');
                    const cleanName = name.replace(this.cookiePrefix, '');
                    data[cleanName] = decodeURIComponent(value || '');
                }
            });
            
            // Merge in local/session storage snapshots when available
            try {
                const firstSnap = this.tryGetLocalStorage(this.cookiePrefix + 'first_snapshot');
                if (firstSnap) {
                    const parsed = JSON.parse(firstSnap);
                    Object.keys(parsed).forEach(key => {
                        const k = 'first_' + key;
                        if (data[k] === undefined) data[k] = String(parsed[key] ?? '');
                    });
                }
            } catch (e) { /* noop */ }

            try {
                const lastSnap = this.tryGetSessionStorage(this.cookiePrefix + 'last_snapshot');
                if (lastSnap) {
                    const parsed = JSON.parse(lastSnap);
                    Object.keys(parsed).forEach(key => {
                        const k = 'last_' + key;
                        if (data[k] === undefined) data[k] = String(parsed[key] ?? '');
                    });
                }
            } catch (e) { /* noop */ }

            return data;
        },

        populateHiddenFields(formElement) {
            if (!formElement) return;
            
            const attributionData = this.getAllAttributionData();
            
            Object.keys(attributionData).forEach(key => {
                // Match by name
                let inputs = Array.from(formElement.querySelectorAll(`input[type="hidden"][name="${key}"]`));
                // Match by data attribute (for Gravity Forms hidden fields with inputName)
                inputs = inputs.concat(Array.from(formElement.querySelectorAll(`input[type="hidden"][data-vt-name="${key}"]`)));
                inputs.forEach(input => {
                    input.value = attributionData[key];
                });
            });
        },

        observeForms() {
            let populateTimer = null;
            const populateAllForms = () => {
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    this.populateHiddenFields(form);
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', populateAllForms);
            } else {
                populateAllForms();
            }

            const observer = new MutationObserver(() => {
                // Debounce to avoid excessive work on busy pages
                if (populateTimer) window.clearTimeout(populateTimer);
                populateTimer = window.setTimeout(populateAllForms, 100);
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        },

        init() {
            this.trackFirstTouch();
            this.trackLastTouch();
            this.observeForms();
        }
    };

    VT_Attribution.init();
    window.VT_Attribution = VT_Attribution;
})();


