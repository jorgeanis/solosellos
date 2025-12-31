/**
 * Renderiza una vista previa de la plantilla dentro de un contenedor específico.
 * @param {HTMLElement} container - El elemento contenedor donde se renderizará la vista previa.
 * @param {object} datosPlantilla - Un objeto con los datos de la plantilla.
 */
function renderizarPlantilla(container, datosPlantilla, colorTexto = 'black', fontScale = 1) {
    if (!container || !datosPlantilla) {
        console.error('Faltan el contenedor o los datos para renderizar la plantilla.');
        return;
    }

    // Seleccionar los elementos de línea DENTRO del contenedor específico
    const linea1 = container.querySelector('.plantilla-linea-1');
    const linea2 = container.querySelector('.plantilla-linea-2');
    const linea3 = container.querySelector('.plantilla-linea-3');
    const linea4 = container.querySelector('.plantilla-linea-4');

    // Array de líneas para no repetir código
    const lineas = [
        { el: linea1, data: datosPlantilla.linea1 },
        { el: linea2, data: datosPlantilla.linea2 },
        { el: linea3, data: datosPlantilla.linea3 },
        { el: linea4, data: datosPlantilla.linea4 },
    ];

    lineas.forEach(item => {
        if (item.el && item.data) {
            // Si la línea no tiene texto, se colapsa para no ocupar espacio.
            if (!item.data.texto) {
                item.el.textContent = '';
                item.el.style.height = '0';
                item.el.style.marginTop = '0';
                item.el.style.marginBottom = '0';
                item.el.style.visibility = 'hidden';
            } else {
                // Si tiene texto, se aplican todos los estilos y se asegura que sea visible.
                item.el.textContent = item.data.texto;
                item.el.style.fontFamily = item.data.fuente + ', sans-serif';
                
                // Aplicar factor de escala al tamaño de la fuente
                const finalSize = (item.data.tamano || 10) * fontScale;
                item.el.style.fontSize = finalSize + 'px';

                // Escalar también el margen superior y definir un line-height consistente
                const finalMargin = (item.data.margen || 0) * fontScale;
                item.el.style.marginTop = finalMargin + 'px';
                item.el.style.lineHeight = 1.1;

                item.el.style.fontWeight = item.data.negrita ? 'bold' : 'normal';
                item.el.style.textAlign = item.data.alineacion;
                item.el.style.textTransform = item.data.mayuscula ? 'uppercase' : 'none';
                item.el.style.color = colorTexto; // Aplicar color de texto
                item.el.style.height = 'auto';
                item.el.style.marginBottom = 'auto';
                item.el.style.visibility = 'visible';
            }
        }
    });
}

// Exponer la función al objeto global para poder llamarla desde otros scripts.
window.renderizarPlantilla = renderizarPlantilla;