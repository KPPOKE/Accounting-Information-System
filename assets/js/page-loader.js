(function () {
    'use strict';

    const pageLoader = document.getElementById('pageLoader');

    function hideLoader() {
        if (pageLoader) {
            pageLoader.classList.add('hidden');
        }
    }

    function showLoader() {
        if (pageLoader) {
            pageLoader.classList.remove('hidden');
        }
    }

    if (document.readyState === 'complete') {
        hideLoader();
    } else if (document.readyState === 'interactive') {
        setTimeout(hideLoader, 100);
    }

    document.addEventListener('DOMContentLoaded', function () {
        hideLoader();
    });

    window.addEventListener('load', function () {
        hideLoader();
    });

    window.addEventListener('pageshow', function () {
        hideLoader();
    });

    document.addEventListener('readystatechange', function () {
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            hideLoader();
        }
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
                showLoader();
            }
        }
    });

    document.addEventListener('submit', function (e) {
        const form = e.target;

        if (!form.hasAttribute('data-no-loader')) {
            showLoader();
        }
    });

    setTimeout(hideLoader, 500);
    setTimeout(hideLoader, 1000);
    setTimeout(hideLoader, 3000);

})();
