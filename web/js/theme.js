function toggleTheme() {
    const body = document.body;
    const html = document.documentElement;
    const themeToggle = document.getElementById('theme-toggle');
    
    if (body.classList.contains('dark-theme')) {
        body.classList.remove('dark-theme');
        html.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
        if (themeToggle) themeToggle.textContent = '🌙';
    } else {
        body.classList.add('dark-theme');
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        if (themeToggle) themeToggle.textContent = '☀️';
    }
}

function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    const body = document.body;
    const html = document.documentElement;
    const themeToggle = document.getElementById('theme-toggle');
    
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        body.classList.add('dark-theme');
        html.setAttribute('data-theme', 'dark');
        if (themeToggle) themeToggle.textContent = '☀️';
    } else {
        body.classList.remove('dark-theme');
        html.setAttribute('data-theme', 'light');
        if (themeToggle) themeToggle.textContent = '🌙';
    }
}

function createThemeToggle() {
    let themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) return;

    const button = document.createElement('button');
    button.id = 'theme-toggle';
    button.className = 'theme-toggle';
    button.textContent = '🌙';
    button.title = 'Переключить тему';
    button.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        transition: all 0.3s;
        z-index: 1000;
    `;

    button.addEventListener('click', toggleTheme);
    button.addEventListener('mouseenter', () => {
        button.style.transform = 'scale(1.1)';
        button.style.boxShadow = '0 6px 20px rgba(0,0,0,0.4)';
    });
    button.addEventListener('mouseleave', () => {
        button.style.transform = 'scale(1)';
        button.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
    });

    document.body.appendChild(button);
    initTheme();
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(createThemeToggle, 100);
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!localStorage.getItem('theme')) {
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        if (e.matches) {
            document.body.classList.add('dark-theme');
            html.setAttribute('data-theme', 'dark');
            if (themeToggle) themeToggle.textContent = '☀️';
        } else {
            document.body.classList.remove('dark-theme');
            html.setAttribute('data-theme', 'light');
            if (themeToggle) themeToggle.textContent = '🌙';
        }
    }
});