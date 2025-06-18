@extends('admin.layouts.app')
@section('title')
    <title>Neuer Zimmer</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Neuer Zimmer</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.rooms.add.post')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="room_number" id="room_number" placeholder="Nummer" required />
                            <label for="room_number">Nummer</label>
                        </div>
                        @error('room_number')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="type" id="type" placeholder="Typ" required />
                            <label for="type">Typ</label>
                        </div>
                        @error('type')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="capacity" id="capacity" placeholder="Kapazität" required />
                            <label for="capacity">Kapazität</label>
                        </div>
                        @error('capacity')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{route('admin.rooms')}}">'
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