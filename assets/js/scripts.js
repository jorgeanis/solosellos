document.addEventListener('DOMContentLoaded', () => {
    updateProgress(1);
    updatePreview();
});

function selectModel(modelId) {
    document.querySelectorAll('.model').forEach(model => model.classList.remove('selected'));
    document.querySelector(`.model[data-id="${modelId}"]`).classList.add('selected');
    document.getElementById('selected_model').value = modelId;
    goToStep2();
}

function goToStep1() {
    switchStep(1);
}

function goToStep2() {
    switchStep(2);
}

function goToStep3() {
    fillHiddenFields();
    switchStep(3);
}

function goToStep4() {
    updateSummary();
    switchStep(4);
}

function switchStep(step) {
    for (let i = 1; i <= 4; i++) {
        document.getElementById(`step-${i}`).classList.toggle('active-step', i === step);
        document.querySelector(`.step-${i}`).classList.toggle('current-step', i === step);
        document.querySelector(`.step-${i}`).classList.toggle('active-step', i <= step);
    }
}

function updateProgress(step) {
    document.querySelectorAll(".circle").forEach((circle, i) => {
        circle.classList.remove("current-step", "active-step");
        if (i + 1 <= step) circle.classList.add("active-step");
        if (i + 1 === step) circle.classList.add("current-step");
    });
}

function updatePreview() {
    const lines = [1, 2, 3, 4];
    lines.forEach(i => {
        const text = document.getElementById(`line${i}`).value;
        document.querySelectorAll(`.template-line.line-${i}`).forEach(el => el.textContent = text);
    });
}

function fillHiddenFields() {
    ['line1', 'line2', 'line3', 'line4'].forEach(id => {
        document.getElementById(`hidden_${id}`).value = document.getElementById(id).value;
    });
    document.getElementById("svg_content").value = generateSVG();
}

function generateSVG() {
    const lines = document.querySelectorAll(".template-line");
    let svg = `<svg width="200" height="100" xmlns="http://www.w3.org/2000/svg">`;
    lines.forEach((line, index) => {
        let y = 20 + index * 20;
        svg += `<text x="10" y="${y}" font-family="${line.style.fontFamily}" font-size="${line.style.fontSize}" font-weight="${line.style.fontWeight}" font-style="${line.style.fontStyle}" fill="white">${line.textContent}</text>`;
    });
    svg += `</svg>`;
    return svg;
}
