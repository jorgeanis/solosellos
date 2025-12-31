<?php
require_once '../../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Ruta Guardada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>#map { height: 100vh; } #panel-direcciones { height: 100vh; overflow-y: auto; }</style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div id="panel-direcciones" class="col-md-4 bg-light p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Ruta Guardada</h3>
                    <a href="historial_rutas.php" class="btn btn-secondary">Volver al Historial</a>
                </div>
                <div id="lista-ruta" class="list-group"></div>
            </div>
            <div class="col-md-8 p-0"><div id="map"></div></div>
        </div>
    </div>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAXXtVhQP_A1Ix8lgPtkpwqR6XVzAQLQzs&libraries=places"></script>
    <script>
    function initMap() {
        const rutaId = new URLSearchParams(window.location.search).get('id');
        if (!rutaId) return;

        fetch(`api/obtener_una_ruta.php?id=${rutaId}`)
            .then(response => response.json())
            .then(pedidos => {
                if (pedidos.length < 1) {
                    document.getElementById('lista-ruta').innerHTML = '<div class="alert alert-warning">No se encontraron paradas para esta ruta.</div>';
                    return;
                }

                const directionsService = new google.maps.DirectionsService();
                const directionsRenderer = new google.maps.DirectionsRenderer();
                const map = new google.maps.Map(document.getElementById('map'), { zoom: 12, center: { lat: -34.6037, lng: -58.3816 } });
                directionsRenderer.setMap(map);

                const waypoints = pedidos.map(p => ({ location: p.direccion, stopover: true }));
                const origin = waypoints.shift().location;
                const destination = waypoints.length > 0 ? waypoints.pop().location : origin;

                directionsService.route({
                    origin: origin,
                    destination: destination,
                    waypoints: waypoints,
                    optimizeWaypoints: false, // FALSO: queremos el orden guardado, no uno nuevo
                    travelMode: google.maps.TravelMode.DRIVING
                }, (response, status) => {
                    if (status === 'OK') {
                        directionsRenderer.setDirections(response);
                        const panel = document.getElementById('lista-ruta');
                        panel.innerHTML = '';
                        pedidos.forEach((pedido, index) => {
                            const navUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(pedido.direccion)}`;
                            const stopNumber = index + 1;
                            const entregadoClass = pedido.entregado ? 'pedido-entregado' : '';
                            panel.innerHTML += `
                                <div class="list-group-item ${entregadoClass}">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">${stopNumber}. ${pedido.direccion}</h5>
                                    </div>
                                    <a href="${navUrl}" target="_blank" class="stretched-link text-decoration-none"><small>Navegar a este destino</small></a>
                                </div>`;
                        });
                    } else {
                        window.alert('No se pudo dibujar la ruta: ' + status);
                    }
                });
            });
    }
    // El script de Google Maps llama a initMap global cuando está listo
    window.initMap = initMap;
    </script>
</body>
</html>