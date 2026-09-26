@props(['title', 'subtitle' => null])

{{-- Company letterhead with the document's title, shown only on paper (the screen already has the page header). --}}
<x-report-letterhead class="d-none d-print-block" :title="$title" :subtitle="$subtitle" />
