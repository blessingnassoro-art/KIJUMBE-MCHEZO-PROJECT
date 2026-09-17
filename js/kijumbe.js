const phrases = [
    "Karibu Kijumbe Management System [KMS]",
    "Huduma Bora za kuhifadhi fedha kidigitali",
    "Ufuatiliaji wa Haraka na Salama",
    "Jisikie uko nyumbani",
    "KMS - Suluhisho la Usimamizi wa fedha kidigitali"

];

let phraseIndex = 0;
let charIndex = 0;
let isDeleting = false;

const sloganEl = document.getElementById('slogan');

function typeEffect() {
    const currentPhrase = phrases[phraseIndex];
    let typeSpeed = isDeleting ? 45 : 90;

    if (isDeleting) {
        charIndex--;
    } else {
        charIndex++;
    }

    sloganEl.textContent = currentPhrase.substring(0, charIndex);

    if (!isDeleting && charIndex === currentPhrase.length) {

        typeSpeed = 1600;
        isDeleting = true;
    } else if (isDeleting && charIndex === 0) {
        isDeleting = false;
        phraseIndex = (phraseIndex + 1) % phrases.length;
        typeSpeed = 400;
    }

    setTimeout(typeEffect, typeSpeed);
}

if (sloganEl) {
    typeEffect();
}

const themeToggle = document.querySelector('.theme-toggle');
const savedTheme = localStorage.getItem('kms-theme');

function setTheme(isDark) {
    if (!themeToggle) return;

    document.body.classList.toggle('dark-mode', isDark);
    themeToggle.setAttribute('aria-pressed', String(isDark));
    themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    themeToggle.querySelector('span').textContent = isDark ? 'Light mode' : 'Dark mode';
    themeToggle.querySelector('i').className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
}

if (themeToggle) {
    setTheme(savedTheme === 'dark');
}

if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const isDark = !document.body.classList.contains('dark-mode');
        setTheme(isDark);
        localStorage.setItem('kms-theme', isDark ? 'dark' : 'light');
    });
}

const languageSelect = document.querySelector('#language');
const translations = {
    en: {
        home: 'Home', about: 'About', features: 'Features', contact: 'Contact', register: 'Register',
        welcome: 'Welcome to Kijumbe Management System', loginPrompt: 'Please login to continue.',
        showPassword: 'Show Password', login: 'Login', aboutTitle: 'About Kijumbe Management System',
        aboutText: 'Kijumbe Management System is a comprehensive solution for managing digital financial transactions with ease and security.',
        aboutText2: 'Our platform offers a user-friendly interface, real-time tracking, and robust security features to ensure your digital finances are well-managed.',
        contactTitle: 'Contact Us', contactText: 'If you have any questions or need assistance, please reach out to us.',
        namePlaceholder: 'Your Name', emailPlaceholder: 'Your Email', messagePlaceholder: 'Your Message', sendMessage: 'Send Message',
        manageTransactions: 'Manage Transactions', manageTransactionsText: 'Effortlessly manage your digital financial transactions with our intuitive interface.',
        realTimeTracking: 'Real-time Tracking', realTimeTrackingText: 'Monitor your financial activities in real-time with our advanced tracking capabilities.',
        securePlatform: 'Secure Platform', securePlatformText: 'Rest assured that your digital finances are protected with our robust security measures.',
        userFriendly: 'User-friendly Interface', userFriendlyText: 'Navigate through our platform with ease, thanks to our user-friendly interface.',
        paymentProcessing: 'Payment Processing', paymentProcessingText: 'Effortlessly process payments with our streamlined payment gateway.',
        analyticsReporting: 'Analytics & Reporting', analyticsReportingText: 'Gain insights into your financial activities with our comprehensive analytics and reporting tools.',
        manageMeetings: 'Manage Meetings', manageMeetingsText: 'Effortlessly schedule and manage your meetings with our integrated calendar and scheduling tools.',
        fines: 'Fines', finesText: 'Manage and track fines associated with your financial activities.',
        settings: 'Settings', settingsText: 'Customize your experience and manage your account preferences.'
    },
    sw: {
        home: 'Nyumbani', about: 'Kuhusu', features: 'Huduma', contact: 'Mawasiliano', register: 'Jisajili',
        welcome: 'Karibu kwenye Mfumo wa Usimamizi wa Kijumbe', loginPrompt: 'Tafadhali ingia ili kuendelea.',
        showPassword: 'Onyesha Nenosiri', login: 'Ingia', aboutTitle: 'Kuhusu Mfumo wa Usimamizi wa Kijumbe',
        aboutText: 'Mfumo wa Usimamizi wa Kijumbe ni suluhisho kamili la kusimamia miamala ya kifedha kidigitali kwa urahisi na usalama.',
        aboutText2: 'Mfumo wetu una muonekano rahisi, ufuatiliaji wa wakati halisi na usalama imara wa kulinda fedha zako kidigitali.',
        contactTitle: 'Wasiliana Nasi', contactText: 'Ikiwa una swali au unahitaji msaada, tafadhali wasiliana nasi.',
        namePlaceholder: 'Jina Lako', emailPlaceholder: 'Barua Pepe Yako', messagePlaceholder: 'Ujumbe Wako', sendMessage: 'Tuma Ujumbe',
        manageTransactions: 'Simamia Miamala', manageTransactionsText: 'Simamia miamala yako ya kifedha kidigitali kwa urahisi kupitia mfumo wetu.',
        realTimeTracking: 'Ufuatiliaji wa Wakati Halisi', realTimeTrackingText: 'Fuatilia shughuli zako za kifedha kwa wakati halisi kupitia mfumo wetu.',
        securePlatform: 'Mfumo Salama', securePlatformText: 'Fedha zako kidigitali zinalindwa kwa kutumia hatua imara za usalama.',
        userFriendly: 'Muonekano Rahisi wa Kutumia', userFriendlyText: 'Tumia mfumo wetu kwa urahisi kutokana na muonekano wake rahisi.',
        paymentProcessing: 'Uchakataji wa Malipo', paymentProcessingText: 'Shughulikia malipo kwa urahisi kupitia mfumo wetu wa malipo.',
        analyticsReporting: 'Uchambuzi na Ripoti', analyticsReportingText: 'Pata maarifa kuhusu shughuli zako za kifedha kupitia uchambuzi na ripoti.',
        manageMeetings: 'Simamia Mikutano', manageMeetingsText: 'Panga na simamia mikutano yako kwa kutumia kalenda yetu jumuishi.',
        fines: 'Faini', finesText: 'Simamia na fuatilia faini zinazohusiana na shughuli zako za kifedha.',
        settings: 'Mipangilio', settingsText: 'Badilisha matumizi yako na simamia mipangilio ya akaunti yako.'
    }
};

function applyLanguage(language) {
    const selectedTranslations = translations[language] || translations.en;
    document.documentElement.lang = language;
    document.querySelectorAll('[data-i18n]').forEach((element) => {
        const key = element.dataset.i18n;
        if (selectedTranslations[key]) {
            element.textContent = selectedTranslations[key];
        }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
        const key = element.dataset.i18nPlaceholder;
        if (selectedTranslations[key]) {
            element.placeholder = selectedTranslations[key];
        }
    });
}

if (languageSelect) {
    const savedLanguage = localStorage.getItem('kms-language') || 'en';
    languageSelect.value = savedLanguage;
    applyLanguage(savedLanguage);
    languageSelect.addEventListener('change', () => {
        localStorage.setItem('kms-language', languageSelect.value);
        applyLanguage(languageSelect.value);
    });
}

document.querySelectorAll('.password-toggle').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const passwordInput = toggle.closest('.input-group').querySelector('input');
        const isVisible = passwordInput.type === 'text';

        passwordInput.type = isVisible ? 'password' : 'text';
        toggle.setAttribute('aria-pressed', String(!isVisible));
        toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        toggle.querySelector('i').className = isVisible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    });
});

const showPassword = document.querySelector('#show_password');
const passwordInput = document.querySelector('input[name="password"]');

if (showPassword && passwordInput) {
    showPassword.addEventListener('change', () => {
        passwordInput.type = showPassword.checked ? 'text' : 'password';
    });
}

window.addEventListener("scroll", function () {
    let reveals = document.querySelectorAll(".reveal");

    reveals.forEach(function (el) {
        let windowHeight = window.innerHeight;
        let elementTop = el.getBoundingClientRect().top;
        let visiblePoint = 100;

        if (elementTop < windowHeight - visiblePoint) {
            el.classList.add("active");
        } else {
            el.classList.remove("active");
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contact-form');
    if (!form) return;

    const adminNumber = (form.dataset.adminNumber || '255719016625').replace(/\D/g, '');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const nameInput = form.querySelector('input[name="name"]');
        const emailInput = form.querySelector('input[name="email"]');
        const messageInput = form.querySelector('textarea[name="message"]');

        if (!nameInput || !emailInput || !messageInput) return;

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const message = messageInput.value.trim();

        if (!name || !email || !message) return;

        const whatsappMessage = `Hello Blessing, my name is ${name}. Email: ${email}. Message: ${message}`;
        const whatsappUrl = `https://wa.me/${adminNumber}?text=${encodeURIComponent(whatsappMessage)}`;

        window.open(whatsappUrl, '_blank');
        form.reset();
    });
});
