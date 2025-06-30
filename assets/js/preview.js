function updatePreview() {
  for (let i = 1; i <= 4; i++) {
    const textInput = document.getElementById('linea_input_' + i) ||
                      document.querySelector(`input[name='linea${i}']`);
    const text = textInput ? textInput.value : '';

    const fontEl = document.querySelector(`[name="fuente${i}"]`);
    const sizeEl = document.getElementById('tamano' + i);
    const boldEl = document.querySelector(`[name="negrita${i}"]`);
    const italicEl = document.getElementById('cursiva' + i);
    const alignEl = document.getElementById('alineacion' + i);
    const marginEl = document.getElementById('margen_top' + i);

    const font = fontEl ? fontEl.value : '';
    const size = sizeEl ? sizeEl.value + 'px' : '';
    const bold = boldEl ? boldEl.checked : false;
    const italic = italicEl ? italicEl.checked : false;
    const align = alignEl ? alignEl.value : '';
    const margin = marginEl ? parseInt(marginEl.value || 0) : 0;

    const preview = document.getElementById('preview_linea' + i);
    if (preview) {
      if (font) preview.style.fontFamily = font;
      if (size) preview.style.fontSize = size;
      preview.style.fontWeight = bold ? 'bold' : 'normal';
      preview.style.fontStyle = italic ? 'italic' : 'normal';
      if (align) preview.style.textAlign = align;
      preview.style.marginTop = margin + 'px';
      preview.textContent = text;
    }

    document.querySelectorAll('.modelo-card').forEach(card => {
      const line = card.querySelectorAll('.linea-prev')[i - 1];
      if (!line) return;
      line.textContent = text;
      const chk = document.getElementById('chk_linea' + i);
      if (chk) line.style.display = chk.checked ? 'block' : 'none';
    });
  }
}

function setAlign(i, direction, el) {
  const group = el.parentElement;
  group.querySelectorAll('button').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  const input = document.getElementById('alineacion' + i);
  if (input) input.value = direction;
  updatePreview();
}

function cambiarMargen(i, delta) {
  const input = document.getElementById('margen_top' + i);
  if (!input) return;
  let valor = parseInt(input.value) || 0;
  valor = valor + delta;
  input.value = valor;
  updatePreview();
}

function showTab(n) {
  document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
  document.querySelectorAll('.tab-buttons button').forEach(btn => btn.classList.remove('active'));
  const activeTab = document.getElementById('tab' + n);
  if (activeTab) activeTab.classList.add('active');
  const btn = document.querySelectorAll('.tab-buttons button')[n - 1];
  if (btn) btn.classList.add('active');
  updatePreview();
}

document.addEventListener('DOMContentLoaded', updatePreview);
