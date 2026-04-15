@extends('admin.layouts.app')
@section('title')
    <title>Neue Zusatzkosten</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Neue Zusatzkosten</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.additional-cost.add.post')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="title" id="title" placeholder="Titel" required />
                            <label for="title">Titel <span class="text-danger">*</span></label>
                        </div>
                        @error('title')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-2">
                            <input type="text" class="form-control" name="price" id="price" placeholder="Preis" required />
                            <label for="price">Preis (Netto) <span class="text-danger">*</span></label>
                        </div>
                        @error('price')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                </div>
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
