<link rel="stylesheet" href="{{ \RattingManager\Support\Asset::url('css/ratting-manager.css') }}">
<style>
/* Forced inline as Mining Manager does (in the theme's colours, from rm-theme.css): some SeAT skins lighten cards and
   tables after the stylesheet loads, and the plugin's light text all but
   vanishes on them. */
.ratting-manager-dashboard .tab-content {
    background-color: transparent !important;
}

.ratting-manager-dashboard .card-body,
.ratting-manager-dashboard .card-dark .card-body,
.ratting-manager-dashboard .card.card-dark .card-body,
.ratting-manager-dashboard div.card-body {
    background-color: var(--rm-surface) !important;
    color: var(--rm-text) !important;
}

.ratting-manager-dashboard .card.card-dark,
.ratting-manager-dashboard .card-dark {
    background-color: var(--rm-surface) !important;
    border-color: var(--rm-surface) !important;
}

.ratting-manager-dashboard .card-dark .card-header,
.ratting-manager-dashboard .card.card-dark .card-header {
    background-color: transparent !important;
    border-bottom: 1px solid var(--rm-line) !important;
}

.ratting-manager-dashboard .table {
    color: var(--rm-text) !important;
}

.ratting-manager-dashboard .table thead th {
    background-color: transparent !important;
    color: var(--rm-label) !important;
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
{{-- The page layout and the EVE-RO look (black ground, Oxanium, blue and green), after everything above.
     With the standard SeAT look picked under Settings, the second sheet swaps the colours and typeface. --}}
<link rel="stylesheet" href="{{ \RattingManager\Support\Asset::url('css/rm-theme.css') }}">
@if(app(\RattingManager\Services\Configuration\Settings::class)->get('display.theme') === 'seat')
    <link rel="stylesheet" href="{{ \RattingManager\Support\Asset::url('css/rm-skin-seat.css') }}">
@endif
<script>
// Every period, month, corporation and character list on the plugin's pages
// applies as soon as something is picked, with no Apply button. requestSubmit
// runs the form's own submit handlers, so the Corporation view's picker still
// reloads only that view.
document.addEventListener('change', function (e) {
    var form = e.target && e.target.closest && e.target.closest('form.rm-filters');
    if (!form || e.target.tagName !== 'SELECT') {
        return;
    }
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else {
        form.submit();
    }
});
</script>
