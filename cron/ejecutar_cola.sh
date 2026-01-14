#!/bin/bash
# cron/ejecutar_cola.sh - Wrapper para el procesador de cola

# Configuración
SCRIPT_DIR="/var/www/html/gestionresiduos"
LOG_DIR="$SCRIPT_DIR/logs"
PHP_PATH="/usr/bin/php"  # Cambia si es diferente
SCRIPT_PATH="$SCRIPT_DIR/cron/procesar_cola.php"

# Verificar que existe
if [ ! -f "$SCRIPT_PATH" ]; then
    echo "ERROR: No se encuentra $SCRIPT_PATH"
    exit 1
fi

# Crear log diario
LOG_FILE="$LOG_DIR/cron_$(date +\%Y-\%m-\%d).log"
TIMESTAMP="[$(date +'\%Y-\%m-\%d \%H:\%M:\%S')]"

# Ejecutar
echo "$TIMESTAMP Iniciando procesamiento" >> "$LOG_FILE"
cd "$SCRIPT_DIR"

# Ejecutar PHP
$PHP_PATH "$SCRIPT_PATH" >> "$LOG_FILE" 2>&1

# Resultado
EXIT_CODE=$?
TIMESTAMP="[$(date +'\%Y-\%m-\%d \%H:\%M:\%S')]"

if [ $EXIT_CODE -eq 0 ]; then
    echo "$TIMESTAMP ✅ Procesamiento exitoso" >> "$LOG_FILE"
else
    echo "$TIMESTAMP ❌ Error: Código $EXIT_CODE" >> "$LOG_FILE"
fi

exit $EXIT_CODE