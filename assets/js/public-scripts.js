document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('wppcs-cookie-banner');
    const acceptButton = document.getElementById('wppcs-accept-all');
    const openSettingsButton = document.getElementById('wppcs-open-settings');
    const settingsModalOverlay = document.getElementById('wppcs-settings-modal-overlay');
    const closeModalButton = document.getElementById('wppcs-close-modal');
    const saveSettingsButton = document.getElementById('wppcs-save-settings');

    if (!banner) return;

    // --- Helper Functions ---
    const hideBanner = () => {
        banner.classList.add('wppcs-cookie-banner--hidden');
    };

    const setCookie = (name, value, days) => {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        let expires = "expires=" + d.toUTCString();
        document.cookie = name + "=" + JSON.stringify(value) + ";" + expires + ";path=/;SameSite=Lax";
    };

    const logConsentToServer = (consentType, consentDetails = {}) => {
        if (typeof wppcs_ajax === 'undefined') {
            console.error('PDPA Plugin: AJAX object not found.');
            return;
        }
        const formData = new FormData();
        formData.append('action', 'wppcs_log_consent');
        formData.append('nonce', wppcs_ajax.nonce);
        formData.append('consent_type', consentType);
        formData.append('consent_details', JSON.stringify(consentDetails));

        fetch(wppcs_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('PDPA Consent Logged:', data.data.message);
            } else {
                console.error('PDPA Log Error:', data.data.message);
            }
        })
        .catch(error => console.error('PDPA AJAX Error:', error));
    };

    // --- Event Listeners ---
    if (acceptButton) {
        acceptButton.addEventListener('click', function() {
            hideBanner();
            setCookie('wppcs_consent_given', 'all', 365);
            
            // --- THIS IS THE KEY CHANGE ---
            // Instead of sending an empty object, we explicitly state that all categories are accepted.
            const allConsentDetails = {
                analytics: true,
                marketing: true
            };
            logConsentToServer('accept_all', allConsentDetails);
        });
    }

    if (openSettingsButton && settingsModalOverlay) {
        openSettingsButton.addEventListener('click', () => settingsModalOverlay.classList.remove('wppcs-modal-hidden'));
    }

    if (closeModalButton && settingsModalOverlay) {
        closeModalButton.addEventListener('click', () => settingsModalOverlay.classList.add('wppcs-modal-hidden'));
    }

    if (saveSettingsButton && settingsModalOverlay) {
        saveSettingsButton.addEventListener('click', function() {
            const analyticsConsent = document.getElementById('wppcs-consent-analytics').checked;
            const marketingConsent = document.getElementById('wppcs-consent-marketing').checked;

            const consentChoices = {
                analytics: analyticsConsent,
                marketing: marketingConsent
            };

            hideBanner();
            setCookie('wppcs_consent_given', consentChoices, 365);
            logConsentToServer('custom_save', consentChoices);
            settingsModalOverlay.classList.add('wppcs-modal-hidden');
        });
    }
});