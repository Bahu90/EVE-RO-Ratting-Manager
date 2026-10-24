<link rel="stylesheet" href="{{ asset('vendor/ratting-manager/css/ratting-manager.css') }}?v=1">
<style>
/* Forced inline as Mining Manager does: some SeAT skins lighten cards and
   tables after the stylesheet loads, and the plugin's light text all but
   vanishes on them. */
.ratting-manager-dashboard .tab-content {
    background-color: #0f1115 !important;
}

.ratting-manager-dashboard .card-body,
.ratting-manager-dashboard .card-dark .card-body,
.ratting-manager-dashboard .card.card-dark .card-body,
.ratting-manager-dashboard div.card-body {
    background-color: #161922 !important;
    color: #e8e8e8 !important;
}

.ratting-manager-dashboard .card.card-dark,
.ratting-manager-dashboard .card-dark {
    background-color: #161922 !important;
    border-color: #2c3138 !important;
}

.ratting-manager-dashboard .card-dark .card-header,
.ratting-manager-dashboard .card.card-dark .card-header {
    background-color: #1a1d24 !important;
    border-bottom: 1px solid #2c3138 !important;
}

.ratting-manager-dashboard .table {
    color: #e8e8e8 !important;
}

.ratting-manager-dashboard .table thead th {
    background-color: #1a1d24 !important;
    color: #ffffff !important;
}

.ratting-manager-dashboard canvas {
    background-color: rgba(26, 29, 36, 0.5) !important;
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
