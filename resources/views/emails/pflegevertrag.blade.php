@extends('emails.layouts.pfotenstube')

@section('title', 'Ihre Pflegevereinbarung – Pfotenstube')

@section('preheader')
    Ihre Pflegevereinbarung für {{ $dogName }} ist im Anhang enthalten.
@endsection

@section('badge')
    <span style="display:inline-block;padding:6px 14px;background-color:#CDA275;color:#FEF5E8;font-family:'Segoe UI',Roboto,Arial,sans-serif;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;border-radius:4px;">
        Pflegevereinbarung
    </span>
@endsection

@section('heading', 'Guten Tag')

@section('content')
    <p style="margin:0 0 16px;">
        anbei erhalten Sie die Pflegevereinbarung für den Aufenthalt von
        <strong style="color:#6D381A;">{{ $dogName }}</strong>.
    </p>
    <p style="margin:0;">
        Der Pflegevertrag ist als PDF im Anhang enthalten.
    </p>
@endsection

