(() => {
  const ajax = 'addonmodules.php?module=cvxcatalog&cvx=ajax';
  function tokenValue() {
    return document.getElementById('cvxToken')?.value
      || document.querySelector('input[name="token"]')?.value
      || '';
  }

  async function post(action, data = {}) {
    const body = new URLSearchParams({ action, token: tokenValue(), ...data });
    const res = await fetch(ajax, { method: 'POST', body, credentials: 'same-origin' });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      const snippet = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180);
      throw new Error(snippet || 'O WHMCS não devolveu JSON. Recarregue a página e tente pelo botão do formulário.');
    }
  }

  function money(v) {
    const n = Number(v);
    if (!n || n <= 0) return '—';
    return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function cardHtml(name, monthly, annually, badge, features) {
    const feats = (features || []).slice(0, 8).map((f) => {
      const label = typeof f === 'string' ? f : ((f.value ? f.value + ' ' : '') + (f.name || ''));
      return `<li>✓ ${label}</li>`;
    }).join('');
    return `<article class="site-card ${badge ? 'feat' : ''}">
      ${badge ? `<span class="badge ok">${badge}</span>` : ''}
      <strong>${name}</strong>
      <small>a partir de</small>
      <b>${money(monthly)}</b>
      <small>/mês${Number(annually) > 0 ? ' · anual ' + money(annually) : ''}</small>
      <ul>${feats}</ul>
      <em>Contratar agora</em>
    </article>`;
  }

  document.querySelectorAll('[data-dup]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const json = await post('duplicate', { id: btn.getAttribute('data-dup') });
      alert(json.ok ? 'Produto duplicado: ' + json.product.name : json.message);
      if (json.ok) location.reload();
    });
  });
  document.querySelectorAll('[data-hide]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const json = await post('hide', { id: btn.getAttribute('data-hide'), mode: btn.getAttribute('data-mode') || 'hide' });
      if (json.ok) location.reload();
      else alert(json.message);
    });
  });
  document.querySelectorAll('[data-dup-group]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const name = prompt('Nome do novo grupo', (btn.dataset.name || 'Grupo') + ' Premium');
      if (!name) return;
      const json = await post('duplicate_group', { id: btn.getAttribute('data-dup-group'), name });
      alert(json.ok ? 'Grupo duplicado com ' + (json.products || []).length + ' produtos' : json.message);
      if (json.ok) location.reload();
    });
  });
  document.getElementById('btnBust')?.addEventListener('click', async () => {
    const json = await post('bust_cache');
    alert(json.message || (json.ok ? 'Cache limpo' : json.message));
  });
  document.getElementById('btnAdjust')?.addEventListener('click', async () => {
    const ids = document.getElementById('adjIds').value;
    const percent = document.getElementById('adjPct').value;
    if (!ids || !percent) return alert('Informe IDs e percentual.');
    if (!confirm('Aplicar ' + percent + '% nos produtos ' + ids + '?')) return;
    const json = await post('adjust_prices', {
      ids,
      percent,
      cycles: document.getElementById('adjCycles').value,
    });
    alert(json.ok ? 'Preços atualizados. Confira o catálogo.' : json.message);
  });
  document.querySelectorAll('.acl-box').forEach((box) => {
    box.addEventListener('change', () => {
      post('save_acl', {
        role_id: box.dataset.role,
        permission: box.dataset.perm,
        allowed: box.checked ? 1 : 0,
      });
    });
  });

  const editor = document.getElementById('cvxEditor');
  if (editor) {
    editor.querySelectorAll('.tab').forEach((tab) => {
      tab.addEventListener('click', () => {
        editor.querySelectorAll('.tab,.tabbox').forEach((el) => el.classList.remove('on'));
        tab.classList.add('on');
        editor.querySelector('.tabbox[data-tab="' + tab.dataset.tab + '"]').classList.add('on');
      });
    });
    document.getElementById('btnGenText')?.addEventListener('click', async () => {
      const json = await post('generate_text', {
        name: editor.name.value,
        features: editor.features_json?.value || '[]',
      });
      if (json.ok) {
        editor.slogan.value = json.short;
        editor.description.value = json.long;
      }
    });
    document.getElementById('btnCardPreview')?.addEventListener('click', () => {
      let feats = [];
      try { feats = JSON.parse(editor.features_json.value || '[]'); } catch (e) {}
      document.getElementById('editCard').innerHTML = cardHtml(
        editor.name.value,
        editor.monthly.value,
        editor.annually.value,
        editor.badge.value,
        feats
      );
    });
    editor.addEventListener('submit', async (e) => {
      if (editor.dataset.ajax !== '1') return;
      e.preventDefault();
      try {
        const data = Object.fromEntries(new FormData(editor).entries());
        const json = await post('save_product', data);
        alert(json.ok ? 'Salvo no WHMCS (PID ' + json.product.id + ')' : json.message);
        if (json.ok) location.href = 'addonmodules.php?module=cvxcatalog&cvx=catalog';
      } catch (err) {
        editor.dataset.ajax = '0';
        editor.submit();
      }
    });
  }

  document.getElementById('cvxGroup')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const json = await post('save_group', data);
    alert(json.ok ? 'Grupo salvo' : json.message);
    if (json.ok) location.reload();
  });

  document.getElementById('cvxImport')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const json = await post('import', data);
    alert(json.ok ? 'Importação concluída' : json.message);
    if (json.ok) location.href = 'addonmodules.php?module=cvxcatalog&cvx=catalog';
  });

  const installTable = document.getElementById('installTable');
  if (installTable) {
    const slugs = () => [...document.querySelectorAll('.inst-slug:checked')].map((el) => el.value);
    document.getElementById('btnInstallSelect')?.addEventListener('click', () => {
      const boxes = [...document.querySelectorAll('.inst-slug')];
      const all = boxes.every((b) => b.checked);
      boxes.forEach((b) => { b.checked = !all; });
    });
    document.getElementById('btnInstallPreview')?.addEventListener('click', (e) => {
      if (e.target && e.target.getAttribute('type') === 'submit') return;
    });
  }

  const wizard = document.getElementById('cvxWizard');
  if (!wizard) return;
  let segments = {};
  try {
    const raw = document.getElementById('cvxSegments')?.textContent || wizard.dataset.segments || '{}';
    segments = JSON.parse(raw);
  } catch (e) {
    console.error('Catálogo: JSON dos segmentos inválido', e);
    segments = {};
  }
  let current = null;

  function show(step) {
    wizard.querySelectorAll('.step').forEach((el) => el.classList.toggle('hide', el.dataset.step !== String(step)));
    wizard.querySelectorAll('.steps span').forEach((el) => el.classList.toggle('on', el.dataset.s === String(step)));
  }

  function esc(v) {
    return String(v ?? '').replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[ch]));
  }

  function catalogSlice(n) {
    const source = (current.catalog && current.catalog.length) ? current.catalog : current.plans.map((name) => ({ name }));
    const rows = source.slice(0, n);
    while (rows.length < n) rows.push({ name: 'Plano ' + (rows.length + 1) });
    return rows;
  }

  function fillPlans(rows) {
    const tb = document.querySelector('#wPlans tbody');
    tb.innerHTML = rows.map((p) => {
      const plan = typeof p === 'string' ? { name: p } : p;
      const feats = encodeURIComponent(JSON.stringify(plan.features || {}));
      return `<tr>
      <td><input name="pname" value="${esc(plan.name || '')}"></td>
      <td><input name="pmonthly" type="number" step="0.01" value="${plan.monthly || ''}" placeholder="Obrigatório ou anual"></td>
      <td><input name="pquarterly" type="number" step="0.01" value="${plan.quarterly || ''}"></td>
      <td><input name="pannually" type="number" step="0.01" value="${plan.annually || ''}"></td>
      <td><input name="pfeat" type="checkbox"${plan.featured ? ' checked' : ''}></td>
      <td class="hide"><input name="pfeats" value="${feats}"></td>
    </tr>`;
    }).join('');
  }

  function fillFeatures() {
    const box = document.getElementById('wFeats');
    if (!current) return;
    if (current.ready && current.catalog && current.catalog.length) {
      const keys = current.features || [];
      let html = '<p><strong>Recursos oficiais do catálogo</strong></p><div class="table-wrap"><table class="cvx-table"><thead><tr><th>Recurso</th>';
      current.catalog.forEach((p) => { html += `<th>${esc(p.name)}</th>`; });
      html += '</tr></thead><tbody>';
      keys.forEach((k) => {
        html += `<tr><td>${esc(k)}</td>`;
        current.catalog.forEach((p) => {
          html += `<td>${esc((p.features || {})[k] || '—')}</td>`;
        });
        html += '</tr>';
      });
      html += '</tbody></table></div>';
      box.innerHTML = html;
      return;
    }
    box.innerHTML = '<p><strong>Recursos do segmento</strong> (valores opcionais por recurso)</p>' +
      current.features.map((f) => `<label>${esc(f)}<input data-feat="${esc(f)}" placeholder="ex.: 50 GB"></label>`).join('');
  }

  function applySegment(seg) {
    current = seg;
    document.getElementById('wGroup').value = current.name;
    document.getElementById('wQty').value = (current.catalog && current.catalog.length) ? current.catalog.length : current.plans.length;
    document.getElementById('wType').value = current.type;
    if (current.module) document.getElementById('wModule').value = current.module;
    const email = document.getElementById('wEmail');
    [...email.options].forEach((o) => {
      if (o.value === current.email) email.value = o.value;
    });
    fillPlans(catalogSlice(Number(document.getElementById('wQty').value)));
    fillFeatures();
  }

  function buildPreview() {
    const plans = collectPlans();
    if (!plans.length) {
      alert('Preencha os planos.');
      return false;
    }
    if (plans.some((p) => !(+p.monthly > 0 || +p.annually > 0))) {
      alert('Cada plano precisa de preço mensal ou anual.');
      return false;
    }
    document.getElementById('wPreview').innerHTML = `
      <p><strong>${plans.length} produtos</strong> prontos no grupo <strong>${esc(document.getElementById('wGroup').value)}</strong>${current.ready ? ' · catálogo oficial preenchido automaticamente' : ''}.</p>
      <p>Módulo: ${esc(document.getElementById('wModule').value || 'nenhum')} · Package: ${esc(document.getElementById('wPackage').value || '—')} · E-mail: ${esc(document.getElementById('wEmail').value)}</p>
      <ul>${plans.map((p) => `<li>${esc(p.name)} — mensal ${p.monthly || '—'} / trimestral ${p.quarterly || '—'} / anual ${p.annually || '—'}</li>`).join('')}</ul>`;
    document.getElementById('wCards').innerHTML = plans.map((p) => {
      const feats = Object.entries(p.feature_values || {}).map(([name, value]) => ({ name, value }));
      return cardHtml(p.name, p.monthly, p.annually, p.featured ? 'Mais Popular' : '', feats);
    }).join('');
    return true;
  }

  wizard.querySelectorAll('.seg-card').forEach((card) => {
    card.addEventListener('click', (ev) => {
      const seg = segments[card.dataset.seg];
      if (!seg) return;
      if (seg.ready) return;
      ev.preventDefault();
      wizard.querySelectorAll('.seg-card').forEach((c) => c.classList.remove('on'));
      card.classList.add('on');
      applySegment(seg);
      show(2);
    });
  });

  const preSeg = wizard.dataset.seg || new URLSearchParams(location.search).get('seg');
  if (preSeg && segments[preSeg] && !segments[preSeg].ready) {
    applySegment(segments[preSeg]);
    show(2);
  }

  document.getElementById('wQty')?.addEventListener('change', () => {
    if (!current) return;
    const n = Math.max(1, Math.min(12, Number(document.getElementById('wQty').value) || 4));
    fillPlans(catalogSlice(n));
  });

  const bind = (id, fn) => { const el = document.getElementById(id); if (el) el.onclick = fn; };
  bind('wBack2', () => show(1));
  bind('wNext2', () => show(3));
  bind('wBack3', () => show(2));
  bind('wNext3', () => { if (buildPreview()) show(4); });
  bind('wBack4', () => show(current && current.ready ? 2 : 3));
  bind('wCreate', async () => {
    if (!current) return alert('Escolha um segmento.');
    try {
      const json = await post('bulk_create', {
        segment: current.slug,
        group_name: document.getElementById('wGroup').value,
        type: document.getElementById('wType').value,
        module: document.getElementById('wModule').value,
        servergroup: document.getElementById('wServer').value,
        package: document.getElementById('wPackage').value,
        welcomeemail_name: document.getElementById('wEmail').value,
        autosetup: document.getElementById('wSetup').value,
        on_exists: document.getElementById('wExists').value,
        plans: JSON.stringify(collectPlans()),
      });
      const box = document.getElementById('wResult');
      if (box) {
        box.classList.remove('hide');
        box.textContent = JSON.stringify(json, null, 2);
      }
      if (json.ok) {
        alert((json.created || []).length + ' criados, ' + (json.skipped || []).length + ' ignorados, ' + (json.errors || []).length + ' erros. Lote ' + json.batch_id);
      } else {
        alert(json.message || 'Não foi possível criar.');
      }
    } catch (err) {
      alert(err.message || 'Falha ao criar. Use o botão do formulário abaixo do preview.');
    }
  });

  function collectFeatures() {
    return [...document.querySelectorAll('#wFeats [data-feat]')].map((input) => ({
      name: input.dataset.feat,
      value: input.value,
    }));
  }

  function collectPlans() {
    const shared = collectFeatures();
    return [...document.querySelectorAll('#wPlans tbody tr')].map((tr) => {
      let perPlan = {};
      const hidden = tr.querySelector('[name=pfeats]');
      if (hidden && hidden.value) {
        try { perPlan = JSON.parse(decodeURIComponent(hidden.value)); } catch (e) { perPlan = {}; }
      }
      const feature_values = Object.keys(perPlan).length ? perPlan : Object.fromEntries(shared.map((f) => [f.name, f.value]));
      return {
        name: tr.querySelector('[name=pname]').value,
        monthly: tr.querySelector('[name=pmonthly]').value,
        quarterly: tr.querySelector('[name=pquarterly]')?.value || '',
        annually: tr.querySelector('[name=pannually]').value,
        featured: tr.querySelector('[name=pfeat]').checked ? 1 : 0,
        feature_values,
      };
    }).filter((p) => p.name);
  }
})();
