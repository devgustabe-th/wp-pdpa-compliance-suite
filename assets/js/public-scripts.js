document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('wppcs-cookie-banner');
    const acceptButton = document.getElementById('wppcs-accept-all');
    const openSettingsButton = document.getElementById('wppcs-open-settings');
    const settingsModalOverlay = document.getElementById('wppcs-settings-modal-overlay');
    const closeModalButton = document.getElementById('wppcs-close-modal');
    const saveSettingsButton = document.getElementById('wppcs-save-settings');

    if (!banner) return;

    const hideBanner = () => {
        banner.classList.add('wppcs-cookie-banner--hidden');
    };

    const setCookie = (name, value, days) => {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        let expires = "expires=" + d.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Lax";
    };

    // --- Event Listeners ---

    // Accept All Button
    if (acceptButton) {
        acceptButton.addEventListener('click', function() {
            setCookie('wppcs_consent_given', 'all', 365);
            hideBanner();
        });
    }

    // Open Settings Modal Button
    if (openSettingsButton && settingsModalOverlay) {
        openSettingsButton.addEventListener('click', function() {
            settingsModalOverlay.classList.remove('wppcs-modal-hidden');
        });
    }

    // Close Settings Modal Button
    if (closeModalButton && settingsModalOverlay) {
        closeModalButton.addEventListener('click', function() {
            settingsModalOverlay.classList.add('wppcs-modal-hidden');
        });
    }

    // Save Settings Button
    if (saveSettingsButton && settingsModalOverlay) {
        saveSettingsButton.addEventListener('click', function() {
            const analyticsConsent = document.getElementById('wppcs-consent-analytics').checked;
            const marketingConsent = document.getElementById('wppcs-consent-marketing').checked;

            const consentChoices = {
                analytics: analyticsConsent,
                marketing: marketingConsent
            };
            
            // Save choices as a JSON string in a cookie
            setCookie('wppcs_consent_given', JSON.stringify(consentChoices), 365);
            
            settingsModalOverlay.classList.add('wppcs-modal-hidden');
            hideBanner();
        });
    }
});