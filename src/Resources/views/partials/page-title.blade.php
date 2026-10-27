{{--
    The left half of a page header in the EVE-RO style: a small spaced
    eyebrow, the big title (usually the period, "SEP. 2026") and a subtitle.
    The page puts it in a .rm-page-head next to its own .rm-page-actions.
--}}
<div>
    <div class="rm-eyebrow">{{ $eyebrow }}</div>
    <h1 class="rm-title">{{ $title }}</h1>
    @if(!empty($subtitle))
        <p class="rm-subtitle">{{ $subtitle }}</p>
    @endif
</div>
