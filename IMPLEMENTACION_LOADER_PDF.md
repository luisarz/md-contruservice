# Implementación de Loader para Descarga de PDFs en Filament

## 🎯 Objetivo
Mostrar un loader con barra de progreso mientras se generan y descargan los PDFs en formato ZIP, evitando que la aplicación se vea "trabada".

## ✅ Lo que ya está implementado

1. **ReportsController actualizado** con:
   - Sistema de progreso usando Laravel Cache
   - Actualización de progreso cada 5 documentos
   - Endpoint `/sale/pdf/progress/{downloadId}` para consultar el progreso
   - Validación de documentos vacíos
   - Manejo de errores con actualización en caché

2. **Rutas configuradas**:
   - `GET /sale/pdf/{starDate}/{endDate}` - Descarga el ZIP
   - `GET /sale/pdf/progress/{downloadId}` - Consulta progreso

## 📋 Cómo Implementar en tu Filament Action

### Opción 1: Modal con JavaScript (Recomendada) ⭐

En tu Resource o Page donde tienes el Action, reemplaza por esto:

```php
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\Blade;

Action::make('downloadPdfZip')
    ->label('Descargar PDFs (ZIP)')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->form([
        DatePicker::make('start_date')
            ->label('Fecha Inicio')
            ->required()
            ->native(false),
        DatePicker::make('end_date')
            ->label('Fecha Fin')
            ->required()
            ->native(false)
            ->afterOrEqual('start_date'),
    ])
    ->modalHeading('Descargar DTEs en PDF')
    ->modalDescription('Selecciona el rango de fechas para descargar los documentos.')
    ->modalSubmitActionLabel('Generar ZIP')
    ->action(function (array $data) {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        // Abrir nueva ventana con el loader
        $this->js(<<<JS
            window.open(
                '/reports/download-progress?start={$startDate}&end={$endDate}',
                'downloadProgress',
                'width=600,height=500,scrollbars=no'
            );
        JS);
    });
```

### Opción 2: Redirigir a página intermedia

Si prefieres una página completa en lugar de ventana popup:

```php
Action::make('downloadPdfZip')
    ->label('Descargar PDFs (ZIP)')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->form([
        DatePicker::make('start_date')
            ->label('Fecha Inicio')
            ->required()
            ->native(false),
        DatePicker::make('end_date')
            ->label('Fecha Fin')
            ->required()
            ->native(false),
    ])
    ->action(function (array $data) {
        return redirect()->route('reports.download.progress', [
            'startDate' => $data['start_date'],
            'endDate' => $data['end_date']
        ]);
    });
```

Luego agrega esta ruta en `routes/web.php`:

```php
Route::get('/reports/download-progress', function () {
    $startDate = request('start') ?? request('startDate');
    $endDate = request('end') ?? request('endDate');

    return view('reports.download-progress', compact('startDate', 'endDate'));
})->name('reports.download.progress')->middleware('auth');
```

### Opción 3: Loader inline con Livewire (Más complejo)

Si quieres el loader dentro del mismo modal de Filament:

```php
Action::make('downloadPdfZip')
    ->label('Descargar PDFs (ZIP)')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->form([
        DatePicker::make('start_date')
            ->label('Fecha Inicio')
            ->required()
            ->native(false),
        DatePicker::make('end_date')
            ->label('Fecha Fin')
            ->required()
            ->native(false),
    ])
    ->modalSubmitActionLabel('Generar ZIP')
    ->action(function (array $data) {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        // Mostrar notificación
        Notification::make()
            ->title('Generando archivo ZIP')
            ->body('El archivo se está generando. Esto puede tardar varios minutos.')
            ->info()
            ->persistent()
            ->send();

        // Descargar en iframe oculto (no bloquea la UI)
        $this->js(<<<JS
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = '/sale/pdf/{$startDate}/{$endDate}';
            document.body.appendChild(iframe);

            // Opcional: polling para actualizar progreso
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        JS);
    });
```

## 🎨 Personalización de la Vista

La vista `resources/views/reports/download-progress.blade.php` ya está creada y lista para usar. Puedes personalizarla:

### Cambiar colores:
- Barra de progreso: `bg-blue-500` (línea 24)
- Botón de descarga: `bg-green-500` (línea 42)
- Mensaje de error: `bg-red-100` (línea 50)

### Cambiar velocidad de polling:
En la línea 126, cambia `2000` (milisegundos):
```javascript
}, 2000); // Consultar cada 2 segundos
```

## 🔍 Cómo Funciona

1. **Usuario hace clic** en "Descargar PDFs"
2. **Se abre modal** o ventana con loader
3. **JavaScript inicia** la descarga en segundo plano (iframe oculto)
4. **Backend procesa** y actualiza progreso en Cache cada 5 documentos
5. **Frontend consulta** progreso cada 2 segundos via AJAX
6. **Barra de progreso** se actualiza en tiempo real
7. **Al terminar**, aparece botón de descarga
8. **Usuario descarga** el archivo ZIP

## 🐛 Solución de Problemas

### El progreso no se actualiza:
- Verifica que el cache driver esté configurado (`.env`):
  ```env
  CACHE_DRIVER=file  # o redis, database
  ```

### Error "Division by zero":
- Ya corregido: valida que haya documentos antes de procesar

### La descarga no inicia:
- Verifica que las rutas estén correctamente registradas:
  ```bash
  php artisan route:list | grep pdf
  ```

### Permisos en Nginx:
- Asegúrate que el directorio `storage/app/temp/DTEs/` tenga permisos de escritura:
  ```bash
  chmod -R 775 storage/app/temp/
  ```

## 📊 Ventajas de esta Implementación

✅ **Sin colas**: No requiere configurar Laravel Queues
✅ **Compatible con Nginx compartido**: Funciona en ambientes multi-tenant
✅ **Progreso real**: Muestra avance documento por documento
✅ **UI no bloqueada**: El usuario puede seguir trabajando
✅ **Manejo de errores**: Muestra mensajes claros si algo falla
✅ **Archivos temporales**: Se limpian automáticamente
✅ **Fácil de implementar**: 3-5 minutos de configuración

## 🚀 Ejemplo Completo en un Resource

```php
<?php

namespace App\Filament\Resources\Sales;

use Filament\Resources\Resource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;

class SaleResource extends Resource
{
    // ... tu código existente ...

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'),
            // ... otras páginas
        ];
    }
}

// En tu ListSales.php (Page)
class ListSales extends ListRecords
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdfZip')
                ->label('Descargar PDFs')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Fecha Inicio')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->native(false),
                    DatePicker::make('end_date')
                        ->label('Fecha Fin')
                        ->required()
                        ->default(now()->endOfMonth())
                        ->native(false),
                ])
                ->modalHeading('Descargar DTEs en PDF')
                ->modalSubmitActionLabel('Generar ZIP')
                ->action(function (array $data) {
                    $start = $data['start_date'];
                    $end = $data['end_date'];

                    // Opción A: Ventana popup
                    $this->js("window.open('/reports/download-progress?start={$start}&end={$end}', 'downloadPDF', 'width=600,height=500')");

                    // Opción B: Descarga directa con notificación
                    // Notification::make()
                    //     ->title('Generando ZIP')
                    //     ->body('La descarga iniciará en breve. Puede tardar varios minutos.')
                    //     ->info()
                    //     ->send();
                    // return redirect("/sale/pdf/{$start}/{$end}");
                }),
        ];
    }
}
```

## 🎓 Notas Adicionales

- **Cache TTL**: Los datos de progreso expiran en 10 minutos (600 segundos)
- **Actualización**: Progreso se actualiza cada 5 documentos para no sobrecargar el cache
- **Rango de progreso**: 0-5% consulta, 5-95% generación, 95-100% finalización
- **Archivos temporales**: Se eliminan automáticamente después de agregarse al ZIP
- **ZIP temporal**: Se elimina después de descargarse (`deleteFileAfterSend(true)`)

## 📝 Próximos Pasos

1. Implementa el Action en tu Resource/Page
2. Prueba con pocos documentos primero (1-2 días)
3. Verifica que la barra de progreso funcione
4. Escala a rangos más grandes
5. Opcional: Personaliza colores y mensajes

---

**¿Necesitas ayuda?** Revisa los logs en `storage/logs/laravel.log`
