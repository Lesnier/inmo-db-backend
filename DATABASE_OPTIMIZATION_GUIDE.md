# 🚀 Guía de Optimización de Base de Datos para Escala Masiva

## 📊 Escenario de Escala
- **10,000,000 propiedades** (10M)
- **100,000,000 imágenes** (100M - promedio 10 por propiedad)
- Motor: **InnoDB** (MySQL 8.0+)

---

## 🎯 Estrategia de Indexación Implementada

### 1. **PROPERTIES** (Tabla Crítica - 10M registros)

#### Índices Compuestos Implementados:

```sql
-- Búsqueda principal por ubicación (90% de las queries)
idx_properties_location_published (status, country, state, city, published_at)
→ Cubre: Listado de propiedades publicadas por ciudad
→ Ejemplo: "Apartamentos en Madrid"

-- Dashboard del agente
idx_properties_agent_status (agent_id, status, published_at)
→ Cubre: "Mis Propiedades" del agente
→ Permite ordenar por fecha sin table scan

-- Propiedades en edificio
idx_properties_building (building_id, status)
→ Cubre: Todas las unidades de un edificio

-- Búsqueda por categoría + ciudad
idx_properties_category_city (category_id, status, city, price)
→ Cubre: "Casas en Barcelona ordenadas por precio"
→ COVERING INDEX incluye price para evitar lookup

-- Tipo de operación
idx_properties_operation_type (operation_type, type_of_offer, status, price)
→ Cubre: "Propiedades en venta de agentes inmobiliarios"

-- Rango de precios
idx_properties_price_range (status, city, price, published_at)
→ Cubre: WHERE price BETWEEN 100000 AND 500000

-- Propiedades recientes
idx_properties_recent (status, published_at, id)
→ Optimiza: ORDER BY published_at DESC LIMIT 50
```

#### Índice Espacial (SPATIAL):
```sql
SPATIAL INDEX idx_properties_location (lat, lng)
→ Búsquedas geográficas: "Propiedades dentro de 5km"
→ Usa: ST_Distance_Sphere() para cálculos precisos
```

**Ejemplo de uso:**
```sql
SELECT * FROM inmo_properties
WHERE ST_Distance_Sphere(
    POINT(lng, lat),
    POINT(-3.7038, 40.4168)  -- Madrid centro
) <= 5000  -- 5km
AND status = 'published';
```

---

### 2. **MEDIA** (Tabla MUY Grande - 100M registros)

#### Estrategia: COVERING INDEXES para evitar table lookups

```sql
-- Galería de imágenes (query más común)
idx_media_property_gallery (property_id, type, position, url)
→ COVERING INDEX: Incluye 'url' para NO acceder a la tabla
→ Query totalmente satisfecha por el índice
→ Reducción 70-90% en I/O

-- Filtro por tipo
idx_media_by_type (property_id, type, id)
→ "Solo videos de esta propiedad"

-- Paginación eficiente
idx_media_pagination (id, property_id)
→ Lazy loading de imágenes
```

**Ejemplo de query optimizada:**
```sql
-- Esta query USA SOLO EL ÍNDICE (no toca la tabla)
SELECT url FROM inmo_media
WHERE property_id = 12345
AND type = 'image'
ORDER BY position
LIMIT 20;
```

---

### 3. **BUILDINGS**

```sql
idx_buildings_location (country, state, city, id)
→ Búsqueda jerárquica de edificios

SPATIAL INDEX idx_buildings_location_point (lat, lng)
→ Búsquedas geográficas de edificios cercanos
```

---

### 4. **FAVORITES**

```sql
-- Ya existe: UNIQUE(user_id, property_id)

-- Nuevo índice inverso:
idx_favorites_property_users (property_id, user_id, created_at)
→ "Quién ha guardado esta propiedad"
→ Análisis de popularidad
```

---

### 5. **CRM Tables** (Leads, Clients, Activities)

```sql
-- LEADS
idx_leads_agent_status (agent_id, status, created_at)
idx_leads_property (property_id, created_at)
idx_leads_contact (contact_id, agent_id)
idx_leads_source (source, created_at)  -- Analytics

-- CLIENTS
idx_clients_agent_status (agent_id, status, created_at)
idx_clients_contact (contact_id, agent_id)

-- ACTIVITIES (Calendario)
idx_activities_agent_schedule (agent_id, status, scheduled_at)
→ Calendario del agente optimizado
idx_activities_type (type, status, scheduled_at)
→ Filtro por tipo de actividad
```

---

## 🔧 Configuración Recomendada de MySQL/MariaDB

### my.cnf / my.ini

```ini
[mysqld]
# ============================================
# INNODB BUFFER POOL (LO MÁS CRÍTICO)
# ============================================
# Para 100M registros de media + 10M propiedades:
# Recomendación: 70-80% de RAM disponible
innodb_buffer_pool_size = 32G  # Ajustar según RAM del servidor
innodb_buffer_pool_instances = 16  # 1 por cada GB de buffer pool

# ============================================
# INNODB CONFIGURACIÓN
# ============================================
innodb_log_file_size = 2G
innodb_log_buffer_size = 256M
innodb_flush_log_at_trx_commit = 2  # Mejor performance, seguro para replicación
innodb_flush_method = O_DIRECT  # Evita double buffering

# File per table (importante para partitioning futuro)
innodb_file_per_table = 1

# ============================================
# QUERY CACHE (MySQL 5.7) o Query Result Cache (MariaDB)
# ============================================
# MySQL 8.0 removió query cache, usar Redis/Memcached
query_cache_type = 0  # Deshabilitado en MySQL 8.0+
query_cache_size = 0

# ============================================
# CONEXIONES Y THREADS
# ============================================
max_connections = 500
thread_cache_size = 100
table_open_cache = 4096

# ============================================
# TMP TABLES (para JOINs y ORDER BY)
# ============================================
tmp_table_size = 512M
max_heap_table_size = 512M

# ============================================
# ÍNDICES Y SORT
# ============================================
sort_buffer_size = 2M  # Por sesión
read_rnd_buffer_size = 1M
join_buffer_size = 2M

# ============================================
# SLOW QUERY LOG (Monitoreo)
# ============================================
slow_query_log = 1
long_query_time = 2  # Queries > 2 segundos
log_queries_not_using_indexes = 1
```

---

## 📈 Particionamiento de Tablas (Futuro)

### Cuando llegues a 50M+ propiedades, considera:

```sql
-- Particionar PROPERTIES por año de publicación
ALTER TABLE inmo_properties
PARTITION BY RANGE (YEAR(published_at)) (
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Particionar MEDIA por property_id (HASH)
ALTER TABLE inmo_media
PARTITION BY HASH(property_id)
PARTITIONS 64;  -- Distribuye en 64 particiones
```

**Beneficios:**
- Queries solo escanean particiones relevantes
- Mantenimiento más rápido (ANALYZE TABLE por partición)
- Borrado masivo eficiente (DROP PARTITION)

---

## 🎨 Arquitectura Recomendada para 10M+ Properties

### 1. **Cache Layer**
```
┌─────────────┐
│   Redis     │  ← Caché de queries frecuentes
│  (Cluster)  │  ← Sesiones de usuario
└─────────────┘  ← Rate limiting
```

### 2. **Read Replicas**
```
┌──────────────┐         ┌──────────────┐
│   Master     │────────→│  Replica 1   │  (Búsquedas)
│  (Escritura) │         └──────────────┘
└──────────────┘         ┌──────────────┐
                         │  Replica 2   │  (Analytics)
                         └──────────────┘
```

### 3. **CDN para Imágenes**
- **NO** almacenar URLs locales en `inmo_media.url`
- Usar S3/CloudFlare/CDN
- `url` = `https://cdn.inmodb.net/properties/12345/image-1.jpg`

### 4. **Search Engine (Elasticsearch/Meilisearch)**
Para búsquedas full-text y filtros complejos:
```json
{
  "query": {
    "bool": {
      "must": [
        { "match": { "title": "apartamento" } },
        { "term": { "city": "Madrid" } },
        { "range": { "price": { "gte": 100000, "lte": 500000 } } }
      ]
    }
  }
}
```

---

## 🔍 Queries de Monitoreo

### 1. Verificar uso de índices:
```sql
EXPLAIN SELECT * FROM inmo_properties
WHERE status = 'published'
AND city = 'Madrid'
AND price BETWEEN 100000 AND 500000
ORDER BY published_at DESC
LIMIT 50;
```

### 2. Ver queries lentas:
```sql
SELECT * FROM mysql.slow_log
ORDER BY query_time DESC
LIMIT 10;
```

### 3. Estadísticas de índices:
```sql
SHOW INDEX FROM inmo_properties;

-- Ver uso de índices
SELECT * FROM information_schema.INNODB_SYS_INDEXES
WHERE name LIKE 'idx_properties%';
```

### 4. Fragmentación de tablas:
```sql
SELECT table_name,
       ROUND(data_length/1024/1024, 2) AS data_mb,
       ROUND(index_length/1024/1024, 2) AS index_mb,
       ROUND(data_free/1024/1024, 2) AS free_mb
FROM information_schema.TABLES
WHERE table_schema = 'inmodb'
AND table_name LIKE 'inmo_%'
ORDER BY (data_length + index_length) DESC;
```

### 5. Optimizar tablas fragmentadas:
```sql
-- Ejecutar mensualmente en horario de bajo tráfico
OPTIMIZE TABLE inmo_properties;
OPTIMIZE TABLE inmo_media;
```

---

## 📊 Métricas de Performance Esperadas

### Con la indexación optimizada:

| Query | Sin Índices | Con Índices Optimizados |
|-------|-------------|-------------------------|
| Listado por ciudad (50 props) | ~5-10s | **< 50ms** |
| Galería de 10 imágenes | ~200ms | **< 10ms** |
| Búsqueda con filtros | ~8-15s | **< 100ms** |
| Dashboard agente | ~3-5s | **< 50ms** |
| Búsqueda geográfica (5km) | ~10-20s | **< 200ms** |

---

## ⚠️ Advertencias Importantes

### 1. **Índices Compuestos - Orden Importa**
```sql
-- ✅ CORRECTO
idx (status, city, price)
WHERE status = 'published' AND city = 'Madrid' AND price > 100000

-- ❌ NO USARÁ EL ÍNDICE COMPLETO
WHERE city = 'Madrid' AND price > 100000
→ Solo usa el primer campo del índice compuesto
```

### 2. **Covering Indexes - Balance**
- Incluir muchas columnas = índice más grande
- Solo incluir columnas realmente usadas en SELECT

### 3. **SPATIAL Indexes - Limitaciones**
- Solo funciona con tipos POINT, LINESTRING, POLYGON
- Requiere que lat/lng NO sean NULL
- Mejor para búsquedas de proximidad, no rangos

### 4. **Mantenimiento de Índices**
```sql
-- Analizar estadísticas mensualmente
ANALYZE TABLE inmo_properties;
ANALYZE TABLE inmo_media;

-- Ver estadísticas de cardinalidad
SHOW INDEX FROM inmo_properties;
```

---

## 🚀 Plan de Implementación

### Fase 1: Desarrollo (actual)
- ✅ Implementar índices compuestos
- ✅ SPATIAL indexes para geo-búsquedas
- ✅ COVERING indexes en media

### Fase 2: 100K-1M propiedades
- Implementar Redis cache
- Configurar Read Replicas
- Migrar imágenes a CDN

### Fase 3: 1M-10M propiedades
- Implementar Elasticsearch
- Particionar tabla de media
- Optimizar configuración InnoDB

### Fase 4: 10M+ propiedades
- Particionar tabla properties
- Considerar sharding de BD
- Implementar archivado de datos antiguos

---

## 📚 Referencias

- [MySQL 8.0 Optimization Guide](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [InnoDB Buffer Pool](https://dev.mysql.com/doc/refman/8.0/en/innodb-buffer-pool.html)
- [MySQL Spatial Indexes](https://dev.mysql.com/doc/refman/8.0/en/spatial-index-optimization.html)
- [Covering Indexes](https://use-the-index-luke.com/sql/clustering/index-organized-clustering)
