# 🌍 Ejemplos de Búsquedas Geográficas y FULLTEXT

## 📍 1. Búsqueda por Radio (Cerca de un Punto)

### Ejemplo: Propiedades dentro de 5km de Madrid centro

```sql
-- Usando columna POINT virtual (ÓPTIMO)
SELECT
    id,
    title,
    price,
    city,
    ST_Distance_Sphere(
        location,
        POINT(-3.7038, 40.4168)  -- Madrid centro (lng, lat)
    ) / 1000 AS distance_km
FROM inmo_properties
WHERE status = 'published'
AND ST_Distance_Sphere(
    location,
    POINT(-3.7038, 40.4168)
) <= 5000  -- 5km en metros
ORDER BY distance_km
LIMIT 50;
```

**Performance:** Usa `idx_properties_location_point` (SPATIAL) + `idx_properties_geo_filters`

### Búsqueda con filtros adicionales:

```sql
-- Apartamentos en venta dentro de 5km con rango de precio
SELECT
    id,
    title,
    price,
    operation_type,
    ST_Distance_Sphere(location, POINT(-3.7038, 40.4168)) / 1000 AS distance_km
FROM inmo_properties
WHERE status = 'published'
AND operation_type = 'sell'
AND category_id = 1  -- Apartamentos
AND price BETWEEN 150000 AND 500000
AND ST_Distance_Sphere(
    location,
    POINT(-3.7038, 40.4168)
) <= 5000
ORDER BY price ASC
LIMIT 50;
```

**Índices usados:**
1. `idx_properties_location_point` (filtro espacial inicial)
2. `idx_properties_geo_filters` (status, operation_type, category_id, price)

---

## 🗺️ 2. Búsqueda por Polígono (Zona Dibujada en el Mapa)

### Ejemplo: Propiedades dentro de un polígono (barrio, distrito)

```sql
-- Definir polígono (ejemplo: área rectangular en Madrid)
SET @polygon = ST_GeomFromText('
    POLYGON((
        -3.71 40.41,
        -3.69 40.41,
        -3.69 40.43,
        -3.71 40.43,
        -3.71 40.41
    ))
');

-- Buscar propiedades dentro del polígono
SELECT
    id,
    title,
    price,
    street_address,
    lat,
    lng
FROM inmo_properties
WHERE status = 'published'
AND ST_Contains(@polygon, location)
ORDER BY price DESC
LIMIT 100;
```

### Polígono complejo (área irregular):

```sql
-- Polígono del barrio de Chamberí, Madrid
SET @chamberi = ST_GeomFromText('
    POLYGON((
        -3.7080 40.4350,
        -3.7000 40.4360,
        -3.6980 40.4310,
        -3.6950 40.4280,
        -3.7020 40.4260,
        -3.7080 40.4300,
        -3.7080 40.4350
    ))
');

SELECT *
FROM inmo_properties
WHERE ST_Contains(@chamberi, location)
AND status = 'published'
AND operation_type = 'rent';
```

---

## 🔍 3. Búsqueda FULLTEXT (Título y Dirección)

### Búsqueda simple en título:

```sql
-- Buscar "apartamento madrid"
SELECT
    id,
    title,
    street_address,
    price,
    MATCH(title) AGAINST('apartamento madrid' IN NATURAL LANGUAGE MODE) AS relevance
FROM inmo_properties
WHERE MATCH(title) AGAINST('apartamento madrid' IN NATURAL LANGUAGE MODE)
AND status = 'published'
ORDER BY relevance DESC
LIMIT 50;
```

**Índice usado:** `idx_properties_search_title`

### Búsqueda combinada (título + dirección + ciudad):

```sql
-- Buscar "calle serrano"
SELECT
    id,
    title,
    street_address,
    city,
    district,
    price,
    MATCH(title, street_address, city, district)
        AGAINST('calle serrano madrid' IN NATURAL LANGUAGE MODE) AS score
FROM inmo_properties
WHERE MATCH(title, street_address, city, district)
    AGAINST('calle serrano madrid' IN NATURAL LANGUAGE MODE)
AND status = 'published'
ORDER BY score DESC
LIMIT 50;
```

**Índice usado:** `idx_properties_search_full`

### Búsqueda BOOLEAN MODE (operadores):

```sql
-- Buscar "apartamento" Y "lujo", PERO NO "reformar"
SELECT
    id,
    title,
    price,
    MATCH(title) AGAINST('+apartamento +lujo -reformar' IN BOOLEAN MODE) AS relevance
FROM inmo_properties
WHERE MATCH(title) AGAINST('+apartamento +lujo -reformar' IN BOOLEAN MODE)
AND status = 'published'
AND price >= 500000
ORDER BY relevance DESC, price DESC
LIMIT 50;
```

---

## 🎯 4. Búsquedas Combinadas (Geo + Filtros + FULLTEXT)

### Ejemplo realista: "Apartamentos en venta cerca de Retiro"

```sql
-- Paso 1: Geocodificar "Retiro" (Parque del Retiro, Madrid)
SET @retiro_point = POINT(-3.6833, 40.4153);

-- Paso 2: Búsqueda combinada
SELECT
    p.id,
    p.title,
    p.street_address,
    p.price,
    p.city,
    p.district,
    ST_Distance_Sphere(p.location, @retiro_point) / 1000 AS distance_km,
    MATCH(p.title, p.street_address, p.city, p.district)
        AGAINST('retiro parque' IN NATURAL LANGUAGE MODE) AS text_score
FROM inmo_properties p
WHERE p.status = 'published'
AND p.operation_type = 'sell'
AND p.category_id = 1  -- Apartamentos
AND (
    -- Filtro geográfico: dentro de 3km
    ST_Distance_Sphere(p.location, @retiro_point) <= 3000
    OR
    -- O que mencione "retiro" en el texto
    MATCH(p.title, p.street_address, p.city, p.district)
        AGAINST('retiro' IN BOOLEAN MODE)
)
ORDER BY
    text_score DESC,
    distance_km ASC,
    price ASC
LIMIT 50;
```

---

## 🏢 5. Búsqueda por Tipo de Propiedad + Operación

### Venta de casas en Madrid:

```sql
SELECT *
FROM inmo_properties
WHERE status = 'published'
AND operation_type = 'sell'
AND category_id = 2  -- Casas
AND city = 'Madrid'
ORDER BY price ASC
LIMIT 50;
```

**Índice usado:** `idx_properties_operation_category_city`

### Apartamentos en renta por distrito:

```sql
SELECT *
FROM inmo_properties
WHERE status = 'published'
AND operation_type = 'rent'
AND city = 'Madrid'
AND district = 'Chamberí'
AND price BETWEEN 800 AND 2000
ORDER BY price ASC
LIMIT 50;
```

**Índice usado:** `idx_properties_district`

---

## 🔢 6. Búsqueda por Código Postal + Filtros

```sql
SELECT *
FROM inmo_properties
WHERE status = 'published'
AND zip_code IN ('28001', '28002', '28003')  -- Centro de Madrid
AND operation_type = 'sell'
AND price <= 300000
ORDER BY price ASC
LIMIT 50;
```

**Índice usado:** `idx_properties_zipcode`

---

## 🌐 7. Búsqueda de Propiedades Cercanas a un Edificio

```sql
-- Encontrar todas las propiedades cerca de un edificio específico
SELECT
    p.id,
    p.title,
    p.price,
    b.name AS building_name,
    ST_Distance_Sphere(p.location, b.location) AS distance_meters
FROM inmo_properties p
CROSS JOIN inmo_buildings b
WHERE b.id = 5  -- Torre Diamante
AND p.status = 'published'
AND ST_Distance_Sphere(p.location, b.location) <= 1000  -- 1km
AND p.building_id != b.id  -- Excluir propiedades DEL mismo edificio
ORDER BY distance_meters
LIMIT 50;
```

---

## 📊 8. Query Completo: Búsqueda Avanzada con Todos los Filtros

```sql
-- Búsqueda tipo Zillow/Idealista
SELECT
    p.id,
    p.title,
    p.street_address,
    p.city,
    p.district,
    p.zip_code,
    p.price,
    p.operation_type,
    p.currency,
    c.name AS category_name,
    ST_Distance_Sphere(
        p.location,
        POINT(-3.7038, 40.4168)  -- Madrid centro
    ) / 1000 AS distance_km,
    MATCH(p.title, p.street_address, p.city, p.district)
        AGAINST('apartamento madrid centro' IN NATURAL LANGUAGE MODE) AS relevance
FROM inmo_properties p
LEFT JOIN inmo_categories c ON p.category_id = c.id
WHERE p.status = 'published'
-- Filtro de operación
AND p.operation_type = 'sell'
-- Filtro de tipo
AND p.category_id IN (1, 3)  -- Apartamentos y Condos
-- Filtro de ubicación (texto)
AND p.city = 'Madrid'
AND (
    p.district IN ('Centro', 'Chamberí', 'Salamanca')
    OR p.zip_code IN ('28001', '28002', '28003', '28004', '28010')
)
-- Filtro de precio
AND p.price BETWEEN 150000 AND 500000
-- Filtro geográfico (opcional)
AND (
    p.location IS NULL
    OR ST_Distance_Sphere(p.location, POINT(-3.7038, 40.4168)) <= 10000
)
-- Filtro de búsqueda de texto (opcional)
AND (
    p.title LIKE '%apartamento%'
    OR MATCH(p.title, p.street_address) AGAINST('apartamento' IN BOOLEAN MODE)
)
ORDER BY
    relevance DESC,
    distance_km ASC,
    price ASC
LIMIT 50
OFFSET 0;  -- Para paginación
```

**Índices utilizados:**
1. `idx_properties_operation_category_city` (filtros principales)
2. `idx_properties_district` (distrito)
3. `idx_properties_location_point` (geo)
4. `idx_properties_search_full` (FULLTEXT)

---

## 🚀 Optimización en Laravel/PHP

### Controller Example:

```php
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function searchGeo(Request $request)
    {
        $lat = $request->lat;  // 40.4168
        $lng = $request->lng;  // -3.7038
        $radius = $request->radius ?? 5000;  // metros

        $properties = DB::table('inmo_properties')
            ->select(
                'id',
                'title',
                'price',
                'city',
                DB::raw("
                    ST_Distance_Sphere(
                        location,
                        POINT(?, ?)
                    ) / 1000 AS distance_km
                ", [$lng, $lat])
            )
            ->where('status', 'published')
            ->whereRaw('
                ST_Distance_Sphere(
                    location,
                    POINT(?, ?)
                ) <= ?
            ', [$lng, $lat, $radius])
            ->when($request->operation_type, function($q, $type) {
                return $q->where('operation_type', $type);
            })
            ->when($request->category_id, function($q, $cat) {
                return $q->where('category_id', $cat);
            })
            ->when($request->min_price, function($q, $min) {
                return $q->where('price', '>=', $min);
            })
            ->when($request->max_price, function($q, $max) {
                return $q->where('price', '<=', $max);
            })
            ->orderBy('distance_km')
            ->limit(50)
            ->get();

        return response()->json($properties);
    }

    public function searchPolygon(Request $request)
    {
        // $request->polygon = [[lng, lat], [lng, lat], ...]
        $polygon = $request->polygon;

        // Construir POLYGON WKT
        $points = collect($polygon)->map(fn($p) => "{$p[0]} {$p[1]}")->join(', ');
        $wkt = "POLYGON(({$points}))";

        $properties = DB::table('inmo_properties')
            ->where('status', 'published')
            ->whereRaw('ST_Contains(ST_GeomFromText(?), location)', [$wkt])
            ->get();

        return response()->json($properties);
    }

    public function searchFulltext(Request $request)
    {
        $query = $request->q;  // "apartamento madrid centro"

        $properties = DB::table('inmo_properties')
            ->select(
                '*',
                DB::raw("
                    MATCH(title, street_address, city, district)
                    AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
                ", [$query])
            )
            ->whereRaw('
                MATCH(title, street_address, city, district)
                AGAINST(? IN NATURAL LANGUAGE MODE)
            ', [$query])
            ->where('status', 'published')
            ->orderByDesc('relevance')
            ->limit(50)
            ->get();

        return response()->json($properties);
    }
}
```

---

## 📝 Notas Importantes

### 1. **POINT vs (lat, lng)**
- MySQL usa `POINT(lng, lat)` NO `POINT(lat, lng)`
- Longitud primero, latitud segundo

### 2. **ST_Distance_Sphere**
- Retorna distancia en **metros**
- Dividir por 1000 para kilómetros
- Usa cálculos esféricos (más preciso que distancia euclidiana)

### 3. **FULLTEXT Limitaciones**
- Palabras < 4 caracteres ignoradas por defecto
- Cambiar en `my.cnf`: `ft_min_word_len = 3`
- Requiere `OPTIMIZE TABLE` después del cambio

### 4. **Performance**
- SPATIAL indexes solo funcionan con InnoDB en MySQL 5.7+
- FULLTEXT indexes funcionan mejor con MyISAM pero InnoDB es OK en MySQL 8.0+
- Combinar SPATIAL + FULLTEXT puede requerir dos pasos

### 5. **Columnas Virtuales POINT**
- `STORED` ocupa espacio pero permite SPATIAL INDEX
- `VIRTUAL` no ocupa espacio pero NO permite índices
- Usamos `STORED` para máxima performance
