# Laravel CI/CD Deployment Setup Guide

Este documento describe la configuración necesaria para el pipeline de CI/CD de Laravel con deployment por SSH.

## Arquitectura del Pipeline

El pipeline está dividido en **4 jobs principales**:

1. **Build & Test** - Instala dependencias y ejecuta tests
2. **Prepare Environment** - Genera el archivo `.env` según la rama
3. **Deploy** - Despliega al servidor via SSH
4. **Notify** - Notifica el resultado del deployment

## Flujo de Trabajo

```
Push a master/develop
  ↓
Build & Test (Composer install + Tests)
  ↓
Prepare Environment (.env dinámico según rama)
  ↓
Deploy (SSH + Script dinámico)
  ↓
Notify (Resultado del deployment)
```

## Ramas y Ambientes

| Rama       | Ambiente    | Servidor                |
|------------|-------------|-------------------------|
| `master`   | production  | /public_html/admin/    |
| `develop`  | develop     | /public_html/admin/    |

## GitHub Secrets Requeridos

📋 **Ver lista completa y detallada en:** [SECRETS_COMPLETE_LIST.md](SECRETS_COMPLETE_LIST.md)

Resumen de secrets mínimos necesarios (19 para production):

### SSH (4 obligatorios)
- `SSH_HOST` - Host del servidor
- `SSH_PORT` - Puerto SSH (ej: 65002, 22)
- `SSH_USER` - Usuario SSH
- `SSH_PRIVATE_KEY` - Clave privada SSH

### Production - App (3 obligatorios)
- `PROD_APP_NAME`
- `PROD_APP_KEY`
- `PROD_APP_URL`

### Production - Database (5 obligatorios)
- `PROD_DB_HOST`
- `PROD_DB_PORT`
- `PROD_DB_DATABASE`
- `PROD_DB_USERNAME`
- `PROD_DB_PASSWORD`

### Production - Email (7 obligatorios si envías emails)
- `PROD_MAIL_MAILER`
- `PROD_MAIL_HOST`
- `PROD_MAIL_PORT`
- `PROD_MAIL_USERNAME`
- `PROD_MAIL_PASSWORD`
- `PROD_MAIL_ENCRYPTION`
- `PROD_MAIL_FROM_ADDRESS`

### Production - AWS (4 opcionales, solo si usas S3)
- `PROD_AWS_ACCESS_KEY_ID`
- `PROD_AWS_SECRET_ACCESS_KEY`
- `PROD_AWS_DEFAULT_REGION`
- `PROD_AWS_BUCKET`

### Develop (solo si tienes servidor de desarrollo)
- Mismos secrets con prefijo `DEV_` en lugar de `PROD_`

## Configuración de SSH

### 1. Generar par de claves SSH (si no las tienes)

```bash
# En tu máquina local
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy
```

### 2. Copiar clave pública al servidor

```bash
# Método 1: Usando ssh-copy-id
ssh-copy-id -i ~/.ssh/github_deploy.pub usuario@tu-servidor.com

# Método 2: Manual (si ssh-copy-id no está disponible)
cat ~/.ssh/github_deploy.pub | ssh usuario@tu-servidor.com "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

### 3. Verificar acceso SSH

```bash
ssh -i ~/.ssh/github_deploy usuario@tu-servidor.com
```

### 4. Agregar clave privada a GitHub Secrets

```bash
# En tu máquina local, copia el contenido de la clave privada
cat ~/.ssh/github_deploy

# Copia TODO el contenido (incluyendo -----BEGIN y -----END)
# y pégalo en GitHub Secrets como SSH_PRIVATE_KEY
```

## Configuración del Servidor

### Requisitos del servidor

- Git instalado
- PHP 8.1 o superior
- Composer instalado
- MySQL/MariaDB
- Acceso SSH habilitado
- Permisos de escritura en `/public_html/admin/`

### Preparación inicial del servidor

```bash
# Conectarse al servidor
ssh usuario@tu-servidor.com

# Navegar al directorio
cd /public_html/admin/

# Clonar el repositorio (solo la primera vez)
git clone https://github.com/TU_USUARIO/TU_REPO.git .

# Configurar git para que acepte el directorio
git config --global --add safe.directory /public_html/admin

# Instalar dependencias iniciales
composer install --no-dev --optimize-autoloader

# Configurar permisos
chmod -R 775 storage bootstrap/cache

# Crear symlink de storage (Voyager)
php artisan storage:link

# Verificar que composer esté en el PATH
which composer
```

## GitHub Environments (Opcional pero Recomendado)

Configurar environments en GitHub para mayor control:

**Settings → Environments → New environment**

### Environment: production
- Protección: Requiere revisión manual antes de deploy
- Branch protection: Solo desde `master`

### Environment: develop
- Protección: Sin restricciones (auto-deploy)
- Branch protection: Solo desde `develop`

## Script de Deployment

El script se genera **dinámicamente** en cada deployment y realiza:

1. Backup del `.env` actual
2. `git pull origin [rama]`
3. `composer install --no-dev --optimize-autoloader`
4. Actualiza `.env` con variables del ambiente
5. Limpia y cachea configuraciones de Laravel
6. Verifica/crea el symlink de storage
7. Ajusta permisos de directorios
8. (Opcional) Ejecuta migraciones
9. Limpia OPCache
10. `composer dump-autoload`

## Migraciones de Base de Datos

Por defecto, las migraciones están **comentadas** en el script de deployment por seguridad.

Para habilitarlas, edita [laravel.yml:292-293](.github/workflows/laravel.yml#L292-L293):

```bash
# Descomentar estas líneas:
echo "[9/10] Running database migrations..."
php artisan migrate --force
```

## Testing del Pipeline

### Primer deployment

1. Haz un push a la rama `master`:
   ```bash
   git add .
   git commit -m "Setup CI/CD pipeline"
   git push origin master
   ```

2. Ve a GitHub → Actions y observa el pipeline ejecutándose

3. Verifica cada job:
   - ✅ Build & Test
   - ✅ Prepare Environment
   - ✅ Deploy
   - ✅ Notify

### Debugging

Si algo falla:

1. Revisa los logs en GitHub Actions
2. Verifica que todos los secrets estén configurados
3. Verifica acceso SSH manualmente:
   ```bash
   ssh usuario@tu-servidor.com "cd /public_html/admin && pwd"
   ```
4. Verifica permisos en el servidor:
   ```bash
   ls -la /public_html/admin/
   ```

## Rollback Manual

Si necesitas hacer rollback:

```bash
# En el servidor
cd /public_html/admin

# Ver commits recientes
git log --oneline -10

# Volver a un commit específico
git reset --hard COMMIT_HASH

# Restaurar .env anterior
cp .env.backup.FECHA .env

# Re-instalar dependencias
composer install --no-dev --optimize-autoloader

# Limpiar caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Mantenimiento

### Limpieza de backups antiguos de .env

```bash
# En el servidor
cd /public_html/admin
find . -name ".env.backup.*" -mtime +30 -delete
```

### Verificar estado del deployment

```bash
# En el servidor
cd /public_html/admin
git status
git log -1
php artisan --version
php artisan route:list
```

## Troubleshooting

### Error: "Permission denied (publickey)"
- Verifica que `SSH_PRIVATE_KEY` esté correctamente configurado en GitHub Secrets
- Verifica que la clave pública esté en `~/.ssh/authorized_keys` del servidor

### Error: "composer: command not found"
- Asegúrate de que Composer esté instalado en el servidor
- Agrega Composer al PATH o usa ruta absoluta: `/usr/local/bin/composer`

### Error: "failed to push some refs"
- El servidor puede tener cambios locales. Haz `git reset --hard` en el servidor

### Error: Storage symlink no se crea
- Verifica permisos: `chmod -R 775 storage public`
- Elimina manualmente el symlink y déjalo crear: `rm public/storage`

### Voyager no carga imágenes
- Verifica que existe: `ls -la public/storage`
- Debe ser un symlink: `public/storage -> ../storage/app/public`
- Re-crear: `php artisan storage:link`

## Seguridad

- ✅ Las claves SSH se limpian después de cada deployment
- ✅ El archivo `.env` se sube de forma segura via SCP
- ✅ El script de deployment se elimina automáticamente del servidor
- ✅ Todas las credenciales están en GitHub Secrets (encriptados)
- ✅ El `.env` del servidor hace backup automático antes de actualizarse

## Próximos Pasos

1. Configurar notificaciones por Slack/Discord/Email
2. Implementar health checks post-deployment
3. Configurar servidor de develop
4. Implementar tests de integración
5. Agregar deployment de assets (npm build)

---

**Última actualización:** 2025-12-04
