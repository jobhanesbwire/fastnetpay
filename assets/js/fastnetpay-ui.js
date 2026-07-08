(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
            return;
        }
        document.addEventListener('DOMContentLoaded', fn);
    }

    function setMode(mode) {
        var body = document.body;
        var root = document.documentElement;
        var wrapper = document.querySelector('.wrapper');
        var toggle = document.getElementById('themeToggle');
        var icon = document.getElementById('toggleIcon');
        var isDark = mode === 'dark';

        root.classList.toggle('dark-mode', isDark);
        body.classList.toggle('dark-mode', isDark);
        if (wrapper) {
            wrapper.classList.toggle('dark-mode', isDark);
        }
        if (toggle) {
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            toggle.classList.toggle('is-dark', isDark);
        }
        if (icon) {
            icon.className = isDark ? 'fa fa-sun-o' : 'fa fa-moon-o';
        }
    }

    ready(function () {
        var savedMode = localStorage.getItem('fastnetpay-theme-mode') || localStorage.getItem('mode');
        setMode(savedMode === 'dark' ? 'dark' : 'light');

        var themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function (event) {
                event.preventDefault();
                var next = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
                localStorage.setItem('mode', next);
                localStorage.setItem('fastnetpay-theme-mode', next);
                setMode(next);
            });
        }

        var overlay = document.getElementById('searchOverlay');
        var openSearch = document.getElementById('openSearch');
        var closeSearch = document.getElementById('closeSearch');
        var searchTerm = document.getElementById('searchTerm');
        var searchResults = document.getElementById('searchResults');

        function openOverlay() {
            if (!overlay) return;
            overlay.classList.add('is-visible');
            overlay.style.display = 'flex';
            document.body.classList.add('fnp-search-open');
            setTimeout(function () {
                if (searchTerm) searchTerm.focus();
            }, 80);
        }

        function closeOverlay() {
            if (!overlay) return;
            overlay.classList.remove('is-visible');
            document.body.classList.remove('fnp-search-open');
            setTimeout(function () {
                if (!overlay.classList.contains('is-visible')) {
                    overlay.style.display = 'none';
                }
            }, 180);
        }

        if (openSearch) {
            openSearch.addEventListener('click', function (event) {
                event.preventDefault();
                openOverlay();
            });
        }
        if (closeSearch) {
            closeSearch.addEventListener('click', function (event) {
                event.preventDefault();
                closeOverlay();
            });
        }
        if (overlay) {
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    closeOverlay();
                }
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeOverlay();
            }
        });

        var searchTimer;
        if (searchTerm && searchResults && window.jQuery) {
            searchTerm.addEventListener('keyup', function () {
                var query = this.value;
                var url = overlay ? overlay.getAttribute('data-search-url') : '';
                window.clearTimeout(searchTimer);

                if (!query || query.length < 2) {
                    searchResults.innerHTML = '';
                    searchResults.style.display = 'none';
                    return;
                }

                searchTimer = window.setTimeout(function () {
                    window.jQuery.ajax({
                        url: url,
                        type: 'GET',
                        data: { query: query },
                        success: function (data) {
                            if (data && data.trim() !== '') {
                                searchResults.innerHTML = data;
                                searchResults.style.display = 'block';
                            } else {
                                searchResults.innerHTML = '<div class="fnp-search-empty">No matching users found.</div>';
                                searchResults.style.display = 'block';
                            }
                        }
                    });
                }, 180);
            });
        }

        if (window.Swal && window.Swal.mixin) {
            window.fnpToast = window.Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4500,
                timerProgressBar: true,
                customClass: {
                    popup: 'fnp-toast'
                },
                didOpen: function (toast) {
                    toast.addEventListener('mouseenter', window.Swal.stopTimer);
                    toast.addEventListener('mouseleave', window.Swal.resumeTimer);
                }
            });
        }
    });
}());
