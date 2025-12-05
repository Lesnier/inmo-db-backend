# Configuración de .htaccess para inmodb.net

## Estructura del Servidor

```
public_html/
├── .htaccess              ← Archivo principal (ruteo de dominios)
├── admin/                 ← Backend Laravel
│   ├── app/
│   ├── config/
│   ├── public/           ← WEBROOT del backend
│   │   ├── .htaccess     ← Ya existe (Laravel estándar)
│   │   └── index.php
│   └── ...
└── client/                ← Frontend (React/Vue/etc)
    ├── index.html
    ├── assets/
    └── ...
```

## Dominios y Rutas

| Dominio | Apunta a | Descripción |
|---------|----------|-------------|
| `inmodb.net` | `/public_html/client/` | Frontend principal |
| `www.inmodb.net` | `/public_html/client/` | Frontend (con www) |
| `admin.inmodb.net` | `/public_html/admin/public/` | Backend Laravel API |

## Instalación

### 1. Archivo `.htaccess` principal

Sube el contenido de `.htaccess-public_html` a **`public_html/.htaccess`**:

```bash
# En el servidor
cd /home/u403607455/domains/inmodb.net/public_html/

# Crear/editar el archivo .htaccess
nano .htaccess
```

Pega este contenido:

```apache
# ============================================
# HTACCESS PRINCIPAL - public_html/
# Configuración para inmodb.net
# ============================================

# Activar RewriteEngine
RewriteEngine On

# ============================================
# FORCE HTTPS (excepto para Let's Encrypt)
# ============================================
RewriteCond %{HTTPS} off
RewriteCond %{HTTP:CDN-LOOP} !cloudflare
RewriteCond %{REQUEST_URI} !^/.well-known/acme-challenge/
RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L,QSA]

# ============================================
# SUBDOMINIO: admin.inmodb.net → /admin/public/
# ============================================
RewriteCond %{HTTP_HOST} ^admin\.inmodb\.net$ [NC]
RewriteCond %{REQUEST_URI} !^/admin/public/ [NC]
RewriteRule ^(.*)$ /admin/public/$1 [L]

# ============================================
# DOMINIO PRINCIPAL: inmodb.net → /client/
# ============================================
RewriteCond %{HTTP_HOST} ^(www\.)?inmodb\.net$ [NC]
RewriteCond %{REQUEST_URI} !^/client/ [NC]
RewriteCond %{REQUEST_URI} !^/admin/ [NC]
RewriteRule ^(.*)$ /client/$1 [L]

# ============================================
# OPCIONAL: Redirigir www a no-www
# ============================================
# Descomenta si quieres forzar sin www
# RewriteCond %{HTTP_HOST} ^www\.inmodb\.net$ [NC]
# RewriteRule ^(.*)$ https://inmodb.net/$1 [R=301,L]
```

### 2. Archivo `.htaccess` del backend (Laravel)

El archivo `public_html/admin/public/.htaccess` ya existe y es correcto (estándar de Laravel). No necesitas modificarlo.

### 3. Configurar el subdominio en tu hosting

**En el panel de control de tu hosting (cPanel, Plesk, etc.):**

1. Ve a **Subdominios** o **Subdomains**
2. Crea el subdominio: `admin.inmodb.net`
3. **Importante:** Configura la **Document Root** como: `/public_html/admin/public`
   - ⚠️ NO uses `/public_html/admin`
   - ⚠️ Debe apuntar a `/public_html/admin/public`

**Alternativa:** Si tu hosting no permite configurar el Document Root del subdominio, el `.htaccess` principal ya maneja la redirección correctamente.

## Verificación

### Probar que funciona correctamente:

1. **Frontend:**
   ```
   https://inmodb.net
   → Debe cargar desde /public_html/client/
   ```

2. **Backend API:**
   ```
   https://admin.inmodb.net
   → Debe cargar desde /public_html/admin/public/
   ```

3. **Endpoint de prueba (ejemplo):**
   ```
   https://admin.inmodb.net/api/test
   → Debe responder con JSON desde Laravel
   ```

### Debug

Si algo no funciona, verifica:

1. **Módulo mod_rewrite activado:**
   ```bash
   # En el servidor
   php -m | grep rewrite
   ```

2. **Permisos del .htaccess:**
   ```bash
   chmod 644 public_html/.htaccess
   chmod 644 public_html/admin/public/.htaccess
   ```

3. **Logs del servidor:**
   ```bash
   tail -f /var/log/apache2/error.log
   # o
   tail -f ~/domains/inmodb.net/logs/error.log
   ```

## Notas Importantes

1. **SSL/HTTPS:**
   - El `.htaccess` fuerza HTTPS en todas las peticiones
   - Asegúrate de tener certificado SSL instalado para `inmodb.net` y `admin.inmodb.net`
   - Let's Encrypt es compatible (la configuración excluye `/.well-known/acme-challenge/`)

2. **Cloudflare:**
   - Si usas Cloudflare, la regla `CDN-LOOP` previene loops de redirección

3. **WWW vs NO-WWW:**
   - Actualmente ambos funcionan (`inmodb.net` y `www.inmodb.net`)
   - Si quieres forzar uno u otro, descomenta la sección opcional

4. **CORS:**
   - Si el frontend (`inmodb.net`) llama a la API (`admin.inmodb.net`), asegúrate de configurar CORS en Laravel
   - Edita `config/cors.php` en tu backend

## Troubleshooting

### Error: "Internal Server Error 500"
- Verifica que mod_rewrite esté habilitado
- Revisa los logs de Apache
- Verifica sintaxis del .htaccess: `apache2ctl configtest`

### Error: "404 Not Found" en rutas del backend
- Verifica que `/public_html/admin/public/.htaccess` existe
- Verifica que el subdominio apunta a la carpeta `public`

### Frontend funciona pero backend da error
- Verifica la configuración del subdominio en cPanel
- Prueba acceder directamente: `https://admin.inmodb.net/index.php`
- Si funciona con `/index.php`, el problema es el `.htaccess` de Laravel

---

**Última actualización:** 2025-12-05
