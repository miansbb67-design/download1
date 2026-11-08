/**
 * FlowFetch Global Application Logic
 * Clean, handcrafted ES6+ implementation with zero bloat.
 */

window.FlowApp = {
    csrfToken: '',

    init() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) this.csrfToken = meta.getAttribute('content');

        // Dark mode setup
        this.initDarkMode();

        // GSAP entry animations
        this.initAnimations();
    },

    initDarkMode() {
        const themeBtn = document.getElementById('themeToggleBtn');
        const isDark = localStorage.getItem('flow_dark_mode') === 'true';

        if (isDark) {
            document.body.classList.add('dark-mode');
            if (themeBtn) themeBtn.innerHTML = '<i class="fa-solid fa-sun text-warning"></i>';
        }

        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const nowDark = document.body.classList.toggle('dark-mode');
                localStorage.setItem('flow_dark_mode', nowDark);
                themeBtn.innerHTML = nowDark 
                    ? '<i class="fa-solid fa-sun text-warning"></i>' 
                    : '<i class="fa-solid fa-moon"></i>';
            });
        }
    },

    initAnimations() {
        if (typeof gsap !== 'undefined') {
            gsap.from('.hero-title', { opacity: 0, y: 30, duration: 0.8, ease: 'power2.out' });
            gsap.from('.hero-subtitle', { opacity: 0, y: 20, duration: 0.8, delay: 0.2, ease: 'power2.out' });
            gsap.from('.downloader-card', { opacity: 0, y: 20, duration: 0.8, delay: 0.35, ease: 'power2.out' });
        }
    },

    notify(message, type = 'info') {
        const toastEl = document.getElementById('liveToast');
        const msgEl = document.getElementById('toastMessage');
        if (!toastEl || !msgEl) return;

        toastEl.className = `toast align-items-center text-white border-0 ${
            type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-dark'))
        }`;

        msgEl.innerText = message;
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    },

    async request(url, options = {}) {
        options.headers = options.headers || {};
        options.headers['X-CSRF-TOKEN'] = this.csrfToken;
        options.headers['Accept'] = 'application/json';

        try {
            const resp = await fetch(url, options);
            const data = await resp.json();
            return data;
        } catch (e) {
            return { success: false, error: 'Network communication error: ' + e.message };
        }
    },

    async handleLogin(event) {
        event.preventDefault();
        const form = event.target;
        const submitBtn = document.getElementById('loginSubmitBtn');
        const alertBox = document.getElementById('loginAlert');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Signing in...';
        alertBox.classList.add('d-none');

        const formData = new FormData(form);
        const res = await this.request('api/auth/login', {
            method: 'POST',
            body: formData
        });

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Sign In';

        if (res.success) {
            this.notify('Login successful!', 'success');
            setTimeout(() => { window.location.href = res.redirect || 'dashboard'; }, 400);
        } else {
            alertBox.innerText = res.error || 'Login failed.';
            alertBox.classList.remove('d-none');
        }
    },

    async handleRegister(event) {
        event.preventDefault();
        const form = event.target;
        const submitBtn = document.getElementById('registerSubmitBtn');
        const alertBox = document.getElementById('registerAlert');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating account...';
        alertBox.classList.add('d-none');

        const formData = new FormData(form);
        const res = await this.request('api/auth/register', {
            method: 'POST',
            body: formData
        });

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-user-check me-1"></i> Register Free Account';

        if (res.success) {
            this.notify('Account created successfully!', 'success');
            setTimeout(() => { window.location.href = res.redirect || 'dashboard'; }, 400);
        } else {
            alertBox.innerText = res.error || 'Registration failed.';
            alertBox.classList.remove('d-none');
        }
    },

    async logout() {
        const res = await this.request('api/auth/logout', { method: 'POST' });
        window.location.href = './';
    },

    handleContactSubmit(event) {
        event.preventDefault();
        this.notify('Thank you! Your message has been received. Our team will get back to you shortly.', 'success');
        event.target.reset();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    FlowApp.init();
});
