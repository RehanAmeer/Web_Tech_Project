document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.nav-toggle');
    var menu = document.getElementById('nav-menu');
    if (toggle && menu) {
        if (window.matchMedia('(max-width: 960px)').matches) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Open sidebar');
            toggle.setAttribute('title', 'Open sidebar');
        }
        toggle.addEventListener('click', function () {
            var sidebar = document.querySelector('.sidebar');
            var mobile = window.matchMedia('(max-width: 960px)').matches;
            if (sidebar && !mobile) {
                var collapsed = document.body.classList.toggle('sidebar-collapsed');
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggle.setAttribute('aria-label', collapsed ? 'Show sidebar' : 'Collapse sidebar');
                toggle.setAttribute('title', collapsed ? 'Show sidebar' : 'Collapse sidebar');
                return;
            }
            var open = menu.classList.toggle('open');
            if (sidebar) {
                sidebar.classList.toggle('open', open);
            }
            document.body.classList.toggle('mobile-sidebar-open', open && mobile);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close sidebar' : 'Open sidebar');
            toggle.setAttribute('title', open ? 'Close sidebar' : 'Open sidebar');
        });
    }

    var fileInput = document.getElementById('file');
    var selectedFile = document.getElementById('selected-file');
    var clearFile = document.getElementById('clear-file');
    if (fileInput && selectedFile) {
        fileInput.addEventListener('change', function () {
            var hasFile = fileInput.files.length > 0;
            selectedFile.textContent = hasFile ? fileInput.files[0].name : 'No file selected';
            selectedFile.classList.toggle('has-file', hasFile);
            if (clearFile) {
                clearFile.hidden = !hasFile;
            }
        });
    }
    if (fileInput && clearFile) {
        clearFile.hidden = true;
        clearFile.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            fileInput.value = '';
            selectedFile.textContent = 'No file selected';
            selectedFile.classList.remove('has-file');
            clearFile.hidden = true;
            fileInput.focus();
        });
    }
});

document.addEventListener('submit', function (event) {
    var form = event.target;
    var message = form.getAttribute('data-confirm');

    if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
    }
    if (form.hasAttribute('data-no-lock')) {
        return;
    }
    window.setTimeout(function () {
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (button) {
            button.disabled = true;
            button.setAttribute('data-locked', '1');
        });
    }, 0);
});

window.addEventListener('pageshow', function () {
    document.querySelectorAll('[data-locked]').forEach(function (button) {
        button.disabled = false;
        button.removeAttribute('data-locked');
    });
});
