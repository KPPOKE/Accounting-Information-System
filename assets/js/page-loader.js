(function () {
    'use strict';

    const pageLoader = document.getElementById('pageLoader');

    window.addEventListener('load', function () {
        setTimeout(function () {
            if (pageLoader) {
                pageLoader.classList.add('hidden');
            }
        }, 500);
    });

    document.addEventListener('click', function (e) {
        const target = e.target.closest('a');

        if (target && target.href) {
            const href = target.getAttribute('href');
            const currentHost = window.location.hostname;

            if (
                href &&
                !href.startsWith('#') &&
                !href.startsWith('javascript:') &&
                !target.hasAttribute('download') &&
                target.getAttribute('target') !== '_blank' &&
                !target.hasAttribute('data-no-loader') &&
                (href.startsWith('/') || href.includes(currentHost))
            ) {
                if (pageLoader) {
                    pageLoader.classList.remove('hidden');
                }
            }
        }
    });

    document.addEventListener('submit', function (e) {
        const form = e.target;

        if (!form.hasAttribute('data-no-loader')) {
            if (pageLoader) {
                pageLoader.classList.remove('hidden');
            }
        }
    });

    window.addEventListener('pageshow', function (event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            if (pageLoader) {
                pageLoader.classList.add('hidden');
            }
        }
    });

    setTimeout(function () {
        if (pageLoader && !pageLoader.classList.contains('hidden')) {
            pageLoader.classList.add('hidden');
        }
    }, 10000);

})();
