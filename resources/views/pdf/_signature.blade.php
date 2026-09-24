{{-- The authorised-signature and company-seal block.

     Printed on letters, vouchers and slips. The seal sits to the left of the
     signature so a scanned round stamp does not overlap the signatory's name. --}}
<table class="sign-wrap">
    <tr>
        <td>
            @if($hasSeal)
                <div class="seal-img"><img src="{{ $sealPath }}" alt="" /></div>
            @endif
        </td>
        <td class="sign-box">
            <div class="sign-img">
                @if($hasSignature)<img src="{{ $signaturePath }}" alt="" />@endif
            </div>
            <div class="sign-line">
                <div class="sign-name">{{ $business?->signatory_name ?: 'Authorised Signatory' }}</div>
                @if($business?->signatory_role)
                    <div class="sign-role">{{ $business->signatory_role }}</div>
                @endif
                <div class="sign-role">For {{ $business?->legal_name ?: ($business?->name ?: 'the Company') }}</div>
            </div>
        </td>
    </tr>
</table>
