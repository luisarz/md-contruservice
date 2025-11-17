# Guía de Deployment - ERP Clementina

## 🚀 Deployment Automático (Recomendado)

### Primera vez - Dar permisos de ejecución:
```bash
chmod +x deploy.sh
```

### Ejecutar deployment:
```bash
./deploy.sh
```

El script automáticamente:
- ✅ Activa modo de mantenimiento
- ✅ Hace pull del código desde git
- ✅ Instala dependencias (Composer + NPM)
- ✅ Compila assets de producción
- ✅ Regenera enlace simbólico de storage
- ✅ Ejecuta migraciones pendientes
- ✅ Limpia y regenera todas las caches
- ✅ Desactiva modo de mantenimiento

---

## 🔧 Deployment Manual (Paso a Paso)

Si prefieres hacerlo manualmente o necesitas más control:

### 1. Activar Modo de Mantenimiento
```bash
php artisan down --refresh=15 --retry=60
```

### 2. Actualizar Código
```bash
git pull origin main
```

### 3. Instalar Dependencias
```bash
composer install --no-dev --optimize-autoloader
npm ci --production=false
npm run build
```

### 4. Regenerar Storage Link
```bash
# Eliminar enlace anterior
rm public/storage

# Crear nuevo enlace
php artisan storage:link
```

### 5. Base de Datos
```bash
# Ejecutar migraciones
php artisan migrate --force

# (Opcional) Ejecutar seeders si es necesario
php artisan db:seed --force
```

### 6. Limpiar Caches
```bash
php artisan optimize:clear
```

### 7. Regenerar Caches de Producción
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
composer dump-autoload --optimize
```

### 8. Desactivar Modo de Mantenimiento
```bash
php artisan up
```

---

## 🔍 Verificación Post-Deployment

Después del deployment, verifica:

1. **Aplicación funcionando**
   ```bash
   curl -I https://tu-dominio.com
   ```

2. **Storage link correcto**
   ```bash
   ls -la public/storage
   # Debe mostrar: public/storage -> ../storage/app/public
   ```

3. **Archivos subidos accesibles**
   - Verifica que los logos de empresas se vean
   - Verifica que los archivos adjuntos funcionen

4. **Logs del sistema**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## ⚠️ Troubleshooting

### Storage link no funciona
```bash
# En Linux/Mac
rm public/storage
php artisan storage:link

# En Windows
rmdir public\storage
php artisan storage:link
```

### Errores 500 después de deployment
```bash
# Limpiar TODAS las caches
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Regenerar
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Archivos de storage no se ven
```bash
# Verificar permisos (Linux/Mac)
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### Errores de Composer
```bash
composer clear-cache
composer install --no-dev --optimize-autoloader
```

---

## 📋 Checklist Pre-Deployment

Antes de hacer deployment a producción:

- [ ] Código testeado en local
- [ ] Migraciones probadas
- [ ] .env de producción configurado correctamente
- [ ] Backup de base de datos realizado
- [ ] Usuario informado del mantenimiento (si aplica)
- [ ] Revisión de logs de errores previos

---

## 🔐 Variables de Entorno Importantes

Asegúrate que el `.env` de producción tenga:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=tu-servidor-db
DB_DATABASE=tu_base_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password_seguro

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# DTE Hacienda (Producción)
DTE_AMBIENTE=00  # 00 = Producción, 01 = Pruebas
DTE_AMBIENTE_QR=00
```

---

## 🕒 Rollback (Volver Atrás)

Si algo sale mal:

```bash
# 1. Activar modo de mantenimiento
php artisan down

# 2. Volver al commit anterior
git reset --hard HEAD~1

# 3. Restaurar base de datos (tener backup!)
mysql -u usuario -p base_datos < backup.sql

# 4. Limpiar caches
php artisan optimize:clear

# 5. Volver a estar en línea
php artisan up
```

---

## 📞 Contacto

Si necesitas ayuda con el deployment:
- Revisar logs: `storage/logs/laravel.log`
- Documentación Laravel: https://laravel.com/docs
- Documentación Filament: https://filamentphp.com/docs
