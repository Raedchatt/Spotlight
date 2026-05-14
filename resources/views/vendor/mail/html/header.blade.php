@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<span style="color: #6366f1; font-size: 24px; font-weight: 800; letter-spacing: -0.025em;">
    {!! $slot !!}
</span>
</a>
</td>
</tr>
