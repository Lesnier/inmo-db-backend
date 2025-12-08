# 🗺️ Búsqueda por Bounding Box (Cuadrante del Mapa)

## 📍 Concepto: ¿Qué es un Bounding Box?

Un **Bounding Box** (cuadro delimitador) es el área rectangular visible en el mapa definida por dos puntos:
- **NorthEast (NE)**: Esquina superior derecha
- **SouthWest (SW)**: Esquina inferior izquierda

```
     NE (ne_lat, ne_lng)
         ┌─────────────┐
         │             │
         │   VISIBLE   │  ← Área del mapa visible
         │     MAP     │
         │             │
         └─────────────┘
    SW (sw_lat, sw_lng)
```

## 🚀 Flujo Frontend → Backend

### 1. Frontend (Google Maps / Leaflet)

```javascript
// Google Maps API
const map = new google.maps.Map(document.getElementById('map'), {
  center: { lat: 40.4168, lng: -3.7038 },
  zoom: 13
});

// Evento cuando el usuario mueve o hace zoom
map.addListener('idle', () => {
  const bounds = map.getBounds();
  const ne = bounds.getNorthEast();
  const sw = bounds.getSouthWest();

  // Enviar al backend
  searchPropertiesInBounds({
    ne_lat: ne.lat(),
    ne_lng: ne.lng(),
    sw_lat: sw.lat(),
    sw_lng: sw.lng()
  });
});

// Función de búsqueda
async function searchPropertiesInBounds(bounds) {
  const response = await fetch('/api/properties/map', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      bounds: bounds,
      // Filtros adicionales
      operation_type: 'sell',
      category_id: 1,
      min_price: 150000,
      max_price: 500000
    })
  });

  const properties = await response.json();
  updateMapMarkers(properties);
}
```

### 2. Request al Backend

```http
POST /api/properties/map
Content-Type: application/json

{
  "bounds": {
    "ne_lat": 40.4500,
    "ne_lng": -3.6800,
    "sw_lat": 40.3900,
    "sw_lng": -3.7300
  },
  "operation_type": "sell",
  "category_id": 1,
  "min_price": 150000,
  "max_price": 500000,
  "status": "published"
}
```

---

## 🎯 Query SQL Optimizada

### Opción 1: Usando MBRContains (Más Rápido)

```sql
-- Crear el bounding box como POLYGON
SET @bbox = ST_GeomFromText('
    POLYGON((
        -3.7300 40.3900,  -- SW (lng, lat)
        -3.6800 40.3900,  -- SE
        -3.6800 40.4500,  -- NE
        -3.7300 40.4500,  -- NW
        -3.7300 40.3900   -- Cerrar polígono (mismo que SW)
    ))
');

-- Buscar propiedades dentro del bounding box
SELECT
    id,
    title,
    price,
    lat,
    lng,
    street_address
FROM inmo_properties
WHERE status = 'published'
AND location IS NOT NULL
AND MBRContains(@bbox, location)  -- Usa SPATIAL INDEX
ORDER BY price ASC
LIMIT 500;  -- Limitar para evitar sobrecarga
```

**Performance:** Usa `idx_properties_location_point` (SPATIAL INDEX)

### Opción 2: Usando ST_Contains (Más Preciso)

```sql
SELECT
    id,
    title,
    price,
    lat,
    lng
FROM inmo_properties
WHERE status = 'published'
AND location IS NOT NULL
AND ST_Contains(@bbox, location)
LIMIT 500;
```

**Diferencia:**
- `MBRContains`: Más rápido, usa Minimum Bounding Rectangle
- `ST_Contains`: Más preciso, pero ligeramente más lento

---

## 💻 Implementación en Laravel

### PropertyController.php

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PropertyController extends Controller
{
    /**
     * Búsqueda de propiedades por bounding box del mapa
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByMap(Request $request)
    {
        $validated = $request->validate([
            'bounds.ne_lat' => 'required|numeric|between:-90,90',
            'bounds.ne_lng' => 'required|numeric|between:-180,180',
            'bounds.sw_lat' => 'required|numeric|between:-90,90',
            'bounds.sw_lng' => 'required|numeric|between:-180,180',
            'operation_type' => 'nullable|in:sell,rent',
            'category_id' => 'nullable|integer|exists:inmo_categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'limit' => 'nullable|integer|min:1|max:1000'
        ]);

        $bounds = $request->bounds;

        // Crear POLYGON del bounding box
        // IMPORTANTE: MySQL usa (lng, lat) NO (lat, lng)
        $polygon = sprintf(
            'POLYGON((%f %f, %f %f, %f %f, %f %f, %f %f))',
            $bounds['sw_lng'], $bounds['sw_lat'],  // SW
            $bounds['ne_lng'], $bounds['sw_lat'],  // SE
            $bounds['ne_lng'], $bounds['ne_lat'],  // NE
            $bounds['sw_lng'], $bounds['ne_lat'],  // NW
            $bounds['sw_lng'], $bounds['sw_lat']   // Cerrar (SW)
        );

        $query = DB::table('inmo_properties')
            ->select([
                'id',
                'title',
                'slug',
                'price',
                'currency',
                'operation_type',
                'lat',
                'lng',
                'street_address',
                'city',
                'district',
                'category_id',
                'status'
            ])
            ->where('status', 'published')
            ->whereNotNull('location')
            // Usar MBRContains para máxima velocidad
            ->whereRaw("MBRContains(ST_GeomFromText(?), location)", [$polygon]);

        // Filtros adicionales
        if ($request->has('operation_type')) {
            $query->where('operation_type', $request->operation_type);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Limitar resultados para evitar sobrecarga
        $limit = $request->input('limit', 500);
        $properties = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'count' => $properties->count(),
            'properties' => $properties,
            'bounds' => $bounds
        ]);
    }

    /**
     * Búsqueda con clustering (para muchos resultados)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByMapClustered(Request $request)
    {
        $validated = $request->validate([
            'bounds.ne_lat' => 'required|numeric',
            'bounds.ne_lng' => 'required|numeric',
            'bounds.sw_lat' => 'required|numeric',
            'bounds.sw_lng' => 'required|numeric',
            'zoom' => 'required|integer|min:1|max:20',
        ]);

        $bounds = $request->bounds;
        $zoom = $request->zoom;

        // Crear polygon
        $polygon = sprintf(
            'POLYGON((%f %f, %f %f, %f %f, %f %f, %f %f))',
            $bounds['sw_lng'], $bounds['sw_lat'],
            $bounds['ne_lng'], $bounds['sw_lat'],
            $bounds['ne_lng'], $bounds['ne_lat'],
            $bounds['sw_lng'], $bounds['ne_lat'],
            $bounds['sw_lng'], $bounds['sw_lat']
        );

        // Clustering basado en zoom level
        // Zoom alto (15+) = mostrar propiedades individuales
        // Zoom bajo (<15) = agrupar en clusters
        if ($zoom >= 15) {
            // Mostrar propiedades individuales
            $properties = DB::table('inmo_properties')
                ->select([
                    'id',
                    'title',
                    'price',
                    'lat',
                    'lng',
                    'operation_type'
                ])
                ->where('status', 'published')
                ->whereNotNull('location')
                ->whereRaw("MBRContains(ST_GeomFromText(?), location)", [$polygon])
                ->limit(1000)
                ->get();

            return response()->json([
                'type' => 'properties',
                'data' => $properties
            ]);
        } else {
            // Crear clusters (agrupación por grid)
            // Dividir el mapa en una cuadrícula
            $gridSize = $this->getGridSize($zoom);

            $clusters = DB::select("
                SELECT
                    COUNT(*) as count,
                    AVG(lat) as center_lat,
                    AVG(lng) as center_lng,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    AVG(price) as avg_price,
                    FLOOR(lat / ?) * ? as grid_lat,
                    FLOOR(lng / ?) * ? as grid_lng
                FROM inmo_properties
                WHERE status = 'published'
                AND location IS NOT NULL
                AND MBRContains(ST_GeomFromText(?), location)
                GROUP BY grid_lat, grid_lng
                HAVING count > 0
            ", [
                $gridSize, $gridSize,
                $gridSize, $gridSize,
                $polygon
            ]);

            return response()->json([
                'type' => 'clusters',
                'data' => $clusters
            ]);
        }
    }

    /**
     * Determinar tamaño de grid según zoom
     *
     * @param int $zoom
     * @return float
     */
    private function getGridSize(int $zoom): float
    {
        // Tamaño de grid en grados (más pequeño = más zoom)
        return match(true) {
            $zoom <= 5 => 5.0,      // Zoom muy lejano
            $zoom <= 8 => 1.0,      // Zoom país
            $zoom <= 10 => 0.5,     // Zoom región
            $zoom <= 12 => 0.1,     // Zoom ciudad
            $zoom <= 14 => 0.05,    // Zoom distrito
            default => 0.01,        // Zoom calle
        };
    }
}
```

### Routes (api.php)

```php
// Búsqueda por mapa (bounding box)
Route::post('/properties/map', [PropertyController::class, 'searchByMap']);
Route::post('/properties/map/clustered', [PropertyController::class, 'searchByMapClustered']);
```

---

## 🎯 Ejemplo de Query Completa con Filtros

```sql
-- Variables
SET @ne_lat = 40.4500;
SET @ne_lng = -3.6800;
SET @sw_lat = 40.3900;
SET @sw_lng = -3.7300;

-- Crear bounding box
SET @bbox = ST_GeomFromText(
    CONCAT(
        'POLYGON((',
        @sw_lng, ' ', @sw_lat, ', ',
        @ne_lng, ' ', @sw_lat, ', ',
        @ne_lng, ' ', @ne_lat, ', ',
        @sw_lng, ' ', @ne_lat, ', ',
        @sw_lng, ' ', @sw_lat,
        '))'
    )
);

-- Query completa con filtros
SELECT
    p.id,
    p.title,
    p.price,
    p.currency,
    p.lat,
    p.lng,
    p.street_address,
    p.city,
    p.operation_type,
    c.name as category_name
FROM inmo_properties p
LEFT JOIN inmo_categories c ON p.category_id = c.id
WHERE p.status = 'published'
AND p.location IS NOT NULL
AND MBRContains(@bbox, p.location)
-- Filtros adicionales
AND p.operation_type = 'sell'
AND p.category_id IN (1, 3)  -- Apartamentos y Condos
AND p.price BETWEEN 150000 AND 500000
ORDER BY p.price ASC
LIMIT 500;
```

**Índices usados:**
1. `idx_properties_location_point` (SPATIAL) - Filtro geográfico
2. `idx_properties_geo_filters` - Filtros adicionales (status, operation_type, category_id, price)

---

## 📊 Performance: Bounding Box vs Otras Búsquedas

| Tipo de Búsqueda | Complejidad | Performance | Casos de Uso |
|------------------|-------------|-------------|--------------|
| **Bounding Box (MBRContains)** | O(log n) | **< 50ms** | Mapa interactivo (90% de casos) |
| Radio (ST_Distance_Sphere) | O(n) | 200-500ms | "Cerca de mí", radio específico |
| Polígono complejo (ST_Contains) | O(n log n) | 100-300ms | Zonas irregulares, distritos |
| Ciudad/Distrito (índice normal) | O(log n) | < 20ms | Filtro de texto simple |

**Conclusión:** Bounding Box con `MBRContains` es **LA forma más eficiente** para mapas interactivos.

---

## 🌍 Frontend: Actualización Dinámica del Mapa

### React Example

```jsx
import { useEffect, useState } from 'react';
import { GoogleMap, Marker } from '@react-google-maps/api';

function PropertyMap() {
  const [map, setMap] = useState(null);
  const [properties, setProperties] = useState([]);
  const [filters, setFilters] = useState({
    operation_type: 'sell',
    min_price: 0,
    max_price: 1000000
  });

  // Actualizar propiedades cuando el mapa se mueve
  const handleMapIdle = async () => {
    if (!map) return;

    const bounds = map.getBounds();
    const ne = bounds.getNorthEast();
    const sw = bounds.getSouthWest();

    const response = await fetch('/api/properties/map', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        bounds: {
          ne_lat: ne.lat(),
          ne_lng: ne.lng(),
          sw_lat: sw.lat(),
          sw_lng: sw.lng()
        },
        ...filters
      })
    });

    const data = await response.json();
    setProperties(data.properties);
  };

  return (
    <GoogleMap
      center={{ lat: 40.4168, lng: -3.7038 }}
      zoom={13}
      onLoad={setMap}
      onIdle={handleMapIdle}  // Se ejecuta cuando el usuario para de moverse
      onBoundsChanged={() => {
        // Opcional: actualizar mientras se mueve (más requests)
      }}
    >
      {properties.map(property => (
        <Marker
          key={property.id}
          position={{ lat: property.lat, lng: property.lng }}
          title={property.title}
          onClick={() => showPropertyDetails(property.id)}
        />
      ))}
    </GoogleMap>
  );
}
```

---

## 🔥 Optimizaciones Adicionales

### 1. **Debouncing de Requests**

```javascript
let searchTimeout;

map.addListener('bounds_changed', () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    searchPropertiesInBounds();
  }, 300);  // Esperar 300ms después de que el usuario pare de moverse
});
```

### 2. **Caché de Resultados**

```php
public function searchByMap(Request $request)
{
    $bounds = $request->bounds;

    // Crear cache key basado en bounds redondeados
    $cacheKey = sprintf(
        'map_search:%f:%f:%f:%f',
        round($bounds['ne_lat'], 3),
        round($bounds['ne_lng'], 3),
        round($bounds['sw_lat'], 3),
        round($bounds['sw_lng'], 3)
    );

    return Cache::remember($cacheKey, 300, function() use ($request) {
        // Query a BD...
    });
}
```

### 3. **Limitar Resultados por Zoom**

```php
private function getLimitByZoom(int $zoom): int
{
    return match(true) {
        $zoom <= 10 => 100,   // Zoom lejano, menos markers
        $zoom <= 14 => 500,   // Zoom medio
        default => 1000,      // Zoom cercano, más detail
    };
}
```

### 4. **Agregar Heatmap para Densidad**

```javascript
// Google Maps Heatmap Layer
const heatmapData = properties.map(p => ({
  location: new google.maps.LatLng(p.lat, p.lng),
  weight: p.price / 1000  // Peso por precio
}));

const heatmap = new google.maps.visualization.HeatmapLayer({
  data: heatmapData,
  map: map
});
```

---

## ✅ Checklist de Implementación

- [x] SPATIAL INDEX en columna `location` POINT
- [x] Índice compuesto `idx_properties_geo_filters` para filtros post-geo
- [x] Controller con método `searchByMap()`
- [x] Validación de bounds en request
- [x] Usar `MBRContains` para máxima velocidad
- [ ] Implementar clustering para zoom bajo
- [ ] Implementar caché de resultados
- [ ] Implementar debouncing en frontend
- [ ] Limitar resultados según zoom level
- [ ] Agregar heatmap opcional

---

## 🎯 Resumen

La búsqueda por **Bounding Box** es:
- ✅ **La más eficiente** para mapas interactivos
- ✅ **La más común** en apps tipo Zillow/Idealista
- ✅ **Perfectamente optimizada** con SPATIAL INDEX
- ✅ **Escalable** a 10M+ propiedades

**Performance esperada:** < 50ms para 500 resultados dentro del bounding box
