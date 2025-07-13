document.addEventListener('DOMContentLoaded', function() {
    // ใช้ jQuery เพื่อให้การจัดการฟอร์มและ AJAX ง่ายขึ้น
    const $ = jQuery; 

    // --- ส่วนที่ 1: โค้ดสำหรับจัดการ Cookie Banner และ Modal ตั้งค่า (ยังคงเหมือนเดิม) ---
    const banner = document.getElementById('wppcs-cookie-banner');
    const acceptButton = document.getElementById('wppcs-accept-all');
    const openSettingsButton = document.getElementById('wppcs-open-settings');
    const settingsModalOverlay = document.getElementById('wppcs-settings-modal-overlay');
    const closeModalButton = document.getElementById('wppcs-close-modal');
    const saveSettingsButton = document.getElementById('wppcs-save-settings');

    const hideBanner = () => {
        if (banner) {
            banner.classList.add('wppcs-cookie-banner--hidden');
        }
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
        // ใช้ jQuery post เพื่อความกระชับ
        $.post(wppcs_ajax.ajax_url, {
            action: 'wppcs_log_consent',
            nonce: wppcs_ajax.nonce,
            consent_type: consentType,
            consent_details: JSON.stringify(consentDetails)
        }).done(function(response) {
            console.log('PDPA Consent Logged:', response);
        }).fail(function(error) {
            console.error('PDPA AJAX Error:', error);
        });
    };

    if (acceptButton) {
        acceptButton.addEventListener('click', function() {
            hideBanner();
            const allConsentDetails = { analytics: true, marketing: true };
            setCookie('wppcs_consent_given', 'all', 365);
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
            const consentChoices = { analytics: analyticsConsent, marketing: marketingConsent };
            hideBanner();
            setCookie('wppcs_consent_given', consentChoices, 365);
            logConsentToServer('custom_save', consentChoices);
            settingsModalOverlay.classList.add('wppcs-modal-hidden');
        });
    }


    // --- ส่วนที่ 2: โค้ดสำหรับจัดการ Popup Error ---
    const errorModalOverlay = document.getElementById('wppcs-error-modal-overlay');
    const closeErrorModalButton = document.getElementById('wppcs-close-error-modal');
    const errorMessageElement = document.getElementById('wppcs-error-message');

    // ฟังก์ชันสำหรับแสดง Popup Error
    const showErrorPopup = (messages) => {
        if (!errorModalOverlay || !errorMessageElement) return;

        // ถ้า messages เป็น Array ให้รวมกันโดยใช้ <br> เพื่อขึ้นบรรทัดใหม่
        const messageText = Array.isArray(messages) ? messages.join('<br>') : messages;
        
        errorMessageElement.innerHTML = messageText;
        errorModalOverlay.classList.remove('wppcs-modal-hidden');
    };

    // THE FIX: เพิ่ม Event Listener สำหรับปุ่มปิด Popup Error
    if (closeErrorModalButton && errorModalOverlay) {
        // ปิด Popup เมื่อกดปุ่ม 'x'
        closeErrorModalButton.addEventListener('click', () => {
            errorModalOverlay.classList.add('wppcs-modal-hidden');
        });
        // ปิด Popup เมื่อคลิกที่พื้นหลังสีเทา
        errorModalOverlay.addEventListener('click', function(event) {
            if (event.target === errorModalOverlay) {
                this.classList.add('wppcs-modal-hidden');
            }
        });
    }

    // --- ส่วนที่ 3: โค้ดสำหรับจัดการฟอร์มสมัครสมาชิกแบบ AJAX ---
    const registerForm = $('form.register');

    if (registerForm.length) {
        // ดักจับเหตุการณ์ submit ของฟอร์ม
        registerForm.on('submit', function(e) {
            e.preventDefault(); // ป้องกันไม่ให้หน้าเว็บโหลดใหม่

            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const originalButtonText = submitButton.text();

            // แสดงสถานะกำลังโหลดที่ปุ่ม
            submitButton.text('Registering...').prop('disabled', true);
            
            // รวบรวมข้อมูลจากฟอร์มเพื่อส่งไปที่เซิร์ฟเวอร์
            const formData = {
                action: 'wppcs_register_user',
                security: wppcs_ajax.register_nonce,
                username: form.find('#reg_username').val(),
                email: form.find('#reg_email').val(),
                password: form.find('#reg_password').val(),
                wppcs_privacy_consent: form.find('#wppcs_privacy_consent').is(':checked') ? 'on' : ''
            };

            // ส่งข้อมูลไปที่เซิร์ฟเวอร์แบบเบื้องหลัง (AJAX)
            $.post(wppcs_ajax.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        // ถ้าสมัครสมาชิกสำเร็จ ให้ Redirect ไปยังหน้าที่กำหนด
                        window.location.href = response.data.redirect_url;
                    } else {
                        // ถ้าเกิดข้อผิดพลาด ให้แสดง Popup Error
                        showErrorPopup(response.data.messages);
                        // คืนค่าปุ่มให้กลับเป็นปกติ
                        submitButton.text(originalButtonText).prop('disabled', false);
                    }
                })
                .fail(function() {
                    // กรณีที่การเชื่อมต่อล้มเหลว
                    showErrorPopup(['An unexpected error occurred. Please try again.']);
                    // คืนค่าปุ่มให้กลับเป็นปกติ
                    submitButton.text(originalButtonText).prop('disabled', false);
                });
        });
    }
});