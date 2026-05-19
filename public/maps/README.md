# Kenya counties TopoJSON

The Dashboard's "Top Sales Locations" map (Unovis TopoJSONMap) loads
**`public/maps/kenya-counties.topojson`** at runtime.

This file is **not committed to git**. Download a Kenya counties TopoJSON and
drop it here. The widget will work as soon as the file is present.

## Recommended sources

| Source | URL | Notes |
|---|---|---|
| KNBS-aligned community file | `https://github.com/kennedymwavu/kenya-counties` | Includes the 47 counties with KNBS codes |
| Code for Africa | `https://github.com/CodeForAfrica/kenya-counties` | Has both GeoJSON and TopoJSON variants |
| openAFRICA | `https://africaopendata.org/dataset/kenya-counties-shapefile` | Original shapefile — convert with `mapshaper`/`topojson-server` |

## Required feature properties

The Livewire computed `topSalesLocations` returns rows with both `name`
(e.g. `"Nairobi"`) and `code` (e.g. `"30"`). The JS layer tries to match
the county data against the following TopoJSON feature properties, in order:

1. `properties.name`
2. `properties.NAME_1`
3. `properties.COUNTY`
4. `properties.code`

So any of those naming conventions will work without code changes.

## File size guidance

- Aim for **&lt; 250 KB** simplified TopoJSON for fast first paint.
- Use `mapshaper` to simplify: `mapshaper kenya-counties.geojson -simplify 5% -o format=topojson`

## Folder layout

```
public/maps/
├── README.md              ← this file
└── kenya-counties.topojson ← drop the file here
```
