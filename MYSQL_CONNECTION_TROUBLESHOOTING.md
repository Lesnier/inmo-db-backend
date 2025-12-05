# Guía de Solución: Error de Conexión MySQL

## El Problema

```
SQLSTATE[HY000] [1045] Access denied for user 'u403607455_u_inmodb'@'82.197.82.225' (using password: YES)
```

Este error indica que **MySQL está bloqueando la conexión desde la IP/hostname del servidor**.

## Causa Raíz

MySQL tiene un sistema de permisos basado en el **host** desde donde se conecta el usuario. Aunque las credenciales (usuario/password) sean correctas, si el host no está en la lista permitida, la conexión se rechaza.

En tu caso:
- Tu usuario `u403607455_u_inmodb` puede conectarse desde:
  - `82.25.83.252` (tu IP local)
  - `191.99.55.83` (otra IP)
- Pero Laravel está intentando conectarse desde `82.197.82.225` (IP interna del servidor)
- Como esa IP no está en la lista, MySQL rechaza la conexión

---

## Solución Paso a Paso

### PASO 1: Subir el Script de Diagnóstico

1. Abre tu cliente FTP (FileZilla, WinSCP, etc.) o el File Manager de cPanel
2. Navega a: `/home/u403607455/domains/inmodb.net/public_html/admin/public/`
3. Sube el archivo `test-db-connection.php` que está en la raíz del repositorio

### PASO 2: Ejecutar el Diagnóstico

1. Abre tu navegador y accede a:
   ```
   https://admin.inmodb.net/test-db-connection.php
   ```

2. El script mostrará información completa sobre:
   - ✅ Configuración actual del .env
   - ✅ Hostname del servidor
   - ✅ IP del servidor
   - ✅ Prueba de conexión a MySQL
   - ✅ Permisos del usuario
   - ✅ Diagnóstico completo

3. **COPIA TODA LA SALIDA DEL SCRIPT** (especialmente la sección "INFORMACIÓN DEL SERVIDOR")

### PASO 3: Identificar el Host Correcto

En la salida del script, busca estas líneas:

```
=== INFORMACIÓN DEL SERVIDOR ===
Hostname del servidor: h123456.example.com
IP del servidor (SERVER_ADDR): 192.168.1.100
```

**Necesitas agregar AMBOS valores** (hostname e IP) a la lista de acceso de MySQL.

### PASO 4: Agregar Hosts Permitidos en cPanel

1. Accede a tu cPanel de Hostinger
2. Ve a **"Remote MySQL"** o **"MySQL Remoto"**
3. En la sección "Add Access Host", agrega **CADA UNO** de estos hosts:

   ```
   localhost
   127.0.0.1
   [HOSTNAME del paso 3]
   [IP del paso 3]
   ```

   **Ejemplo:**
   ```
   localhost
   127.0.0.1
   h82197082225.example.com
   82.197.82.225
   ```

4. Click en "Add Host" para cada uno

### PASO 5: Verificar la Conexión

1. Regresa al navegador y **recarga** la página:
   ```
   https://admin.inmodb.net/test-db-connection.php
   ```

2. Ahora deberías ver:
   ```
   ✓ CONEXIÓN EXITOSA usando mysqli
   ✓ CONEXIÓN EXITOSA usando PDO
   ```

3. Si ves estos mensajes, **¡la conexión funciona!**

### PASO 6: Eliminar el Script de Diagnóstico

**MUY IMPORTANTE:** Este archivo contiene información sensible (configuración de .env)

1. Via FTP/File Manager, elimina:
   ```
   /home/u403607455/domains/inmodb.net/public_html/admin/public/test-db-connection.php
   ```

   O via SSH:
   ```bash
   rm /home/u403607455/domains/inmodb.net/public_html/admin/public/test-db-connection.php
   ```

### PASO 7: Probar Laravel

1. Accede a tu aplicación Laravel:
   ```
   https://admin.inmodb.net
   ```

2. La conexión a MySQL debería funcionar correctamente ahora

---

## Alternativa: Usar Comodín (Menos Seguro)

Si quieres permitir conexiones desde **cualquier host** (útil para desarrollo, pero menos seguro):

1. En cPanel → Remote MySQL
2. Agregar host: `%`
3. Esto permite conexiones desde cualquier IP

**⚠️ ADVERTENCIA:** Esta opción es menos segura porque permite conexiones desde cualquier lugar. Solo úsala si es absolutamente necesario.

---

## Explicación Técnica

### ¿Por qué sucede esto?

MySQL almacena usuarios con el formato: `'usuario'@'host'`

Por ejemplo:
- `'u403607455_u_inmodb'@'localhost'` → Solo conexiones locales
- `'u403607455_u_inmodb'@'82.25.83.252'` → Solo desde esa IP específica
- `'u403607455_u_inmodb'@'%'` → Desde cualquier host

Cuando Laravel (desde el servidor) intenta conectarse a MySQL, el servidor MySQL verifica:
1. ¿El usuario existe? ✅ Sí
2. ¿La contraseña es correcta? ✅ Sí
3. ¿El host está permitido? ❌ No → **Access denied**

### ¿Por qué funciona desde tu PC pero no desde el servidor?

- **Desde tu PC:** Conectas desde IP `82.25.83.252` → Está en la lista → ✅ Permitido
- **Desde el servidor:** Laravel conecta desde IP `82.197.82.225` → No está en la lista → ❌ Denegado

### ¿Por qué localhost no funciona automáticamente?

En algunos webhostings, aunque uses `DB_HOST=localhost`, la conexión puede pasar por la red interna y aparecer con el hostname o IP interna del servidor en lugar de "localhost".

---

## Solución Rápida (Si tienes prisa)

```bash
# En cPanel → Remote MySQL, agregar:
localhost
127.0.0.1
%

# El comodín % permite desde cualquier host
# Esto debería resolver el problema inmediatamente
# Pero es menos seguro
```

---

## Referencias

- [MySQL User Account Management](https://dev.mysql.com/doc/refman/8.0/en/account-management-statements.html)
- [cPanel Remote MySQL Documentation](https://docs.cpanel.net/cpanel/databases/remote-mysql/)

---

**Creado:** 2025-12-05
**Última actualización:** 2025-12-05
