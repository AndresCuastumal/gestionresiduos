#!/bin/bash
#
# deploy.sh - Script de despliegue para gestionResiduos
#
# Uso: bash deploy.sh
#
# Este script automatiza los pasos comunes de despliegue:
# 1. Verifica el estado de Git
# 2. Hace git pull desde GitHub
# 3. Instala dependencias de Composer
# 4. Ajusta permisos de carpetas de escritura
# 5. Verifica y recarga Apache
#

set -e  # Detener el script si algún comando falla

# Configuración
PROJECT_DIR="/var/www/gestionResiduos"
WEB_USER="www-data"
GIT_BRANCH="main"

# Colores para mensajes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # Sin color

echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Despliegue de gestionResiduos                 ${NC}"
echo -e "${BLUE}  $(date '+%Y-%m-%d %H:%M:%S')                  ${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# 1. Ir al directorio del proyecto
cd "$PROJECT_DIR"
echo -e "${GREEN}[1/7]${NC} Ubicado en $PROJECT_DIR"

# 2. Verificar estado de Git
echo ""
echo -e "${GREEN}[2/7]${NC} Verificando estado de Git..."
git status --short
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠️  Hay cambios locales sin commitear.${NC}"
    echo -e "${YELLOW}   Revisa esos cambios antes de continuar.${NC}"
    read -p "¿Deseas continuar con el despliegue? (s/N): " continuar
    if [[ ! "$continuar" =~ ^[sS]$ ]]; then
        echo -e "${RED}Despliegue cancelado.${NC}"
        exit 1
    fi
fi

# 3. Hacer git pull
echo ""
echo -e "${GREEN}[3/7]${NC} Trayendo cambios desde GitHub (rama $GIT_BRANCH)..."
git pull origin "$GIT_BRANCH"

# 4. Instalar dependencias de Composer
echo ""
echo -e "${GREEN}[4/7]${NC} Instalando dependencias de Composer..."
composer install --no-interaction --optimize-autoloader

# 5. Ajustar permisos de carpetas de escritura
echo ""
echo -e "${GREEN}[5/7]${NC} Ajustando permisos de carpetas de escritura..."

# Carpetas que Apache (www-data) necesita poder escribir
CARPETAS_ESCRITURA=(
    "$PROJECT_DIR/vendor"
    "$PROJECT_DIR/vendor/mpdf/mpdf/tmp"
    "$PROJECT_DIR/procesos/admin/certificados"
    "$PROJECT_DIR/procesos/generador/soportes_generador"
    "$PROJECT_DIR/logs"
)

for carpeta in "${CARPETAS_ESCRITURA[@]}"; do
    if [ -d "$carpeta" ]; then
        sudo chown -R "$WEB_USER:$WEB_USER" "$carpeta"
        sudo chmod -R 775 "$carpeta"
        echo "  ✅ $carpeta"
    else
        echo -e "  ${YELLOW}⚠️  No existe: $carpeta${NC}"
    fi
done

# 6. Verificar configuración de Apache
echo ""
echo -e "${GREEN}[6/7]${NC} Verificando configuración de Apache..."
sudo apache2ctl configtest

# 7. Recargar Apache
echo ""
echo -e "${GREEN}[7/7]${NC} Recargando Apache..."
sudo systemctl reload apache2

# Mensaje final
echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${GREEN}  ✅ Despliegue completado exitosamente         ${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""
echo "Recomendaciones:"
echo "  1. Probar la aplicación en el navegador:"
echo "     http://44.205.21.161"
echo "  2. Revisar los logs en tiempo real:"
echo "     sudo tail -f /var/log/apache2/gestionResiduos_error.log"
echo "  3. Verificar los últimos PDFs generados:"
echo "     ls -lt $PROJECT_DIR/procesos/admin/certificados/ | head -5"
echo ""
