

let currentQuery = '';

document.querySelectorAll('.search-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.search-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab + '-tab').style.display = 'block';
    });
});

document.getElementById('search-input')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') performSearch();
});

function performSearch() {
    const query = document.getElementById('search-input').value.trim();
    if (query) {
        window.location.href = '/search?q=' + encodeURIComponent(query);
    }
}

function quickSearch(term) {
    const input = document.getElementById('search-input');
    if (input) input.value = term;
    performSearch();
}

let searchTimeout;
const searchInput = document.getElementById('search-input');

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();

        if (query.length === 0) {
            showSuggestions();
            return;
        }

        hideSuggestions();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                liveSearch(query);
            }, 400);
        }
    });

    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length === 0) {
            showSuggestions();
        }
    });
}

function showSuggestions() {
    let container = document.getElementById('search-suggestions');
    if (!container) {
        container = document.createElement('div');
        container.id = 'search-suggestions';
        container.className = 'search-suggestions-live';
        searchInput.parentElement.appendChild(container);
    }
    container.innerHTML = `
        <div class="suggestions-title">Популярные запросы</div>
        <div class="suggestion-tags">
            <button class="suggestion-tag" onclick="quickSearch('admin')">admin</button>
            <button class="suggestion-tag" onclick="quickSearch('привет')">привет</button>
            <button class="suggestion-tag" onclick="quickSearch('тест')">тест</button>
            <button class="suggestion-tag" onclick="quickSearch('новости')">новости</button>
            <button class="suggestion-tag" onclick="quickSearch('фото')">фото</button>
        </div>
    `;
    container.style.display = 'block';
}

function hideSuggestions() {
    const container = document.getElementById('search-suggestions');
    if (container) container.style.display = 'none';
}

async function liveSearch(query) {
    try {
        const response = await fetch('/api/search?q=' + encodeURIComponent(query));
        const data = await response.json();
        renderLiveResults(data, query);
    } catch (error) {
        
    }
}

function renderLiveResults(data, query) {
    let container = document.getElementById('live-search-results');
    if (!container) {
        container = document.createElement('div');
        container.id = 'live-search-results';
        container.className = 'live-search-results';
        searchInput.parentElement.appendChild(container);
    }
    
    const users = data.users || [];
    const posts = data.posts || [];
    const total = users.length + posts.length;
    
    if (total === 0) {
        container.innerHTML = '<div class="live-search-empty">Ничего не найдено</div>';
    } else {
        let html = '';
        
        if (users.length > 0) {
            html += '<div class="live-search-section"><div class="live-search-section-title">Пользователи</div>';
            users.slice(0, 5).forEach(user => {
                const avatar = user.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${user.id}`;
                html += `
                    <a href="/profile/view?id=${user.id}" class="live-search-item">
                        <img src="${avatar}" class="live-search-avatar" alt="">
                        <span class="live-search-name">${escapeHtml(user.username)}</span>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        if (posts.length > 0) {
            html += '<div class="live-search-section"><div class="live-search-section-title">Посты</div>';
            posts.slice(0, 3).forEach(post => {
                html += `
                    <a href="/post/view?id=${post.id}" class="live-search-item live-search-post">
                        <span class="live-search-post-text">${escapeHtml(post.content.substring(0, 60))}${post.content.length > 60 ? '...' : ''}</span>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        html += `<a href="/search?q=${encodeURIComponent(query)}" class="live-search-all">Показать все результаты →</a>`;
        container.innerHTML = html;
    }
    
    container.style.display = 'block';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-box-large')) {
        hideSuggestions();
        const liveResults = document.getElementById('live-search-results');
        if (liveResults) liveResults.style.display = 'none';
    }
});
