#!/bin/bash

# ==============================================================================
# Script de Deployment - Negocios y Servicios Clementina
# ==============================================================================
# Automatiza el proceso de deployment a producción
# Uso: ./deploy.sh
# ==============================================================================

set -e  # Detener el script si hay algún error

echo "=========================================="
echo "  DEPLOYMENT - ERP Clementina"
echo "=========================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ==============================================================================
# 1. VERIFICAR MODO DE MANTENIMIENTO
# ==============================================================================
info "Activando modo de mantenimiento..."
php artisan down --refresh=15 --retry=60 || warning "No se pudo activar modo de mantenimiento"

# ==============================================================================
# 2. PULL DEL CÓDIGO
# ==============================================================================
info "Obteniendo últimos cambios del repositorio..."
git pull origin main

# ==============================================================================
# 3. INSTALAR/ACTUALIZAR DEPENDENCIAS
# ==============================================================================
info "Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

info "Instalando dependencias de NPM..."
npm ci --production=false

# ==============================================================================
# 4. BUILD DE ASSETS FRONTEND
# ==============================================================================
info "Compilando assets de producción..."
npm run build

# ==============================================================================
# 5. REGENERAR ENLACE SIMBÓLICO DE STORAGE
# ==============================================================================
info "Regenerando enlace simbólico de storage..."
# Eliminar enlace anterior si existe
if [ -L public/storage ]; then
    rm public/storage
    info "Enlace simbólico anterior eliminado"
fi
php artisan storage:link

# ==============================================================================
# 6. EJECUTAR MIGRACIONES
# ==============================================================================
info "Ejecutando migraciones de base de datos..."
php artisan migrate --force

# ==============================================================================
# 7. LIMPIAR CACHES
# ==============================================================================
info "Limpiando caches..."
php artisan optimize:clear

# ==============================================================================
# 8. REGENERAR CACHES DE PRODUCCIÓN
# ==============================================================================
info "Regenerando caches de producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# ==============================================================================
# 9. OPTIMIZAR AUTOLOADER
# ==============================================================================
info "Optimizando autoloader..."
composer dump-autoload --optimize

# ==============================================================================
# 10. LIMPIAR ARCHIVOS TEMPORALES VIEJOS (Opcional)
# ==============================================================================
info "Limpiando archivos temporales..."
php artisan schedule:run || warning "No se pudo ejecutar el scheduler"

# ==============================================================================
# 11. DESACTIVAR MODO DE MANTENIMIENTO
# ==============================================================================
info "Desactivando modo de mantenimiento..."
php artisan up

# ==============================================================================
# DEPLOYMENT COMPLETADO
# ==============================================================================
echo ""
echo "=========================================="
echo -e "${GREEN}✓ DEPLOYMENT COMPLETADO EXITOSAMENTE${NC}"
echo "=========================================="
echo ""
echo "Tareas ejecutadas:"
echo "  ✓ Código actualizado desde git"
echo "  ✓ Dependencias instaladas"
echo "  ✓ Assets compilados"
echo "  ✓ Storage link regenerado"
echo "  ✓ Migraciones ejecutadas"
echo "  ✓ Caches regeneradas"
echo "  ✓ Aplicación en línea"
echo ""
echo "Hora: $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
