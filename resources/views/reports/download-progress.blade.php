<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Generando Archivo ZIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-6">
            <svg class="mx-auto h-16 w-16 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <h2 class="text-2xl font-bold text-gray-800 mt-4">Generando Archivo ZIP</h2>
            <p class="text-gray-600 mt-2" id="status-message">Iniciando proceso...</p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span id="progress-text">0%</span>
                <span id="items-count">0 / 0</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div id="progress-bar" class="bg-blue-500 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>

        <!-- Info adicional -->
        <div class="text-center text-sm text-gray-500">
            <p>Este proceso puede tardar varios minutos</p>
            <p class="mt-2">Por favor, no cierres esta ventana</p>
        </div>

        <!-- Botón de cancelar -->
        <div id="cancel-section" class="mt-6">
            <button onclick="cancelProcess()" class="block w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-center transition-colors">
                Cancelar Proceso
            </button>
        </div>

        <!-- Botón de descarga (oculto hasta que termine) -->
        <div id="download-section" class="hidden mt-6">
            <a id="download-link" href="#" class="block w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg text-center transition-colors">
                <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Descargar Archivo ZIP
            </a>
            <button onclick="window.close()" class="block w-full mt-3 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg text-center transition-colors">
                Cerrar Ventana
            </button>
        </div>

        <!-- Mensaje de error (oculto) -->
        <div id="error-section" class="hidden mt-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <div class="flex">
                <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-bold">Error al generar archivo</p>
                    <p id="error-message" class="text-sm mt-1"></p>
                </div>
            </div>
            <button onclick="window.close()" class="mt-4 w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition-colors">
                Cerrar
            </button>
        </div>
    </div>

    <script>
        const startDate = '{{ $startDate }}';
        const endDate = '{{ $endDate }}';
        const userId = '{{ auth()->id() }}';
        const downloadId = 'pdf_download_' + userId + '_' + startDate + '_' + endDate;
        let pollInterval;

        // Elementos del DOM
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const statusMessage = document.getElementById('status-message');
        const itemsCount = document.getElementById('items-count');
        const downloadSection = document.getElementById('download-section');
        const downloadLink = document.getElementById('download-link');
        const errorSection = document.getElementById('error-section');
        const errorMessage = document.getElementById('error-message');

        // Iniciar descarga
        async function startDownload() {
            try {
                console.log('Iniciando descarga con ID:', downloadId);
                console.log('URL:', `/sale/pdf/${startDate}/${endDate}`);

                // Iniciar proceso de generación
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = `/sale/pdf/${startDate}/${endDate}`;
                iframe.onload = function() {
                    console.log('Iframe cargado');
                };
                iframe.onerror = function(error) {
                    console.error('Error al cargar iframe:', error);
                    showError('Error al iniciar el proceso de generación');
                };
                document.body.appendChild(iframe);

                // Esperar 3 segundos antes de empezar a consultar progreso
                setTimeout(() => {
                    console.log('Iniciando polling...');
                    startPolling();
                }, 3000);

            } catch (error) {
                console.error('Error en startDownload:', error);
                showError('Error al iniciar la descarga: ' + error.message);
            }
        }

        // Consultar progreso
        function startPolling() {
            let attempts = 0;
            const maxAttempts = 300; // 10 minutos máximo (300 * 2s)

            pollInterval = setInterval(async () => {
                attempts++;

                try {
                    console.log(`Consultando progreso (intento ${attempts})...`);
                    const response = await fetch(`/sale/pdf/progress/${downloadId}`);

                    console.log('Response status:', response.status);

                    if (!response.ok) {
                        if (response.status === 404) {
                            console.log('Progreso no encontrado aún, esperando...');
                            if (attempts > 10) {
                                showError('El proceso no se ha iniciado. Verifica que haya documentos en el rango de fechas.');
                                clearInterval(pollInterval);
                            }
                        }
                        return;
                    }

                    const data = await response.json();
                    console.log('Datos de progreso:', data);

                    // Actualizar UI
                    if (data.progress !== undefined) {
                        updateProgress(data);
                    }

                    // Si completó o hubo error, detener polling
                    if (data.status === 'completed' || data.status === 'error') {
                        clearInterval(pollInterval);

                        if (data.status === 'completed') {
                            showDownloadComplete(data.download_url);
                        } else {
                            showError(data.message);
                        }
                    }

                    // Timeout de seguridad
                    if (attempts >= maxAttempts) {
                        clearInterval(pollInterval);
                        showError('El proceso ha excedido el tiempo máximo permitido.');
                    }

                } catch (error) {
                    console.error('Error al consultar progreso:', error);
                    if (attempts > 10) {
                        clearInterval(pollInterval);
                        showError('Error al consultar el progreso: ' + error.message);
                    }
                }
            }, 2000); // Consultar cada 2 segundos
        }

        // Actualizar barra de progreso
        function updateProgress(data) {
            const progress = Math.min(data.progress || 0, 100);

            progressBar.style.width = progress + '%';
            progressText.textContent = Math.round(progress) + '%';
            statusMessage.textContent = data.message || 'Procesando...';

            if (data.total && data.current !== undefined) {
                itemsCount.textContent = `${data.current} / ${data.total}`;
            }
        }

        // Mostrar descarga completada
        function showDownloadComplete(url) {
            statusMessage.textContent = '¡Archivo generado exitosamente!';
            progressBar.style.width = '100%';
            progressBar.classList.remove('bg-blue-500');
            progressBar.classList.add('bg-green-500');
            progressText.textContent = '100%';

            if (url) {
                downloadLink.href = url;
            } else {
                downloadLink.href = `/sale/pdf/${startDate}/${endDate}`;
            }

            downloadSection.classList.remove('hidden');
        }

        // Mostrar error
        function showError(message) {
            errorMessage.textContent = message;
            errorSection.classList.remove('hidden');
            progressBar.classList.remove('bg-blue-500');
            progressBar.classList.add('bg-red-500');

            // Ocultar botón de cancelar
            const cancelSection = document.getElementById('cancel-section');
            if (cancelSection) cancelSection.classList.add('hidden');
        }

        // Cancelar proceso
        async function cancelProcess() {
            if (!confirm('¿Estás seguro de que deseas cancelar la generación del ZIP?')) {
                return;
            }

            try {
                // Enviar señal de cancelación al servidor
                await fetch(`/sale/pdf/cancel/${downloadId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                // Detener polling
                if (pollInterval) {
                    clearInterval(pollInterval);
                }

                // Mostrar mensaje de cancelación
                statusMessage.textContent = 'Proceso cancelado por el usuario';
                progressBar.classList.remove('bg-blue-500');
                progressBar.classList.add('bg-gray-500');

                // Ocultar botón de cancelar
                document.getElementById('cancel-section').classList.add('hidden');

                // Mostrar botón de cerrar
                const closeButton = document.createElement('button');
                closeButton.className = 'mt-4 w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors';
                closeButton.textContent = 'Cerrar Ventana';
                closeButton.onclick = () => window.close();
                document.querySelector('.bg-white').appendChild(closeButton);

            } catch (error) {
                console.error('Error al cancelar:', error);
                alert('Error al cancelar el proceso');
            }
        }

        // Iniciar al cargar la página
        window.addEventListener('load', () => {
            startDownload();
        });

        // Limpiar y cancelar al cerrar ventana
        window.addEventListener('beforeunload', () => {
            if (pollInterval) {
                clearInterval(pollInterval);

                // Enviar señal de cancelación (beacon para garantizar envío)
                const cancelUrl = `/sale/pdf/cancel/${downloadId}`;
                const blob = new Blob([JSON.stringify({})], { type: 'application/json' });
                navigator.sendBeacon(cancelUrl, blob);
            }
        });
    </script>
</body>
</html>
