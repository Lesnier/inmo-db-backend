# Lista Completa de GitHub Secrets

## Configuración en GitHub
**Ruta:** `Settings → Secrets and variables → Actions → New repository secret`

---

## 🔐 SECRETS DE SSH (4 obligatorios)

```
SSH_HOST
  Descripción: Hostname o IP del servidor webhosting
  Ejemplo: ftp.tudominio.com
          185.123.45.67
  Dónde obtenerlo: Panel de control de tu webhosting (cPanel, Plesk, etc.)

SSH_PORT
  Descripción: Puerto SSH del servidor (si es diferente al 22 por defecto)
  Ejemplo: 65002
          2222
          22 (puerto estándar)
  Dónde obtenerlo: Panel de control de tu webhosting → SSH Access
  IMPORTANTE: Muchos webhostings usan puertos no estándar por seguridad

SSH_USER
  Descripción: Usuario SSH/FTP de tu webhosting
  Ejemplo: usuario_cpanel
          root
          tu_usuario
  Dónde obtenerlo: Panel de control de tu webhosting

SSH_PRIVATE_KEY
  Descripción: Clave privada SSH para autenticación
  Formato: -----BEGIN OPENSSH PRIVATE KEY-----
          ...contenido de la clave...
          -----END OPENSSH PRIVATE KEY-----
  Ver guía de generación abajo ⬇️
```

---

## 🚀 SECRETS DE PRODUCTION (Prefijo: PROD_)

### Aplicación (5 obligatorios)

```
PROD_APP_NAME
  Descripción: Nombre de tu aplicación
  Ejemplo: "Plusvalia Admin"
          "Mi Sistema Inmobiliario"

PROD_APP_KEY
  Descripción: Clave de encriptación de Laravel
  Ejemplo: base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
  Cómo generarlo: php artisan key:generate --show
  IMPORTANTE: Debe empezar con "base64:"

PROD_APP_URL
  Descripción: URL principal de tu aplicación en producción
  Ejemplo: https://midominio.com
          https://admin.midominio.com
  IMPORTANTE: Incluir https://

PROD_APP_DEBUG
  Descripción: Modo debug (SIEMPRE false en producción)
  Valor: false

PROD_APP_ENV
  Descripción: Ambiente de ejecución
  Valor: production
```

### Base de Datos (5 obligatorios)

```
PROD_DB_HOST
  Descripción: Host del servidor MySQL
  Ejemplo: localhost
          127.0.0.1
          mysql.tudominio.com
  Dónde obtenerlo: Panel de webhosting → MySQL Databases

PROD_DB_PORT
  Descripción: Puerto de MySQL
  Valor por defecto: 3306

PROD_DB_DATABASE
  Descripción: Nombre de la base de datos
  Ejemplo: usuario_laravel
          plusvalia_db
  Dónde obtenerlo: Panel de webhosting → MySQL Databases

PROD_DB_USERNAME
  Descripción: Usuario de la base de datos
  Ejemplo: usuario_db
          root
  Dónde obtenerlo: Panel de webhosting → MySQL Databases

PROD_DB_PASSWORD
  Descripción: Contraseña de la base de datos
  Ejemplo: TuContraseñaSegura123!
  Dónde obtenerlo: Panel de webhosting → MySQL Databases
```

### Email / SMTP (6 obligatorios para envío de emails)

```
PROD_MAIL_MAILER
  Descripción: Driver de correo
  Ejemplo: smtp
          sendmail
          mailgun
          ses

PROD_MAIL_HOST
  Descripción: Servidor SMTP
  Ejemplos comunes:
    Gmail: smtp.gmail.com
    Outlook: smtp.office365.com
    SendGrid: smtp.sendgrid.net
    Mailgun: smtp.mailgun.org
    cPanel: mail.tudominio.com
  Dónde obtenerlo: Configuración de email de tu webhosting

PROD_MAIL_PORT
  Descripción: Puerto SMTP
  Valores comunes:
    25  - Sin encriptación (no recomendado)
    587 - TLS (recomendado)
    465 - SSL

PROD_MAIL_USERNAME
  Descripción: Usuario del correo SMTP
  Ejemplo: noreply@midominio.com
          tu-email@gmail.com
  Dónde obtenerlo: Configuración de email de tu webhosting

PROD_MAIL_PASSWORD
  Descripción: Contraseña del correo SMTP
  Ejemplo: tu-contraseña-de-email
  IMPORTANTE para Gmail: Usar "Contraseña de aplicación" no la contraseña normal
  Dónde obtenerlo:
    Gmail: https://myaccount.google.com/apppasswords
    Otros: Panel de webhosting → Email Accounts

PROD_MAIL_ENCRYPTION
  Descripción: Tipo de encriptación
  Valores: tls
          ssl
          null (sin encriptación, no recomendado)
  Recomendado: tls

PROD_MAIL_FROM_ADDRESS
  Descripción: Dirección de correo del remitente
  Ejemplo: noreply@midominio.com
          admin@midominio.com

PROD_MAIL_FROM_NAME
  Descripción: Nombre del remitente
  Ejemplo: "Plusvalia Admin"
          "Sistema Inmobiliario"
  Puede usar: "${PROD_APP_NAME}" (usa el nombre de la app)
```

### AWS S3 (4 opcionales - solo si usas almacenamiento S3)

```
PROD_AWS_ACCESS_KEY_ID
  Descripción: Access Key de AWS
  Ejemplo: AKIAIOSFODNN7EXAMPLE
  Dónde obtenerlo: AWS Console → IAM → Users → Security credentials
  DEJAR VACÍO si no usas S3

PROD_AWS_SECRET_ACCESS_KEY
  Descripción: Secret Key de AWS
  Ejemplo: wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
  Dónde obtenerlo: AWS Console → IAM → Users → Security credentials
  DEJAR VACÍO si no usas S3

PROD_AWS_DEFAULT_REGION
  Descripción: Región de AWS
  Ejemplo: us-east-1
          us-west-2
          eu-west-1
  DEJAR VACÍO si no usas S3

PROD_AWS_BUCKET
  Descripción: Nombre del bucket S3
  Ejemplo: mi-bucket-produccion
  DEJAR VACÍO si no usas S3
```

---

## 🧪 SECRETS DE DEVELOP (Prefijo: DEV_)

**NOTA:** Solo necesarios si creas un servidor de desarrollo separado.
Si solo trabajas con producción, IGNORA esta sección.

### Aplicación

```
DEV_APP_NAME
DEV_APP_KEY
DEV_APP_URL
DEV_APP_DEBUG (valor: true)
DEV_APP_ENV (valor: development)
```

### Base de Datos

```
DEV_DB_HOST
DEV_DB_PORT
DEV_DB_DATABASE
DEV_DB_USERNAME
DEV_DB_PASSWORD
```

### Email

```
DEV_MAIL_MAILER
DEV_MAIL_HOST
DEV_MAIL_PORT
DEV_MAIL_USERNAME
DEV_MAIL_PASSWORD
DEV_MAIL_ENCRYPTION
DEV_MAIL_FROM_ADDRESS
DEV_MAIL_FROM_NAME
```

### AWS (opcional)

```
DEV_AWS_ACCESS_KEY_ID
DEV_AWS_SECRET_ACCESS_KEY
DEV_AWS_DEFAULT_REGION
DEV_AWS_BUCKET
```

---

## 📋 RESUMEN - Secrets Mínimos Requeridos para Empezar

### Para SOLO Production (19 secrets mínimos):

**SSH (4):**
- SSH_HOST
- SSH_PORT
- SSH_USER
- SSH_PRIVATE_KEY

**App (3):**
- PROD_APP_NAME
- PROD_APP_KEY
- PROD_APP_URL

**Database (5):**
- PROD_DB_HOST
- PROD_DB_PORT
- PROD_DB_DATABASE
- PROD_DB_USERNAME
- PROD_DB_PASSWORD

**Email (7):**
- PROD_MAIL_MAILER
- PROD_MAIL_HOST
- PROD_MAIL_PORT
- PROD_MAIL_USERNAME
- PROD_MAIL_PASSWORD
- PROD_MAIL_ENCRYPTION
- PROD_MAIL_FROM_ADDRESS

**Opcionales:**
- PROD_MAIL_FROM_NAME (puede usar "${PROD_APP_NAME}")
- PROD_AWS_* (solo si usas S3)

---

## 🔑 Configuración de SSH con Contraseña de Webhosting

Ya tienes usuario, contraseña, host y puerto del webhosting, pero **GitHub Actions no soporta autenticación por contraseña**, solo por clave SSH.

### Opciones:

### ✅ OPCIÓN 1: Generar Clave SSH desde tu PC (RECOMENDADA)

```bash
# 1. Generar par de claves en tu PC local
ssh-keygen -t ed25519 -C "github-deploy" -f github_deploy

# Esto crea 2 archivos:
# - github_deploy (privada) ← Este va a GitHub Secrets
# - github_deploy.pub (pública) ← Este va al servidor

# 2. Ver contenido de la clave PRIVADA
cat github_deploy

# 3. Copiar TODO el contenido (incluyendo BEGIN y END) y guardarlo en:
#    GitHub → Settings → Secrets → SSH_PRIVATE_KEY

# 4. Subir la clave PÚBLICA al servidor
#    Opción A: Via FTP/Filemanager del webhosting
#    - Sube github_deploy.pub al servidor
#    - Contenido debe ir a: ~/.ssh/authorized_keys

#    Opción B: Via SSH (si tienes acceso)
ssh-copy-id -i github_deploy.pub usuario@tu-servidor.com

#    Opción C: Manual via cPanel/Webhosting panel
#    - cPanel → SSH Access → Manage SSH Keys
#    - Import Key → Pega el contenido de github_deploy.pub
```

### ✅ OPCIÓN 2: Usar cPanel para generar claves

```
1. Accede a cPanel de tu webhosting
2. Busca "SSH Access" o "Terminal"
3. Click en "Manage SSH Keys"
4. Click en "Generate a New Key"
5. Configuración:
   - Key Name: github_deploy
   - Key Type: RSA (4096 bits) o ED25519
   - Key Password: Dejar VACÍO (GitHub Actions no soporta passphrase)
6. Click "Generate Key"
7. Busca la clave generada y click "Manage"
8. Click "Authorize" (esto la agrega a authorized_keys)
9. Click "View Private Key"
10. Copiar TODO el contenido de la clave privada
11. Pegar en GitHub → Settings → Secrets → SSH_PRIVATE_KEY
```

### ❌ OPCIÓN 3: SSH con contraseña (NO RECOMENDADA)

Requeriría modificar el workflow para usar `sshpass`, pero es inseguro y no es una práctica recomendada.

---

## 🧪 Verificar Configuración SSH

Una vez configurada la clave SSH:

```bash
# En tu PC local, probar conexión
ssh -i github_deploy usuario@tu-servidor.com

# Si conecta exitosamente, estás listo!
# Si pide contraseña, la clave pública no está en el servidor
```

---

## 📝 Ejemplo de Valores Reales

### Para un proyecto típico en cPanel/Webhosting:

```
SSH_HOST=ftp.midominio.com
SSH_PORT=65002
SSH_USER=usuario_cpanel
SSH_PRIVATE_KEY=-----BEGIN OPENSSH PRIVATE KEY-----
(contenido de la clave)
-----END OPENSSH PRIVATE KEY-----

PROD_APP_NAME="Plusvalia Admin"
PROD_APP_KEY=base64:abc123def456... (generado con php artisan key:generate --show)
PROD_APP_URL=https://admin.midominio.com

PROD_DB_HOST=localhost
PROD_DB_PORT=3306
PROD_DB_DATABASE=cpanel_laravel
PROD_DB_USERNAME=cpanel_dbuser
PROD_DB_PASSWORD=MiContraseñaSegura123!

PROD_MAIL_MAILER=smtp
PROD_MAIL_HOST=mail.midominio.com
PROD_MAIL_PORT=587
PROD_MAIL_USERNAME=noreply@midominio.com
PROD_MAIL_PASSWORD=contraseña_del_email
PROD_MAIL_ENCRYPTION=tls
PROD_MAIL_FROM_ADDRESS=noreply@midominio.com
```

---

## ⚠️ IMPORTANTE

1. **Nunca** compartas tus secrets en código o repositorios
2. **Nunca** subas las claves SSH privadas a Git
3. Para `PROD_APP_KEY`: Genera uno nuevo o usa el existente de tu `.env` actual del servidor
4. Si cambias `PROD_APP_KEY`, toda la data encriptada (passwords, tokens) se perderá
5. Los valores de email son obligatorios solo si tu aplicación envía correos
6. Si no usas AWS S3, deja esos campos vacíos o no los crees

---

**Última actualización:** 2025-12-04
