@extends('admin.layouts.app')
@section('title')
    <title>Zusatzkosten aktualisieren</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Zusatzkosten aktualisieren</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.additional-cost.update')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="title" id="title" value="{{$cost->title}}" placeholder="Titel" required />
                            <label for="title">Titel <span class="text-danger">*</span></label>
                        </div>
                        @error('title')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-2">
                            <input type="text" class="form-control" name="price" id="price" value="{{$cost->price}}" placeholder="Preis" required />
                            <label for="price">Preis (Netto) <span class="text-danger">*</span></label>
                        </div>
                        @error('price')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                </div>
                <input type="hidden" class="form-control" name="id" value="{{$cost->id}}">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{route('admin.additional-costs')}}">'
                    <button type="button" class="btn btn-outline-secondary">Stornieren</button>
                </a>
              </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('extra_js')
<script src="assets/vendor/libs/select2/select2.js"></script>
@endsection
