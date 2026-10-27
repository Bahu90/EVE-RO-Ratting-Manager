<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oxanium:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ \RattingManager\Support\Asset::url('css/ratting-manager.css') }}">
<style>
/* Forced inline as Mining Manager does (in the EVE-RO theme's colours): some SeAT skins lighten cards and
   tables after the stylesheet loads, and the plugin's light text all but
   vanishes on them. */
.ratting-manager-dashboard .tab-content {
    background-color: transparent !important;
}

.ratting-manager-dashboard .card-body,
.ratting-manager-dashboard .card-dark .card-body,
.ratting-manager-dashboard .card.card-dark .card-body,
.ratting-manager-dashboard div.card-body {
    background-color: #0d1016 !important;
    color: #eef2f7 !important;
}

.ratting-manager-dashboard .card.card-dark,
.ratting-manager-dashboard .card-dark {
    background-color: #0d1016 !important;
    border-color: #0d1016 !important;
}

.ratting-manager-dashboard .card-dark .card-header,
.ratting-manager-dashboard .card.card-dark .card-header {
    background-color: transparent !important;
    border-bottom: 1px solid #1b2029 !important;
}

.ratting-manager-dashboard .table {
    color: #eef2f7 !important;
}

.ratting-manager-dashboard .table thead th {
    background-color: transparent !important;
    color: #a7b0be !important;
}

.ratting-manager-dashboard canvas {
    background-color: transparent !important;
}

.ratting-manager-dashboard .rm-chart {
    position: relative;
    height: 300px;
}

.ratting-manager-dashboard .rm-chart-tall {
    position: relative;
    height: 360px;
}

.ratting-manager-dashboard .rm-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.ratting-manager-dashboard .rm-filters select {
    width: auto;
    min-width: 150px;
}
</style>
{{-- The EVE-RO look (black ground, Oxanium, blue and green), after everything above. --}}
<link rel="stylesheet" href="{{ \RattingManager\Support\Asset::url('css/rm-theme.css') }}">
