document.addEventListener('DOMContentLoaded', function() {
    const historialLista = document.getElementById('historial-lista');

    function cargarHistorial() {
        // Muestra un indicador de carga
        historialLista.innerHTML = `
            <div class="text-center mt-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2">Cargando historial...</p>
            </div>`;

        fetch('api/obtener_entregados.php')
            .then(response => response.json())
            .then(pedidos => {
                historialLista.innerHTML = ''; // Limpiar el indicador de carga
                if (pedidos.length === 0) {
                    historialLista.innerHTML = '<p class="text-muted text-center col-12 mt-5">No hay pedidos en el historial.</p>';
                    return;
                }

                pedidos.forEach(pedido => {
                    const fecha = new Date(pedido.fecha_creacion).toLocaleDateString('es-AR');
                    const pedidoCard = document.createElement('div');
                    pedidoCard.className = 'col-md-6 mb-4';
                    // Usamos la clase .pedido-entregado que ya creamos para darle el estilo atenuado
                    pedidoCard.innerHTML = `
                        <div class="card pedido-entregado h-100">
                            <div class="row g-0">
                                <div class="col-md-4 d-flex justify-content-center align-items-center p-2">
                                    <img src="uploads/${pedido.imagen_sello}" class="img-fluid rounded-start" alt="Diseño del sello" style="max-height: 100px;">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title">${pedido.direccion}</h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                Entregado (Cargado el: ${fecha})
                                            </small>
                                        </p>
                                        <a href="https://wa.me/549${pedido.whatsapp.replace(/[^0-9]/g, '')}" target="_blank" class="btn btn-sm btn-outline-secondary">WhatsApp</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    historialLista.appendChild(pedidoCard);
                });
            })
            .catch(error => {
                console.error('Error al cargar el historial:', error);
                historialLista.innerHTML = '<p class="text-danger text-center col-12 mt-5">Error al cargar el historial.</p>';
            });
    }

    // Cargar el historial al cargar la página
    cargarHistorial();
});
