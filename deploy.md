# Guía de Despliegue a Producción

Este documento describe los pasos necesarios para desplegar cambios del
servidor de pruebas al servidor de producción.

## Requisitos previos

- Servidor Ubuntu 22.04 LTS
- Apache 2.4, PHP 8.1, MySQL 8.0
- Composer instalado
- Acceso SSH al servidor
- Llave .pem para conectarse por SSH

## Estructura del proyecto

- Ruta en producción: `/var/www/gestionResiduos`
- Ruta en pruebas: `/var/www/html/gestionResiduos`
- Usuario del servidor web: `www-data`
- Usuario de despliegue en producción: `gestionResiduos`
- Usuario de despliegue en pruebas: `sistemas02`
- Repositorio: `git@github.com:AndresCuastumal/gestionresiduos.git`

## Pasos de despliegue

### 1. Ingresar al servidor y ubicarse en el proyecto

```bash
cd /var/www/gestionResiduos
```

### 2. Verificar el estado del repositorio

```bash
git status
```

Debe estar limpio (nada que commitear). Si hay cambios locales sin
commitear, revisarlos antes de continuar.

### 3. Traer los últimos cambios

```bash
git pull origin main
```

### 4. Instalar dependencias de Composer (si aplica)

Si el `composer.json` o `composer.lock` cambiaron, ejecutar:

```bash
composer install
```

### 5. Ajustar permisos de carpetas de escritura

Después de cualquier cambio, ejecutar:

```bash
sudo chown -R www-data:www-data /var/www/gestionResiduos/vendor
sudo chown -R www-data:www-data /var/www/gestionResiduos/procesos/admin/certificados
sudo chown -R www-data:www-data /var/www/gestionResiduos/procesos/generador/soportes_generador
sudo chown -R www-data:www-data /var/www/gestionResiduos/logs
sudo chown -R www-data:www-data /var/www/gestionResiduos/vendor/mpdf/mpdf/tmp

sudo chmod -R 775 /var/www/gestionResiduos/procesos/admin/certificados
sudo chmod -R 775 /var/www/gestionResiduos/procesos/generador/soportes_generador
sudo chmod -R 775 /var/www/gestionResiduos/logs
sudo chmod -R 775 /var/www/gestionResiduos/vendor/mpdf/mpdf/tmp
```

### 6. Aplicar cambios en la base de datos (si aplica)

Si el despliegue incluye cambios en la estructura de la base de datos,
aplicarlos manualmente:

```bash
mysql -u gestion_user -p bdresiduos < ruta/al/script.sql
```

### 7. Configurar Git para evitar error de "dubious ownership"

Si el dueño de las carpetas cambió a `www-data`, Git puede dar error.
Solución (una sola vez por servidor):

```bash
git config --global --add safe.directory /var/www/gestionResiduos
```

### 8. Verificar la configuración de Apache

```bash
sudo apache2ctl configtest
```

Debe responder `Syntax OK`.

### 9. Recargar Apache

```bash
sudo systemctl reload apache2
```

### 10. Verificar que la aplicación funciona

- Abrir la app en el navegador: http://44.205.21.161
- Probar el flujo afectado por el cambio.
- Revisar los logs:

```bash
sudo tail -50 /var/log/apache2/gestionResiduos_error.log
```

## Estructura de permisos

| Carpeta | Dueño | Permisos | Uso |
|---|---|---|---|
| `/var/www/gestionResiduos` | `gestionResiduos:gestionResiduos` | 775 | Raíz del proyecto |
| `/var/www/gestionResiduos/vendor` | `www-data:www-data` | 775 | Dependencias de Composer |
| `/var/www/gestionResiduos/procesos/admin/certificados` | `www-data:www-data` | 775 | PDFs de certificados |
| `/var/www/gestionResiduos/procesos/generador/soportes_generador` | `www-data:www-data` | 775 | Archivos subidos por generadores |
| `/var/www/gestionResiduos/logs` | `www-data:www-data` | 775 | Logs de la app |
| `/var/www/gestionResiduos/vendor/mpdf/mpdf/tmp` | `www-data:www-data` | 775 | Temporales de mPDF |

## Solución de problemas comunes

### Error: "Temporary files directory is not writable"

Ocurre cuando mPDF no puede escribir en su carpeta temporal. Solución:

```bash
sudo chown -R www-data:www-data /var/www/gestionResiduos/vendor/mpdf/mpdf/tmp
sudo chmod -R 775 /var/www/gestionResiduos/vendor/mpdf/mpdf/tmp
```

### Error: "Class PHPMailer\PHPMailer\PHPMailer not found"

Ocurre cuando la carpeta `vendor/phpmailer/phpmailer/` está vacía o incompleta.
Solución:

```bash
cd /var/www/gestionResiduos
composer install
sudo chown -R www-data:www-data vendor
```

Si `composer install` dice "Nothing to install", forzar la reinstalación:

```bash
rm -rf vendor
composer install
sudo chown -R www-data:www-data vendor
```

### Error: "Permission denied" al escribir PDFs

Ocurre cuando la carpeta de certificados no pertenece a `www-data`. Solución:

```bash
sudo chown -R www-data:www-data /var/www/gestionResiduos/procesos/admin/certificados
sudo chmod -R 775 /var/www/gestionResiduos/procesos/admin/certificados
```

### Error: "dubious ownership" al usar Git

Ocurre cuando el dueño de la carpeta no coincide con el usuario que ejecuta
Git. Solución:

```bash
git config --global --add safe.directory /var/www/gestionResiduos
```

### Error: "Daily user sending limit exceeded" en Gmail

No es un error del código. Es el límite de Gmail:
- 500 correos/día en cuentas gratuitas.
- 2000 correos/día en Google Workspace.

Opciones:
1. Esperar 24 horas a que se resetee.
2. Usar Google Workspace.
3. Configurar un servicio de correo transaccional
   (SendGrid, Mailgun, Amazon SES).

## Flujo de trabajo recomendado

1. Desarrollar y probar cambios en el **servidor de pruebas** (`pyc-sis-07`).
2. Verificar que todo funciona en pruebas.
3. Hacer commit y push desde pruebas:
   ```bash
   git add .
   git commit -m "Descripción del cambio"
   git push origin main
   ```
4. En **producción** (AWS), ejecutar el script de despliegue:
   ```bash
   cd /var/www/gestionResiduos
   bash deploy.sh
   ```
5. Verificar que la app funciona en producción.
6. Si algo falla, revisar este documento en la sección "Solución de problemas comunes".

## Comandos útiles

### Ver los últimos commits
```bash
git log --oneline -5
```

### Ver el estado del repositorio
```bash
git status
```

### Ver los logs de Apache en tiempo real
```bash
sudo tail -f /var/log/apache2/gestionResiduos_error.log
```

### Ver los últimos PDFs generados
```bash
ls -lt /var/www/gestionResiduos/procesos/admin/certificados/ | head -5
```

### Verificar que la clase PHPMailer se carga
```bash
cd /var/www/gestionResiduos
php -r "require 'vendor/autoload.php'; var_dump(class_exists('PHPMailer\\PHPMailer\\PHPMailer'));"
```

### Verificar que la clase mPDF se carga
```bash
cd /var/www/gestionResiduos
php -r "require 'vendor/autoload.php'; var_dump(class_exists('Mpdf\\Mpdf'));"
```

### Verificar permisos de una carpeta
```bash
ls -la /var/www/gestionResiduos/procesos/admin/certificados/ | head -3
```

### Verificar los últimos registros finalizados
```bash
mysql -u gestion_user -p bdresiduos -e "SELECT generador_id, anio, estado_finalizado, fecha_finalizacion FROM revisiones_anuales WHERE estado_finalizado = 1 ORDER BY fecha_finalizacion DESC LIMIT 5;"
```
