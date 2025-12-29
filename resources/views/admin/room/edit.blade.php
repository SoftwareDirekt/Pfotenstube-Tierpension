@extends('admin.layouts.app')
@section('title')
    <title>Raum aktualisieren</title>
@endsection
@section('extra_css')
<link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
@endsection
@section('body')
<div class="px-4 flex-grow-1 container-p-y">
    <div class="row gy-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Raum aktualisieren</h5>
            </div>
            <div class="card-body">
              <form action="{{route('admin.rooms.update')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="room_number" id="room_number" value="{{$room->number}}" placeholder="Nummer" required />
                            <label for="room_number">Nummer</label>
                        </div>
                        @error('room_number')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="type" id="type" value="{{$room->type}}" placeholder="Typ" required />
                            <label for="type">Typ</label>
                        </div>
                        @error('type')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" name="capacity" id="capacity" value="{{$room->capacity}}" placeholder="Kapazität" required />
                            <label for="capacity">Kapazität</label>
                        </div>
                        @error('capacity')
                            <p class="formError">*{{$message}}</p>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-4">
                            <select name="status" id="status" class="form-control" required>
                                <option value="1" {{ ($room->status == 1) ? 'selected': '' }}>Active</option>
                                <option value="2" {{ ($room->status == 2) ? 'selected': '' }}>In-Active</option>
                            </select>
                            <label for="status">Status</label>
                        </div>
                    </div>
                </div>
                <input type="hidden" class="form-control" name="id" value="{{$room->id}}">
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