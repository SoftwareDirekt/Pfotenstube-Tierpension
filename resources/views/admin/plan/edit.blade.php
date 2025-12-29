@extends('admin.layouts.app')
@section('title')
    <title>Preisplan aktualisieren</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Preisplan aktualisieren</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.plan.update')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="title" id="title" value="{{$plan->title}}" placeholder="Titel" required />
                            <label for="title">Titel</label>
                        </div>
                        @error('title')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="type" id="type" value="{{$plan->type}}" placeholder="Typ" required />
                            <label for="type">Typ</label>
                        </div>
                        @error('type')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-2">
                            <input type="text" class="form-control" name="price" id="price" value="{{$plan->price}}" placeholder="Preis" required />
                            <label for="price">Preis (Netto)</label>
                        </div>
                        <small class="text-muted mb-4 d-block">
                            <i class="mdi mdi-information-outline"></i> Der Preis ist ohne Mehrwertsteuer. MwSt wird nur hinzugefügt, wenn die Rechnung an die Registrierkasse gesendet wird.
                        </small>
                        @error('price')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-12">
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="flat_rate" {{ ($plan->flat_rate == 1) ? 'checked': '' }} id="flexCheckDefault">
                            <label class="form-check-label" for="flexCheckDefault">
                                Pauschale
                            </label>
                          </div>
                    </div>
                </div>
                <input type="hidden" class="form-control" name="id" value="{{$plan->id}}">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{route('admin.plans')}}">'
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