<div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ereignis anzeigen</h5>
                <button type="button" class="btn btn-sm btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="eventShowing">
                    <h4 id="modalTitle"></h4>
                </div>
                <div class="eventEditing" style="display: none;">
                    <div class="row">
                        <div class="col-lg-6">
                            <input class="form-control" id="titleEdit" name="title" type="text" value="">
                        </div>
                        <div class="col-lg-6">
                            <select class="form-control" id="statusEdit" name="status">
                                <option value="Arbeit">Arbeit</option>
                                <option value="Urlaub">Urlaub</option>
                                <option value="Krankenstand">Krankenstand</option>
                                <option value="">Other</option>
                            </select>
                        </div>
                    </div>
                    <h6>Mitarbeiter: </h6>
                    <select class="form-control" id="uidEdit" name="uid">
                        @foreach(App\Models\User::all() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="modalWhen" style="margin-top:5px;"></div>
                <input type="hidden" id="eventID" />
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary eventEditing d-none" id="submitButtonUpdate">Aktualisieren</button>
                <button type="submit" class="btn btn-danger eventShowing" id="deleteButton">Löschen</button>
            </div>
        </div>
    </div>
</div>
