# Optimización del Sistema DTE - Archivos Temporales

**Fecha:** 2025-10-30
**Versión:** 1.0.0

---

## 📋 RESUMEN DE CAMBIOS

Se ha optimizado el sistema de manejo de archivos DTE (Documentos Tributarios Electrónicos) para eliminar redundancia y mejorar la eficiencia del almacenamiento.

### **Cambios Principales:**

1. ✅ **JSON**: Eliminado almacenamiento permanente en archivos - Solo se guarda en BD (`history_dtes.dte`)
2. ✅ **PDF**: Ya no se guarda - Se genera on-demand cuando se necesita
3. ✅ **QR**: Eliminado almacenamiento físico - Se genera en memoria como base64
4. ✅ **Archivos Temporales**: Sistema de generación temporal para email y reportes
5. ✅ **Limpieza Automática**: Comando programado diariamente

---

## 🎯 BENEFICIOS

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| **Storage JSON** | ~80MB/10K DTEs | 0MB | **100%** |
| **Storage PDF** | ~70MB/10K DTEs | 0MB | **100%** |
| **Storage QR** | ~80MB/10K DTEs | 0MB | **100%** |
| **Total** | ~230MB/10K DTEs | ~0MB | **~230MB** |
| **Consistencia** | Múltiple | Una fuente | ✅ |
| **Mantenimiento** | Manual | Automático | ✅ |

---

## 📁 ARCHIVOS MODIFICADOS

### **Nuevos Archivos:**
- `app/Services/DteFileService.php` - Servicio centralizado para manejo de archivos DTE
- `app/Console/Commands/CleanTempDteFiles.php` - Comando de limpieza automática
- `database/migrations/2025_10_30_220517_remove_json_url_from_sales_table.php` - Elimina campo `jsonUrl`

### **Archivos Modificados:**
- `app/Http/Controllers/DTEController.php`
  - Método `saveJson()` - Ya no guarda archivo físico
  - Método `printDTETicket()` - Lee desde BD y QR en base64
  - Método `printDTEPdf()` - Lee desde BD y QR en base64

- `app/Http/Controllers/SenEmailDTEController.php`
  - Genera archivos temporales para email
  - Limpia archivos después de enviar

- `app/Http/Controllers/ReportsController.php`
  - Método `downloadJson()` - Genera archivos temporales para ZIP

- `app/Models/Sale.php` - Eliminado campo `jsonUrl` del fillable
- `app/Models/Order.php` - Eliminado campo `jsonUrl` del fillable
- `app/Filament/Exports/SaleExporter.php` - Eliminada columna `jsonUrl`
- `routes/console.php` - Agregado schedule para limpieza automática

---

## 🔧 NUEVO SERVICIO: DteFileService

### **Métodos Principales:**

```php
// Generar JSON temporal desde BD
$jsonPath = $dteFileService->generateTempJsonFile($codigoGeneracion);

// Generar PDF temporal
$pdfPath = $dteFileService->generateTempPdfFile($codigoGeneracion, $isTicket = false);

// Generar QR en base64 (sin archivo)
$qrBase64 = $dteFileService->generateQrBase64($DTE);

// Generar ambos archivos para email
$files = $dteFileService->generateTempFilesForEmail($codigoGeneracion);
// Retorna: ['json' => path, 'pdf' => path]

// Limpiar archivo temporal
$dteFileService->cleanTempFile($filePath);

// Limpiar archivos antiguos (>24h por defecto)
$deletedCount = $dteFileService->cleanOldTempFiles($hoursOld = 24);
```

---

## 🗂️ ESTRUCTURA DE ALMACENAMIENTO

### **Antes:**
```
storage/app/public/
├── DTEs/
│   ├── {codigo}.json  ← 8KB cada uno ❌
│   └── {codigo}.pdf   ← 7KB cada uno ❌
└── QR/
    └── {codigo}.jpg   ← 10KB cada uno ❌
```

### **Después:**
```
storage/app/temp/DTEs/  ← Solo temporales, auto-limpiados
    ├── {codigo}.json  (temporal, se elimina después de usar)
    └── {codigo}.pdf   (temporal, se elimina después de usar)

Base de Datos:
history_dtes.dte → JSON completo (única fuente de verdad) ✅
```

---

## 🤖 LIMPIEZA AUTOMÁTICA

### **Comando Manual:**
```bash
# Limpiar archivos temporales >24h
php artisan dte:clean-temp

# Limpiar archivos temporales >12h
php artisan dte:clean-temp --hours=12
```

### **Ejecución Automática:**
Se ejecuta **diariamente a las 2:00 AM** automáticamente (configurado en `routes/console.php`).

### **Configuración del Cron:**
Para habilitar la ejecución automática, agregar al crontab del servidor:
```bash
* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔄 FLUJO DE GENERACIÓN DTE (ACTUALIZADO)

### **1. Generar DTE:**
```
Usuario → generarDTE() → API Hacienda
    ↓
Guarda en history_dtes.dte (JSON) ✅
Guarda en sales.generationCode ✅
NO guarda archivo JSON ❌
```

### **2. Imprimir PDF/Ticket:**
```
Usuario → printDTEPdf($codigo)
    ↓
Lee history_dtes.dte (BD) ✅
Genera QR en base64 (memoria) ✅
Genera PDF on-demand ✅
Retorna stream del PDF ✅
NO guarda archivos ❌
```

### **3. Enviar Email:**
```
Usuario → SenEmailDTEController
    ↓
DteFileService::generateTempFilesForEmail()
    ├── Genera JSON temporal ⏱️
    └── Genera PDF temporal ⏱️
    ↓
Envía email con adjuntos ✉️
    ↓
Elimina archivos temporales 🗑️
```

### **4. Descargar ZIP Masivo:**
```
Usuario → downloadJson($startDate, $endDate)
    ↓
Por cada venta:
    ├── Genera JSON temporal desde BD ⏱️
    └── Agrega al ZIP
    ↓
Retorna ZIP para descarga
    ↓
Elimina todos los archivos temporales 🗑️
```

---

## ⚠️ IMPORTANTE - MIGRACIÓN

### **Base de Datos:**

**ANTES DE MIGRAR** - Verificar que todos los DTEs tienen registro en `history_dtes`:

```sql
-- Verificar DTEs sin history
SELECT COUNT(*)
FROM sales
WHERE is_dte = 1
  AND generationCode IS NOT NULL
  AND id NOT IN (SELECT sales_invoice_id FROM history_dtes WHERE sales_invoice_id IS NOT NULL);
```

**Si hay DTEs sin registro**, contactar al administrador antes de continuar.

**Para ejecutar la migración:**
```bash
php artisan migrate
```

Esto eliminará la columna `jsonUrl` de la tabla `sales`.

### **Archivos Físicos Existentes:**

Los archivos JSON, PDF y QR existentes en `storage/app/public/DTEs/` y `storage/app/public/QR/` **pueden ser eliminados** después de verificar que:

1. ✅ Todos los DTEs están en `history_dtes.dte`
2. ✅ La migración se ejecutó correctamente
3. ✅ Las pruebas funcionan correctamente

**Comando para limpiar:**
```bash
# Backup primero (recomendado)
tar -czf dte_backup_$(date +%Y%m%d).tar.gz storage/app/public/DTEs/ storage/app/public/QR/

# Luego eliminar
rm -rf storage/app/public/DTEs/*.json
rm -rf storage/app/public/DTEs/*.pdf
rm -rf storage/app/public/QR/*.jpg
```

---

## ✅ PRUEBAS RECOMENDADAS

Después de implementar los cambios, probar:

1. **Generar DTE nuevo**
   - Verificar que se guarda en `history_dtes`
   - Verificar que NO se crea archivo JSON en storage

2. **Imprimir PDF**
   - Abrir PDF de DTE existente
   - Verificar que QR funciona correctamente
   - Verificar formato y datos correctos

3. **Enviar Email**
   - Enviar DTE por email a un cliente
   - Verificar que lleguen ambos adjuntos (JSON y PDF)
   - Verificar que archivos temporales se eliminaron

4. **Descargar ZIP**
   - Descargar ZIP de DTEs de un rango de fechas
   - Verificar que todos los JSON estén en el ZIP
   - Verificar que archivos temporales se eliminaron

5. **Comando de Limpieza**
   ```bash
   php artisan dte:clean-temp
   ```

---

## 🐛 TROUBLESHOOTING

### **Problema: "No se encontró el DTE en la base de datos"**
**Causa:** El DTE no existe en `history_dtes`
**Solución:** Verificar que el DTE se generó correctamente. Revisar tabla `history_dtes`.

### **Problema: "No se pudieron generar los archivos del DTE"**
**Causa:** Error al leer datos de BD o permisos en directorio temp
**Solución:**
```bash
# Verificar permisos
chmod 755 storage/app/temp
mkdir -p storage/app/temp/DTEs
chmod 755 storage/app/temp/DTEs
```

### **Problema: QR no se muestra en PDF**
**Causa:** Error en generación de QR base64
**Solución:** Verificar que la librería `simplesoftwareio/simple-qrcode` esté instalada correctamente.

### **Problema: Email no envía adjuntos**
**Causa:** Archivos temporales no se generaron
**Solución:** Revisar logs en `storage/logs/laravel.log` para ver el error específico.

---

## 📊 MONITOREO

### **Verificar espacio usado por temporales:**
```bash
du -sh storage/app/temp/DTEs/
```

### **Contar archivos temporales:**
```bash
ls -1 storage/app/temp/DTEs/ | wc -l
```

### **Ver últimos archivos temporales:**
```bash
ls -lth storage/app/temp/DTEs/ | head -10
```

### **Verificar ejecución del comando de limpieza:**
```bash
grep "dte:clean-temp" storage/logs/laravel.log
```

---

## 🔗 REFERENCIAS

- **Documentación DTE:** https://www.mh.gob.sv/dte/
- **Laravel Storage:** https://laravel.com/docs/filesystem
- **Laravel Scheduler:** https://laravel.com/docs/scheduling
- **SimpleSoftwareIO QR Code:** https://www.simplesoftware.io/docs/simple-qrcode

---

## 📞 SOPORTE

Para consultas o problemas relacionados con esta optimización, contactar al equipo de desarrollo.

---

**Documento generado automáticamente** - Última actualización: 2025-10-30
