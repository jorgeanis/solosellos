// Archivo: assets/js/app.js

// --- LÓGICA DE LA APLICACIÓN PRINCIPAL ---

    document.addEventListener('DOMContentLoaded', function () {

        // --- ELEMENTOS DEL DOM (Formulario Principal) ---
        const formPedido = document.getElementById('form-pedido');
        const pasteArea = document.getElementById('paste-area');
        const imagenBase64Input = document.getElementById('imagen_base64');
        const previewContainer = document.getElementById('preview-container');
        const previewImage = document.getElementById('preview-image');
        const removeImageBtn = document.getElementById('remove-image');
        const listaPedidos = document.getElementById('lista-pedidos');

        // --- ELEMENTOS DEL DOM (Modal de Edición) ---
        const editModalEl = document.getElementById('editModal');
        const editModal = new bootstrap.Modal(editModalEl);
        const formEditPedido = document.getElementById('form-edit-pedido');
        const editPasteArea = document.getElementById('edit_paste-area');
        const editImagenBase64Input = document.getElementById('edit_imagen_base64');
        const editPreviewContainer = document.getElementById('edit_preview-container');
        const editPreviewImage = document.getElementById('edit_preview-image');
        const editRemoveImageBtn = document.getElementById('edit_remove-image');

        // --- EVENTO PARA INICIALIZAR AUTOCOMPLETADO EN MODAL ---
        editModalEl.addEventListener('shown.bs.modal', () => {
            const editDireccionInput = document.getElementById('edit_direccion');
            const options = {
                componentRestrictions: { country: 'ar' },
                fields: ["address_components", "geometry", "name"],
                types: ["address"],
            };
            new google.maps.places.Autocomplete(editDireccionInput, options);
        });

        // --- LÓGICA PARA PEGAR IMAGEN ---
        pasteArea.addEventListener('paste', handlePaste.bind(null, imagenBase64Input, previewImage, previewContainer, pasteArea, removeImageBtn));
        removeImageBtn.addEventListener('click', removeImage.bind(null, imagenBase64Input, previewImage, previewContainer, pasteArea, removeImageBtn));
        editPasteArea.addEventListener('paste', handlePaste.bind(null, editImagenBase64Input, editPreviewImage, editPreviewContainer, editPasteArea, editRemoveImageBtn));
        editRemoveImageBtn.addEventListener('click', removeImage.bind(null, editImagenBase64Input, editPreviewImage, editPreviewContainer, editPasteArea, editRemoveImageBtn, true));

        function handlePaste(input, img, pContainer, pArea, rmBtn, event) {
            event.preventDefault();
            const items = (event.clipboardData || window.clipboardData).items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const file = items[i].getAsFile();
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        input.value = e.target.result;
                        img.src = e.target.result;
                        pContainer.style.display = 'block';
                        pArea.style.display = 'none';
                        rmBtn.style.display = 'inline-block';
                    };
                    reader.readAsDataURL(file);
                    break;
                }
            }
        }

        function removeImage(input, img, pContainer, pArea, rmBtn, isEdit = false) {
            input.value = '';
            pArea.style.display = 'block';
            rmBtn.style.display = 'none';
            if (!isEdit) {
                img.src = '';
                pContainer.style.display = 'none';
            } else {
                const originalSrc = document.getElementById('edit_id').dataset.originalImage;
                img.src = originalSrc;
            }
        }

        // --- LÓGICA DE FORMULARIOS (CREAR Y EDITAR) ---
        formPedido.addEventListener('submit', function(event) {
            event.preventDefault();
            if (!imagenBase64Input.value) { alert('Por favor, pega una imagen para el sello.'); return; }
            const formData = new FormData(formPedido);
            fetch('api/guardar_pedido.php', { method: 'POST', body: formData })
                .then(res => res.json()).then(handleFormResponse.bind(null, formPedido, null));
        });

        formEditPedido.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(formEditPedido);
            fetch('api/actualizar_pedido.php', { method: 'POST', body: formData })
                .then(res => res.json()).then(handleFormResponse.bind(null, null, editModal));
        });

        function handleFormResponse(form, modal, data) {
            if (data.status === 'success') {
                alert(data.message || '¡Operación exitosa!');
                if (form) form.reset();
                if (modal) modal.hide();
                cargarPedidos();
            } else {
                alert('Error: ' + data.message);
            }
        }

        // --- LÓGICA DE PEDIDOS (CARGAR, EDITAR, ENTREGAR) ---
        window.cargarPedidos = function() {
            listaPedidos.innerHTML = `<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>`;
            fetch('api/obtener_pedidos.php')
                .then(res => {
                    if (!res.ok) {
                        // Si la respuesta del servidor no es 2xx, lanzar un error con el cuerpo de la respuesta
                        return res.json().then(errorInfo => {
                            throw new Error(`Error del servidor (HTTP ${res.status}): ${errorInfo.message || 'Sin detalles.'}`);
                        });
                    }
                    return res.json();
                })
                .then(pedidos => {
                    listaPedidos.innerHTML = '';
                    if (pedidos.length === 0) {
                        listaPedidos.innerHTML = '<p class="text-muted text-center mt-5">No hay pedidos pendientes.</p>';
                        return;
                    }
                    pedidos.forEach(pedido => {
                        const whatsappLink = `https://wa.me/549${pedido.whatsapp.replace(/[^0-9]/g, '')}`;
                        const pedidoCard = document.createElement('div');
                        pedidoCard.className = 'card mb-3';
                        pedidoCard.id = `pedido-${pedido.id}`;
                        pedidoCard.innerHTML = `
                            <div class="row g-0">
                                <div class="col-4 d-flex justify-content-center align-items-center p-2"><img src="uploads/${pedido.imagen_sello}" class="img-fluid rounded-start" style="max-height: 120px;"></div>
                                <div class="col-8"><div class="card-body">
                                    <h5 class="card-title">${pedido.direccion}</h5>
                                    <p class="card-text"><small class="text-muted">${pedido.otros_datos || 'Sin datos adicionales'}</small></p>
                                    <a href="${whatsappLink}" target="_blank" class="btn btn-success btn-sm">WhatsApp</a>
                                    <button class="btn btn-secondary btn-sm" onclick="abrirModalEdicion(${pedido.id})">Editar</button>
                                    <button class="btn btn-outline-primary btn-sm" onclick="marcarEntregado(${pedido.id}, this)">Entregado</button>
                                </div></div>
                            </div>`;
                        listaPedidos.appendChild(pedidoCard);
                    });
                })
                .catch(error => {
                    console.error('Error al cargar pedidos:', error);
                    listaPedidos.innerHTML = `<div class="alert alert-danger"><strong>Error al cargar los pedidos:</strong><br><pre>${error.message}</pre></div>`;
                });
        }

        window.abrirModalEdicion = function(id) {
            fetch(`api/obtener_un_pedido.php?id=${id}`)
                .then(res => res.json())
                .then(pedido => {
                    if (pedido.status === 'error') { alert(pedido.message); return; }
                    document.getElementById('edit_id').value = pedido.id;
                    document.getElementById('edit_direccion').value = pedido.direccion;
                    document.getElementById('edit_whatsapp').value = pedido.whatsapp;
                    document.getElementById('edit_otros_datos').value = pedido.otros_datos;
                    const originalImageUrl = `uploads/${pedido.imagen_sello}`;
                    editPreviewImage.src = originalImageUrl;
                    document.getElementById('edit_id').dataset.originalImage = originalImageUrl;
                    editImagenBase64Input.value = '';
                    editPreviewContainer.style.display = 'block';
                    editPasteArea.style.display = 'block';
                    editRemoveImageBtn.style.display = 'none';
                    editModal.show();
                });
        }

        window.marcarEntregado = function(id, element) {
            if (!confirm(`¿Estás seguro de que quieres marcar el pedido como entregado?`)) return;
            fetch('api/marcar_entregado.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: id }) })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // En lugar de recargar, solo modificamos el DOM
                        const card = document.getElementById(`pedido-${id}`);
                        if(card) {
                            card.classList.add('pedido-entregado');
                            element.disabled = true;
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        // Carga inicial de pedidos
        cargarPedidos();
    });

function initAutocomplete() {
    // Solo inicializar si estamos en la página de la app
    const direccionInput = document.getElementById('direccion');
    if (direccionInput) {
        const options = {
            componentRestrictions: { country: 'ar' },
            fields: ["address_components", "geometry", "name"],
            types: ["address"],
        };
        new google.maps.places.Autocomplete(direccionInput, options);
    }
}