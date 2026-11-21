@include('builder.elements._graphic-css')
@php $c = fn ($v, $d) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $v) ? $v : $d; @endphp
<div data-builder-node="{{ $node['id'] }}" class="wr-node wr-g-tl" style="--wr-tl-line:{{ $c($settings['line_color'] ?? '', '#b8903a') }};--wr-tl-dot:{{ $c($settings['dot_color'] ?? '', '#14382a') }}">
    @foreach ((array) ($node['children'] ?? []) as $child)
        <div class="item{{ ($child['settings']['ongoing'] ?? 'off') === 'on' ? ' ongoing' : '' }}" data-builder-node="{{ $child['id'] }}">
            @if (filled($child['content']['date'] ?? null))<div class="date">{{ $child['content']['date'] }}</div>@endif
            @if (filled($child['content']['title'] ?? null))<h3>{{ $child['content']['title'] }}</h3>@endif
            @if (filled($child['content']['text'] ?? null))<p>{{ $child['content']['text'] }}</p>@endif
        </div>
    @endforeach
</div>
