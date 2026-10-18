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

        /* Gerador de senha segura (cadastro) */
        var passGen = qs('#cv-pass-gen');
        if (passGen) {
            passGen.addEventListener('click', function () {
                var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#%*';
                var value = '';
                var rand = new Uint32Array(14);
                window.crypto.getRandomValues(rand);
                for (var i = 0; i < 14; i++) {
                    value += chars.charAt(rand[i] % chars.length);
                }
                var p1 = qs('#inputNewPassword1');
                var p2 = qs('#inputNewPassword2');
                if (p1 && p2) {
                    p1.value = value;
                    p2.value = value;
                    p1.setAttribute('type', 'text');
                    p2.setAttribute('type', 'text');
                    p2.classList.add('cv-field-ok');
                }
            });
        }

        /* Preenchimento automático: CEP (ViaCEP) e CNPJ (BrasilAPI/Receita) */
        (function () {
            var onlyDigits = function (v) { return (v || '').replace(/\D/g, ''); };

            var maskCep = function (v) {
                v = onlyDigits(v).slice(0, 8);
                return v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5) : v;
            };

            var maskDoc = function (v) {
                v = onlyDigits(v).slice(0, 14);
                if (v.length <= 11) {
                    v = v.replace(/(\d{3})(\d)/, '$1.$2');
                    v = v.replace(/(\d{3})(\d)/, '$1.$2');
                    return v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                }
                v = v.replace(/(\d{2})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1/$2');
                return v.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            };

            var validCpf = function (s) {
                if (s.length !== 11 || /^(\d)\1{10}$/.test(s)) { return false; }
                var t, i, sum, rem;
                for (t = 9; t < 11; t++) {
                    sum = 0;
                    for (i = 0; i < t; i++) { sum += parseInt(s.charAt(i), 10) * (t + 1 - i); }
                    rem = (sum * 10) % 11;
                    if (rem === 10 || rem === 11) { rem = 0; }
                    if (rem !== parseInt(s.charAt(t), 10)) { return false; }
                }
                return true;
            };

            var validCnpj = function (s) {
                if (s.length !== 14 || /^(\d)\1{13}$/.test(s)) { return false; }
                var calc = function (base, weights) {
                    var sum = 0, i;
                    for (i = 0; i < weights.length; i++) { sum += parseInt(base.charAt(i), 10) * weights[i]; }
                    var rem = sum % 11;
                    return rem < 2 ? 0 : 11 - rem;
                };
                if (calc(s, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) !== parseInt(s.charAt(12), 10)) { return false; }
                if (calc(s, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) !== parseInt(s.charAt(13), 10)) { return false; }
                return true;
            };

            var setHint = function (el, message, kind) {
                if (!el) { return; }
                el.textContent = message || '';
                el.classList.remove('is-ok', 'is-bad');
                if (kind) { el.classList.add(kind === 'ok' ? 'is-ok' : 'is-bad'); }
            };

            var markField = function (el, ok) {
                if (!el) { return; }
                el.classList.remove('cv-field-ok', 'cv-field-bad');
                el.classList.add(ok ? 'cv-field-ok' : 'cv-field-bad');
            };

            var fillIfEmpty = function (sel, value) {
                var el = typeof sel === 'string' ? qs(sel) : sel;
                if (el && value && el.value.trim() === '') {
                    el.value = value;
                    markField(el, true);
                    return true;
                }
                return false;
            };

            var setLoading = function (el, on) {
                if (el) { el.classList.toggle('cv-field-loading', !!on); }
            };

            var applyAddress = function (data) {
                var street = [data.logradouro, data.numero].filter(Boolean).join(', ');
                var line1 = street;
                if (data.bairro) { line1 = line1 ? line1 + ' - ' + data.bairro : data.bairro; }
                fillIfEmpty('#inputAddress1', line1);
                fillIfEmpty('#inputAddress2', data.complemento || '');
                fillIfEmpty('#inputCity', data.cidade || data.localidade || '');
                var uf = (data.uf || '').toUpperCase();
                var stateSel = qs('#state');
                if (stateSel && uf && stateSel.value === '') {
                    var opt = stateSel.querySelector('option[value="' + uf + '"]');
                    if (opt) { stateSel.value = uf; markField(stateSel, true); }
                }
                if (data.cep) { fillIfEmpty('#inputPostcode', maskCep(data.cep)); }
            };

            /* CEP -> ViaCEP */
            var cep = qs('#inputPostcode');
            if (cep) {
                cep.addEventListener('input', function () { cep.value = maskCep(cep.value); });
                cep.addEventListener('blur', function () {
                    var digits = onlyDigits(cep.value);
                    var hint = qs('#cv-cep-hint');
                    cep.classList.remove('cv-field-ok', 'cv-field-bad');
                    if (digits.length !== 8) { return; }
                    setLoading(cep, true);
                    fetch('https://viacep.com.br/ws/' + digits + '/json/')
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            setLoading(cep, false);
                            if (data && data.erro) {
                                markField(cep, false);
                                setHint(hint, 'CEP não encontrado. Confira os números digitados.', 'bad');
                                return;
                            }
                            applyAddress({
                                logradouro: data.logradouro,
                                bairro: data.bairro,
                                complemento: data.complemento,
                                cidade: data.localidade,
                                uf: data.uf,
                            });
                            markField(cep, true);
                            setHint(hint, 'Endereço preenchido automaticamente pelo CEP.', 'ok');
                        })
                        .catch(function () {
                            setLoading(cep, false);
                            setHint(hint, 'Não foi possível consultar o CEP agora. Preencha manualmente.', 'bad');
                        });
                });
            }

            /* CPF/CNPJ -> validação local + CNPJ na Receita (BrasilAPI) */
            qsa('[data-cv-doc]').forEach(function (doc) {
                doc.addEventListener('input', function () { doc.value = maskDoc(doc.value); });
                doc.addEventListener('blur', function () {
                    var d = onlyDigits(doc.value);
                    var hint = qs('#' + doc.id + '-hint') || qs('#cv-doc-hint');
                    doc.classList.remove('cv-field-ok', 'cv-field-bad');
                    if (d.length === 0) { setHint(hint, ''); return; }
                    if (d.length === 11) {
                        if (validCpf(d)) {
                            markField(doc, true);
                            setHint(hint, 'CPF válido.', 'ok');
                        } else {
                            markField(doc, false);
                            setHint(hint, 'CPF inválido. Confira os dígitos.', 'bad');
                        }
                        return;
                    }
                    if (d.length !== 14) {
                        markField(doc, false);
                        setHint(hint, 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos).', 'bad');
                        return;
                    }
                    if (!validCnpj(d)) {
                        markField(doc, false);
                        setHint(hint, 'CNPJ inválido. Confira os dígitos.', 'bad');
                        return;
                    }
                    setLoading(doc, true);
                    setHint(hint, 'Consultando os dados na Receita Federal...', '');
                    fetch('https://brasilapi.com.br/api/cnpj/v1/' + d)
                        .then(function (r) {
                            if (!r.ok) { throw new Error('cnpj'); }
                            return r.json();
                        })
                        .then(function (data) {
                            setLoading(doc, false);
                            markField(doc, true);
                            fillIfEmpty('#inputCompanyName', data.razao_social || '');
                            applyAddress({
                                logradouro: data.logradouro,
                                numero: data.numero,
                                bairro: data.bairro,
                                complemento: data.complemento,
                                cidade: data.municipio,
                                uf: data.uf,
                                cep: data.cep,
                            });
                            setHint(hint, 'CNPJ localizado na Receita Federal — razão social e endereço preenchidos.', 'ok');
                        })
                        .catch(function () {
                            setLoading(doc, false);
                            markField(doc, true);
                            setHint(hint, 'CNPJ válido. Não foi possível consultar os dados agora — preencha manualmente.', 'ok');
                        });
                });
            });
        })();

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

        /* Lista de recursos: expandir/recolher quando há muitos itens */
        qsa('.cv-catalog-grid .cv-plan-feats').forEach(function (list) {
            var items = qsa('li', list);
            if (items.length <= 6) {
                return;
            }
            var expanded = false;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cv-feat-toggle';
            btn.setAttribute('aria-expanded', 'false');
            var update = function () {
                btn.textContent = expanded
                    ? 'Ver menos recursos'
                    : 'Ver todos os ' + items.length + ' recursos';
                btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                list.classList.toggle('is-expanded', expanded);
            };
            btn.addEventListener('click', function () {
                expanded = !expanded;
                update();
            });
            update();
            list.parentNode.insertBefore(btn, list.nextSibling);
        });
    });
})();
