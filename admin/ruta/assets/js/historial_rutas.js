document.addEventListener('DOMContentLoaded', function() {
    const lista = document.getElementById('historial-rutas-lista');

    fetch('api/obtener_historial_rutas.php')
        .then(response => response.json())
        .then(rutas => {
            lista.innerHTML = '';
            if (rutas.length === 0) {
                lista.innerHTML = '<p class="text-muted text-center mt-4">No hay rutas guardadas en el historial.</p>';
                return;
            }
            rutas.forEach(ruta => {
                const fecha = new Date(ruta.fecha_creacion).toLocaleString('es-AR');
                const link = document.createElement('a');
                link.href = `ver_ruta.php?id=${ruta.id}`;
                link.className = 'list-group-item list-group-item-action flex-column align-items-start';
                link.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">${ruta.nombre_ruta}</h5>
                        <small>${fecha}</small>
                    </div>
                    <small>Haz clic para ver el detalle de esta ruta.</small>
                `;
                lista.appendChild(link);
            });
        })
        .catch(error => {
            lista.innerHTML = '<p class="text-danger text-center mt-4">Error al cargar el historial de rutas.</p>';
            console.error('Error:', error);
        });
});