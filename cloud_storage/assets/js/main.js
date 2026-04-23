// assets/js/main.js — CloudVault

// Auto-dismiss alerts after 4s
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 3800);
    setTimeout(() => el.remove(), 4200);
});

// Confirm before delete actions
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
});

// File type → emoji icon
function fileIcon(name = '') {
    const ext = name.split('.').pop().toLowerCase();
    const map = {
        pdf:'📄', doc:'📝', docx:'📝', xls:'📊', xlsx:'📊',
        ppt:'📋', pptx:'📋', zip:'🗜️', rar:'🗜️',
        jpg:'🖼️', jpeg:'🖼️', png:'🖼️', gif:'🖼️', svg:'🎨',
        mp4:'🎬', mov:'🎬', avi:'🎬',
        mp3:'🎵', wav:'🎵',
        txt:'📃', csv:'📊', json:'🔧', js:'🔧', php:'🐘',
        html:'🌐', css:'🎨', py:'🐍',
    };
    return map[ext] || '📁';
}

document.querySelectorAll('.file-icon[data-name]').forEach(el => {
    el.textContent = fileIcon(el.dataset.name);
});
