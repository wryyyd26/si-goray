(() => {
    'use strict';

    const root = document.getElementById('visitor-login');
    if (!root) return;

    const form = document.getElementById('visitor-login-form');
    const email = document.getElementById('visitor-email');
    const otpSection = document.getElementById('visitor-otp-section');
    const otp = document.getElementById('visitor-otp');
    const submit = document.getElementById('visitor-submit');
    const resend = document.getElementById('visitor-resend');
    const message = document.getElementById('visitor-message');
    const csrf = form.querySelector('input[name="_token"]');
    let step = 'email';
    let busy = false;

    const showMessage = (text, error = false) => {
        message.textContent = text;
        message.style.color = error ? '#a32323' : 'inherit';
    };

    async function post(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf.value,
            },
            body: JSON.stringify(payload),
        });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 419) throw new Error('Sesi halaman berakhir. Muat ulang halaman dan coba lagi.');
            if (response.status === 429) throw new Error('Terlalu banyak percobaan. Coba lagi nanti.');
            const validationMessage = Object.values(body.errors || {}).flat()[0];
            throw new Error(validationMessage || body.message || 'Permintaan gagal. Coba lagi.');
        }
        return body;
    }

    async function requestOtp() {
        if (!email.checkValidity()) {
            email.reportValidity();
            return;
        }
        busy = true;
        submit.disabled = true;
        resend.disabled = true;
        showMessage('Meminta kode OTP...');
        try {
            const result = await post(root.dataset.requestUrl, { no_hp_email: email.value.trim() });
            step = 'otp';
            otpSection.hidden = false;
            otp.required = true;
            email.readOnly = true;
            submit.textContent = 'Verifikasi dan Masuk';
            showMessage(result.message || 'Jika alamat email valid, kode OTP akan dikirim.');
            otp.focus();
        } catch (error) {
            showMessage(error.message || 'Tidak dapat menghubungi server.', true);
        } finally {
            busy = false;
            submit.disabled = false;
            resend.disabled = false;
        }
    }

    async function verifyOtp() {
        if (!otp.checkValidity() || !/^[0-9]{6}$/.test(otp.value)) {
            otp.reportValidity();
            return;
        }
        busy = true;
        submit.disabled = true;
        resend.disabled = true;
        showMessage('Memverifikasi OTP...');
        try {
            await post(root.dataset.verifyUrl, {
                no_hp_email: email.value.trim(),
                kode_otp: otp.value,
            });
            otp.value = '';
            showMessage('Login berhasil. Mengalihkan ke halaman sebelumnya...');
            window.location.assign(root.dataset.next || '/events');
        } catch (error) {
            showMessage(error.message || 'Verifikasi gagal.', true);
        } finally {
            busy = false;
            submit.disabled = false;
            resend.disabled = false;
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;
        if (step === 'email') await requestOtp();
        else await verifyOtp();
    });

    resend.addEventListener('click', async () => {
        if (!busy) await requestOtp();
    });
})();
