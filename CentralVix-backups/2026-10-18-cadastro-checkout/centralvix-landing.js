(function () {
    'use strict';

    function qs(sel, ctx) {
        return (ctx || document).querySelector(sel);
    }

    function qsa(sel, ctx) {
        return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
    }

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        if (document.querySelector('.cv-landing')) {
            document.body.classList.add('cv-landing-page');
        }
        if (!document.body.classList.contains('cv-landing-page')) {
            return;
        }

        /* Sticky header */
        var header = qs('#cv-landing-header');
        if (header) {
            var onScroll = function () {
                header.classList.toggle('is-scrolled', window.scrollY > 8);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        /* Reveal on scroll */
        var revealEls = qsa('.cv-landing [data-cv-reveal]');
        if (revealEls.length) {
            if ('IntersectionObserver' in window) {
                revealEls.forEach(function (el, i) {
                    el.style.setProperty('--cv-reveal-delay', ((i % 4) * 70) + 'ms');
                });
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
                revealEls.forEach(function (el) {
                    observer.observe(el);
                });
            } else {
                revealEls.forEach(function (el) {
                    el.classList.add('is-in');
                });
            }
        }

        /* Menu mobile */
        var burger = qs('#cv-landing-burger');
        var panel = qs('#cv-landing-mobile-panel');
        if (burger && panel) {
            var closePanel = function () {
                panel.classList.remove('is-open');
                burger.setAttribute('aria-expanded', 'false');
            };
            burger.addEventListener('click', function () {
                var open = panel.classList.toggle('is-open');
                burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            panel.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closePanel);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closePanel();
                }
            });
            document.addEventListener('click', function (e) {
                if (panel.classList.contains('is-open')
                    && !panel.contains(e.target)
                    && !burger.contains(e.target)) {
                    closePanel();
                }
            });
        }

        /* Mostrar/ocultar senha */
        var toggle = qs('#cv-landing-toggle-password');
        var password = qs('#inputPassword');
        if (toggle && password) {
            toggle.addEventListener('click', function () {
                var hidden = password.getAttribute('type') === 'password';
                password.setAttribute('type', hidden ? 'text' : 'password');
                toggle.setAttribute('aria-label', hidden ? 'Ocultar senha' : 'Mostrar senha');
                var icon = toggle.querySelector('i');
                if (icon) {
                    icon.className = hidden ? 'fas fa-eye-slash' : 'fas fa-eye';
                }
            });
        }

        /* Recarregar captcha ao clicar na imagem */
        qsa('.cv-landing-captcha-img').forEach(function (img) {
            img.addEventListener('click', function () {
                var src = img.getAttribute('src').split('?')[0];
                img.setAttribute('src', src + '?r=' + Date.now());
            });
        });

        /* Estado de carregamento no botão de login */
        var form = qs('#cv-landing-login-form');
        if (form) {
            form.addEventListener('submit', function () {
                var btn = qs('#cv-landing-submit');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Entrando...';
                }
            });
        }

        /* Validação de senha do cadastro */
        var register = qs('#cv-register-form');
        if (register) {
            register.addEventListener('submit', function (evt) {
                var pass = qs('#inputNewPassword1');
                var confirm = qs('#inputNewPassword2');
                if (pass && confirm && pass.value !== confirm.value) {
                    evt.preventDefault();
                    window.alert('As senhas não conferem.');
                }
            });
        }

        /* Filtro de localização (Brasil/Canadá) */
        qsa('[data-loc-root]').forEach(function (root) {
            var buttons = qsa('[data-loc]', root);
            var cards = qsa('.cv-plan-card[data-location]', root.parentNode);
            var groups = qsa('.cv-catalog-group', root.parentNode);
            function apply(loc) {
                cards.forEach(function (card) {
                    var match = (card.getAttribute('data-location') || '').indexOf(loc) !== -1
                        || (loc === 'brasil' && (card.getAttribute('data-location') || '').indexOf('vps-brasil') !== -1)
                        || (loc === 'canada' && (card.getAttribute('data-location') || '').indexOf('vps-canada') !== -1);
                    card.style.display = match ? '' : 'none';
                });
                groups.forEach(function (g) {
                    var slug = g.getAttribute('data-group-location') || '';
                    g.style.display = (loc === 'brasil' && slug.indexOf('brasil') !== -1) || (loc === 'canada' && slug.indexOf('canada') !== -1) ? '' : 'none';
                });
            }
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(function (b) { b.classList.toggle('is-on', b === btn); });
                    apply(btn.getAttribute('data-loc'));
                });
            });
            apply('brasil');
        });

        /* Alternância de ciclo de preço (mensal/anual) */
        qsa('[data-cycle-root]').forEach(function (root) {
            var buttons = qsa('[data-cycle]', root);
            var cards = qsa('.cv-plan-card[data-monthly], .cv-plan-card[data-annually]', root.parentNode);
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var cycle = btn.getAttribute('data-cycle');
                    buttons.forEach(function (b) { b.classList.toggle('is-on', b === btn); });
                    cards.forEach(function (card) {
                        var price = card.getAttribute('data-' + cycle);
                        var node = card.querySelector('[data-price]');
                        var period = card.querySelector('[data-period]');
                        if (price && node) {
                            node.textContent = price;
                        }
                        if (period) {
                            period.textContent = cycle === 'annually' ? '/ano' : '/mês';
                        }
                    });
                });
            });
        });
    });
})();
