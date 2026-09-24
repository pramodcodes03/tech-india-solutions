{{--
    The heading every statutory register shares: form number, the rule it is
    made under, the title, the Act, then the establishment identity block.

    Rendered to a string by the layout so it can be dropped either into a
    page-fixed div (registers that run for pages) or inline (Form C, which
    starts a fresh page per employee and so repeats the block itself).
--}}
<div class="form-head">
    <div class="form-no">{{ $formNo }}</div>
    @if($formRule)<div class="form-rule">{{ $formRule }}</div>@endif
    <div class="form-title">{{ $formTitle }}</div>
    @if($formAct)<div class="form-act">{{ $formAct }}</div>@endif
</div>

<table class="est">
    <tr>
        <td class="k">Name and Address of the Establishment</td>
        <td>
            <div class="val">{{ $establishment['name'] }}</div>
            <div>{{ $establishment['address'] }}</div>
        </td>
    </tr>
</table>

@if($formMeta)
    {!! $formMeta !!}
@endif
