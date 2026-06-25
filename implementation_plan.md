# Implementation Plan: Aesthetic Enhancement for Merchant Sales & Producer Sales Pages

## [Overview]

Enhance the visual aesthetics of two Filament v5 pages (Merchant Sales & Producer Sales) to match the MoonShine design system, which features glassmorphism UI, dark theme support, gradient ribbons, compact metric pills, and responsive card-based layouts.

## [Types]

### Data Structures (Already Exists in PHP Pages)
- `$reportData` - Collection of sales report rows
- `$totals` - Aggregated summary values (titip, laku, modal, kas, setoran, omset, laba)
- `$groupedData` - Hierarchical grouped data for producer sales (produsen → produk)
- `$mode` - Filter mode: 'tanggal', 'nama', 'tahunan', 'range'

### Component Patterns to Adopt
1. **Glassmorphism Cards**: Semi-transparent backgrounds with blur effect
2. **Metric Pills**: Compact inline badges for quick stats
3. **Heatmap Colors**: Dynamic color based on percentage values
4. **Floating Ribbon**: Sticky summary bar with key metrics
5. **Expandable Groups**: Accordion-style collapsible sections

## [Files]

### New Files to Create
1. `resources/views/filament/components/glass-card.blade.php` - Reusable glass panel component
2. `resources/views/filament/components/metric-pill.blade.php` - Compact metric display
3. `resources/views/filament/components/floating-ribbon.blade.php` - Sticky summary ribbon

### Existing Files to Modify
1. `resources/views/filament/pages/merchant-sales.blade.php` - Full aesthetic overhaul
2. `resources/views/filament/pages/producer-sales.blade.php` - Full aesthetic overhaul
3. `app/Filament/Pages/MerchantSalesPage.php` - Add helper methods if needed
4. `app/Filament/Pages/ProducerSalesPage.php` - Add helper methods if needed

## [Functions]

### New Blade Components
1. `x-glass-card` - Wrapper with glassmorphism styling
2. `x-metric-pill` - Compact inline stat display
3. `x-hhr-toolbar` - Horizontal scrollable filter toolbar
4. `x-sortable-header` - Sortable column header helper

### Modified Functions (Blade)
1. **Enhanced Table Styling**: Add heatmap color coding for percentages
2. **Expandable Groups**: Alpine.js accordion for grouped data
3. **Live Search**: Client-side filtering by name/date
4. **Floating Summary**: Sticky ribbon showing totals
5. **Responsive Cards**: Mobile-friendly metric display

## [Classes]

### CSS Classes to Add
```css
/* Glassmorphism */
.glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); }
.glass-pill { background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(16px); }

/* Metric Pills */
.metric-capsule { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 9999px; font-size: 11px; }
.pill-onyx { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); }

/* Number Badges */
.num-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; border-radius: 6px; font-size: 9px; font-weight: 700; }
.num-badge-producer { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
.num-badge-product { background: rgba(56, 189, 248, 0.2); color: #38bdf8; }

/* Group Box */
.group-box { border: 1px solid rgba(255, 255, 255, 0.1); }
.box-header { background: rgba(255, 255, 255, 0.02); }

/* Heatmap */
.heatmap-cell { font-weight: 700; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }

/* Toolbar */
.hhr-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 12px; background: rgba(30, 41, 59, 0.5); border-radius: 12px; }
.hhr-group { display: flex; align-items: center; gap: 6px; }
.hhr-label-ghost { font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.3); font-weight: 700; }
.hhr-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); cursor: pointer; transition: all 0.2s; }
.hhr-btn:hover { background: rgba(255,255,255,0.1); color: #e2e8f0; }
.hhr-btn-excel { background: rgba(16, 185, 129, 0.15); color: #10b981; border-color: rgba(16, 185, 129, 0.3); }
```

## [Dependencies]

### CSS Framework
- Tailwind CSS (already in use by Filament v5)
- Custom CSS classes added via `<style>` blocks

### JavaScript
- Alpine.js (built into Filament)
- No new dependencies required

### Helper Functions Needed
1. `alignUang($number, $formatted = true)` - Format currency with thousand separators
2. `getHeatmapColor($percentage)` - Return HSL color based on percentage (0-100)

## [Testing]

### Visual Validation Checklist
- [ ] Glassmorphism cards render with backdrop blur
- [ ] Metric pills display inline with proper spacing
- [ ] Heatmap colors apply correctly to percentage cells
- [ ] Floating ribbon sticks to top when scrolling
- [ ] Expandable groups toggle correctly
- [ ] Dark mode compatibility
- [ ] Mobile responsiveness (breakpoints at 640px, 768px, 1024px)
- [ ] Live search filters table rows in real-time
- [ ] Sortable headers work with current mode

## [Implementation Order]

1. **Create Helper Functions** - Add `alignUang()` and `getHeatmapColor()` helpers
2. **Create Blade Components** - Build reusable glass-card, metric-pill, hhr-toolbar
3. **Enhance Merchant Sales Blade** - Apply new styling, replace plain table with cards
4. **Enhance Producer Sales Blade** - Apply expandable group layout
5. **Add Alpine.js Interactivity** - Live search, expand/collapse, drill-down
6. **Test & Polish** - Verify responsiveness and dark mode
