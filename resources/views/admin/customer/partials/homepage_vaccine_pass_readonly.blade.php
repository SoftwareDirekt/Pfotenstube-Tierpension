{{-- Impfpass uploads from customer portal: view only, no edit/delete in Tierpension admin --}}
@php
    $p1 = $dog->vaccine_pass_page1 ?? null;
    $p2 = $dog->vaccine_pass_page2 ?? null;
    $show = filled($p1) || filled($p2);
@endphp
@if($show)
    <div class="alert alert-secondary mb-3 border" role="region" aria-label="Impfpass Kundenportal">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h6 class="alert-heading mb-1">Impfpass (Kundenportal)</h6>
            </div>
        </div>
        <div class="row g-3">
            @if(filled($p1))
                @php $url1 = $dog->vaccinePassPublicPath($p1); @endphp
                <div class="col-md-6">
                    <p class="small fw-semibold mb-2 text-body-secondary">Seite 1</p>
                    @if(preg_match('/\.pdf$/i', $p1))
                        <a href="{{ $url1 }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-file-pdf"></i> PDF anzeigen
                        </a>
                    @else
                        <a href="{{ $url1 }}" target="_blank" rel="noopener noreferrer" class="d-inline-block">
                            <img src="{{ $url1 }}" alt="Impfpass Seite 1" class="img-fluid rounded border" style="max-height:260px;">
                        </a>
                    @endif
                </div>
            @endif
            @if(filled($p2))
                @php $url2 = $dog->vaccinePassPublicPath($p2); @endphp
                <div class="col-md-6">
                    <p class="small fw-semibold mb-2 text-body-secondary">Seite 2</p>
                    @if(preg_match('/\.pdf$/i', $p2))
                        <a href="{{ $url2 }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-file-pdf"></i> PDF anzeigen
                        </a>
                    @else
                        <a href="{{ $url2 }}" target="_blank" rel="noopener noreferrer" class="d-inline-block">
                            <img src="{{ $url2 }}" alt="Impfpass Seite 2" class="img-fluid rounded border" style="max-height:260px;">
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
