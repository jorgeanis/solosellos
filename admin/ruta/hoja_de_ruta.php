<?php
require_once '../includes/auth.php';
require_once '../includes/settings.php';

$delivery_origin = get_setting('delivery_origin');
$ruta_id = $_GET['ruta_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Hoja de Ruta #<?= htmlspecialchars($ruta_id) ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        #map {
            width: 100%;
            height: 100%;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .pedido-entregado {
            background-color: #f0fdf4 !important; /* green-50 */
            opacity: 0.8;
        }
    </style>
    <script>
        const GLOBAL_ORIGIN = <?= json_encode($delivery_origin) ?>;
        const RUTA_ID = <?= json_encode($ruta_id) ?>;
    </script>
</head>
<body class="bg-gray-100 h-screen overflow-hidden flex flex-col md:flex-row">

    <!-- Mobile Header (Visible only on mobile) -->
    <div class="md:hidden bg-white shadow-sm p-3 z-20 flex justify-between items-center h-14 shrink-0">
        <h1 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-route text-brand-600 mr-2"></i> Ruta #<?= htmlspecialchars($ruta_id) ?>
        </h1>
        <a href="../pedidos.php" class="text-gray-500 hover:text-gray-700 px-2">
            <i class="fas fa-times text-xl"></i>
        </a>
    </div>

    <!-- Panel de Direcciones (Lista) -->
    <div id="panel-direcciones" class="w-full md:w-96 lg:w-[450px] bg-white shadow-xl z-10 flex flex-col h-[60vh] md:h-full order-2 md:order-1 relative border-r border-gray-200">
        
        <!-- Desktop Header -->
        <div class="hidden md:flex justify-between items-center p-4 border-b border-gray-200 bg-white">
            <h3 class="text-xl font-bold text-gray-800">Ruta #<?= htmlspecialchars($ruta_id) ?></h3>
            <a href="../pedidos.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>

        <!-- Configuración de Ruta (Hora Inicio) -->
        <div class="px-4 py-3 bg-white border-b border-gray-100 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <i class="fas fa-clock text-brand-500"></i>
                <span class="font-semibold">Salida:</span>
            </div>
            <input type="time" id="hora-inicio" class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none" onchange="recalcularHorarios()">
        </div>

        <!-- Status Bar -->
        <div id="status-bar" class="px-4 py-2 bg-brand-50 text-brand-700 text-xs font-semibold flex justify-between items-center border-b border-brand-100">
            <span><i class="fas fa-info-circle mr-1"></i> Calculando ruta óptima...</span>
        </div>

        <!-- Lista Scrollable -->
        <div id="lista-ruta" class="flex-1 overflow-y-auto custom-scroll p-0 relative bg-gray-50">
            <!-- Spinner de carga -->
            <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                <i class="fas fa-circle-notch fa-spin text-3xl mb-2 text-brand-500"></i>
                <p>Cargando pedidos...</p>
            </div>
        </div>
    </div>


    <!-- Mapa -->
    <div class="flex-1 h-[40vh] md:h-full order-1 md:order-2 relative bg-gray-200">
        <div id="map"></div>
        <button onclick="initMap()" class="absolute top-4 right-4 bg-white p-2 rounded-full shadow-lg text-gray-600 md:hidden z-10 active:bg-gray-100">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAXXtVhQP_A1Ix8lgPtkpwqR6XVzAQLQzs&libraries=places&callback=initMap" async defer></script>

    <script>
    // Variables globales
    let g_routeData = null; 
    let g_pedidosOrdenados = null; 
    let g_tiempoServicioMin = 5; 
    let g_userMarker = null; // Marcador de ubicación del usuario
    let g_map = null; // Referencia global al mapa

    document.addEventListener('DOMContentLoaded', () => {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('hora-inicio').value = `${hours}:${minutes}`;
    });

    function marcarEntregadoRuta(id, element) {
        if (!confirm(`¿Confirmar entrega del pedido?`)) return;

        const originalContent = element.innerHTML;
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        element.disabled = true;

        fetch('api/marcar_entregado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const card = element.closest('.pedido-card');
                if (card) {
                    card.classList.add('pedido-entregado');
                    element.className = 'px-4 py-1.5 rounded-full text-xs font-bold shadow-sm transition-all bg-green-100 text-green-700 cursor-default border border-green-200';
                    element.innerHTML = '<i class="fas fa-check mr-1"></i> Listo';
                }
            } else {
                alert('Error: ' + data.message);
                element.innerHTML = originalContent;
                element.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al marcar como entregado:', error);
            alert('Ocurrió un error de conexión.');
            element.innerHTML = originalContent;
            element.disabled = false;
        });
    }

    function renderizarLista() {
        if (!g_pedidosOrdenados || !g_routeData) return;

        const panel = document.getElementById('lista-ruta');
        panel.innerHTML = ''; 

        const horaInput = document.getElementById('hora-inicio').value;
        const [hInicio, mInicio] = horaInput.split(':').map(Number);
        
        let currentTime = new Date();
        currentTime.setHours(hInicio, mInicio, 0, 0);

        const legs = g_routeData.legs;
        let tiempoAcumuladoSegundos = 0;

        g_pedidosOrdenados.forEach((pedido, index) => {
            const stopNumber = index + 1;
            const isEntregado = pedido.entregado == 1;

            if (GLOBAL_ORIGIN && GLOBAL_ORIGIN.trim() !== '') {
                if (index < legs.length) {
                    tiempoAcumuladoSegundos += legs[index].duration.value;
                }
            } else {
                if (index === 0) {
                    tiempoAcumuladoSegundos = 0; 
                } else {
                    if ((index - 1) < legs.length) {
                        tiempoAcumuladoSegundos += legs[index - 1].duration.value;
                    }
                }
            }

            let arrivalDate = new Date(currentTime.getTime() + (tiempoAcumuladoSegundos * 1000));
            let arrivalStr = arrivalDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            tiempoAcumuladoSegundos += (g_tiempoServicioMin * 60);

            let clienteInfo = '<span class="text-gray-400 italic">Sin datos</span>';
            let otrosTexto = pedido.otros_datos;
            let whatsappNum = pedido.whatsapp;

            try {
                const datos = JSON.parse(pedido.otros_datos);
                if (datos.cliente) {
                    clienteInfo = `<span class="font-semibold text-gray-800">${datos.cliente}</span>`;
                    otrosTexto = ''; 
                }
                if (!whatsappNum && datos.telefono) whatsappNum = datos.telefono;
                if (!whatsappNum && datos.whatsapp) whatsappNum = datos.whatsapp;
            } catch(e) {}

            let whatsappBtn = '';
            if (whatsappNum) {
                let cleanNum = whatsappNum.replace(/\D/g, ''); 
                if (cleanNum.length === 10) {
                    cleanNum = '549' + cleanNum;
                } else if (cleanNum.length > 10 && !cleanNum.startsWith('54')) {
                    if(cleanNum.startsWith('0')) cleanNum = '549' + cleanNum.substring(1);
                }
                
                whatsappBtn = `
                    <a href="https://wa.me/${cleanNum}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-500 text-white shadow-sm hover:bg-green-600 transition-colors ml-2" title="WhatsApp al cliente">
                        <i class="fab fa-whatsapp text-lg"></i>
                    </a>
                `;
            } else {
                 whatsappBtn = `
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-400 cursor-not-allowed ml-2" title="Sin número">
                        <i class="fab fa-whatsapp text-lg"></i>
                    </span>
                `;
            }

            const hasCoords = pedido.lat && pedido.lng;
            const destination = hasCoords ? `${pedido.lat},${pedido.lng}` : encodeURIComponent(pedido.direccion);
            const navUrl = `https://www.google.com/maps/dir/?api=1&destination=${destination}`;
            
            const itemDiv = document.createElement('div');
            itemDiv.className = `pedido-card relative bg-white border-b border-gray-100 p-4 transition-all hover:bg-gray-50 ${isEntregado ? 'pedido-entregado' : ''}`;
            itemDiv.id = `pedido-ruta-${pedido.id}`;

            itemDiv.innerHTML = `
                <div class="flex justify-between items-start">
                    <div class="flex-1 pr-2">
                        <div class="flex items-center mb-1">
                            <div class="bg-brand-600 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center shadow-sm mr-2 flex-shrink-0">
                                ${stopNumber}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-brand-600 bg-brand-50 px-1.5 rounded inline-block w-max mb-0.5">
                                    <i class="far fa-clock mr-1"></i>${arrivalStr}
                                </span>
                                <h5 class="text-sm font-bold text-gray-900 leading-tight">
                                    ${pedido.direccion}
                                </h5>
                            </div>
                        </div>
                        
                        <div class="ml-8 mb-2 text-sm text-gray-600">
                            ${clienteInfo}
                            ${otrosTexto ? `<div class="text-xs text-gray-500 mt-1 truncate max-w-[200px]">${otrosTexto}</div>` : ''}
                        </div>

                        <div class="ml-8 flex items-center relative z-30 pt-1">
                            <a href="${navUrl}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors border border-gray-200">
                                <i class="fas fa-location-arrow mr-1.5 text-blue-500"></i> Ir
                            </a>
                            ${whatsappBtn}
                        </div>
                    </div>

                    <div class="flex flex-col items-end space-y-3 pl-2 border-l border-gray-100 min-w-[80px]">
                        <div class="h-12 w-16 bg-white border border-gray-200 rounded p-1 shadow-sm flex items-center justify-center overflow-hidden">
                            <img src="uploads/${pedido.imagen_sello}" class="max-h-full max-w-full object-contain" alt="Sello">
                        </div>

                        <button 
                            class="z-20 w-full px-2 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-brand-500
                            ${isEntregado 
                                ? 'bg-green-100 text-green-700 cursor-default border border-green-200' 
                                : 'bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800'}"
                            ${isEntregado ? 'disabled' : ''}
                            onclick="marcarEntregadoRuta(${pedido.id}, this);">
                            ${isEntregado 
                                ? '<i class="fas fa-check mr-1"></i> Listo' 
                                : 'Entregado'}
                        </button>
                    </div>
                </div>
            `;
            
            panel.appendChild(itemDiv);
        });
    }

    function recalcularHorarios() {
        renderizarLista();
    }

    function iniciarSeguimientoUbicacion() {
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(
                (position) => {
                    const pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };

                    const icon = {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 8,
                        fillColor: "#4285F4",
                        fillOpacity: 1,
                        strokeWeight: 2,
                        strokeColor: "white",
                    };

                    if (!g_userMarker) {
                        g_userMarker = new google.maps.Marker({
                            position: pos,
                            map: g_map,
                            title: "Mi Ubicación",
                            icon: icon,
                            zIndex: 999 
                        });
                    } else {
                        g_userMarker.setPosition(pos);
                    }
                },
                (error) => {
                    console.warn("Error Geolocation: " + error.message);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 1000,
                    timeout: 20000
                }
            );
        } else {
            console.log("Navegador no soporta Geolocalización");
        }
    }

    function initMap() {
        const url = RUTA_ID ? `api/obtener_pedidos.php?ruta_id=${RUTA_ID}` : 'api/obtener_pedidos.php';
        const statusBar = document.getElementById('status-bar');
        
        fetch(url)
            .then(response => response.json())
            .then(pedidos => {
                if (pedidos.length < 1) {
                    document.getElementById('lista-ruta').innerHTML = `
                        <div class="p-6 text-center text-gray-500">
                            <i class="fas fa-exclamation-triangle text-yellow-400 text-4xl mb-3"></i>
                            <p>Se necesita al menos 1 pedido para generar una ruta.</p>
                        </div>`;
                    statusBar.innerHTML = '<span class="text-red-600">Sin pedidos</span>';
                    return;
                }

                const directionsService = new google.maps.DirectionsService();
                const directionsRenderer = new google.maps.DirectionsRenderer({
                    map: null, 
                    suppressMarkers: false,
                    polylineOptions: { strokeColor: "#2563eb", strokeWeight: 5 }
                });
                
                g_map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 12,
                    center: { lat: -34.6037, lng: -58.3816 },
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                    zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_CENTER }
                });
                directionsRenderer.setMap(g_map);

                iniciarSeguimientoUbicacion();

                const waypoints = pedidos.map(p => {
                    if (p.lat && p.lng) {
                        return { location: { lat: parseFloat(p.lat), lng: parseFloat(p.lng) }, stopover: true };
                    }
                    let address = p.direccion;
                    if (!address.toLowerCase().includes('tucumán') && !address.toLowerCase().includes('argentina')) {
                        address += ', Tucumán, Argentina';
                    }
                    return { location: address, stopover: true };
                });

                let origin, destination;
                if (GLOBAL_ORIGIN && GLOBAL_ORIGIN.trim() !== '') {
                    origin = GLOBAL_ORIGIN;
                    destination = waypoints.length > 0 ? waypoints.pop().location : origin;
                } else {
                    origin = waypoints.shift().location;
                    destination = waypoints.length > 0 ? waypoints.pop().location : origin;
                }

                directionsService.route({
                    origin: origin,
                    destination: destination,
                    waypoints: waypoints,
                    optimizeWaypoints: true,
                    travelMode: google.maps.TravelMode.DRIVING
                }, (response, status) => {
                    if (status === 'OK') {
                        g_routeData = response.routes[0];
                        directionsRenderer.setDirections(response);

                        let totalDistance = 0;
                        let totalDuration = 0;
                        g_routeData.legs.forEach(leg => {
                            totalDistance += leg.distance.value;
                            totalDuration += leg.duration.value;
                        });
                        const distKm = (totalDistance / 1000).toFixed(1);
                        const durMins = Math.round(totalDuration / 60);
                        
                        statusBar.innerHTML = `
                            <div class="flex space-x-3 text-gray-600 text-[11px] md:text-xs">
                                <span><i class="fas fa-road text-brand-500"></i> ${distKm}km</span>
                                <span><i class="fas fa-car text-brand-500"></i> ${durMins}m</span>
                                <span><i class="fas fa-flag-checkered text-brand-500"></i> ${pedidos.length}</span>
                            </div>
                        `;

                        g_pedidosOrdenados = [];
                        if (GLOBAL_ORIGIN && GLOBAL_ORIGIN.trim() !== '') {
                            const pedidosWaypoints = pedidos.slice(0, pedidos.length - 1);
                            const ultimoPedido = pedidos[pedidos.length - 1]; 
                            g_routeData.waypoint_order.forEach(index => {
                                g_pedidosOrdenados.push(pedidosWaypoints[index]);
                            });
                            if (ultimoPedido) g_pedidosOrdenados.push(ultimoPedido);
                        } else {
                            const intermediatePedidos = pedidos.slice(1, pedidos.length > 1 ? -1 : undefined);
                            g_pedidosOrdenados.push(pedidos[0]); 
                            g_routeData.waypoint_order.forEach(i => {
                                g_pedidosOrdenados.push(intermediatePedidos[i]);
                            });
                            if (pedidos.length > 1) {
                                g_pedidosOrdenados.push(pedidos[pedidos.length - 1]);
                            }
                        }

                        renderizarLista();

                    } else {
                        statusBar.innerHTML = `<span class="text-red-600 font-bold">Error API: ${status}</span>`;
                        alert('No se pudo calcular la ruta: ' + status);
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('lista-ruta').innerHTML = `
                    <div class="p-4 bg-red-50 text-red-700 m-4 rounded-lg border border-red-200 text-center">
                        <i class="fas fa-wifi text-2xl mb-2"></i>
                        <p class="font-bold">Error de conexión</p>
                    </div>`;
            });
    }
    </script>
</body>
</html>
