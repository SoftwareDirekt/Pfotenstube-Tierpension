<div class="modal fade" id="createEventModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ereignis hinzufügen</h5>
                <button type="button" class="btn btn-sm btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-6">
                        <input class="form-control" readonly id="title" name="title" type="text" value="">
                    </div>
                    <div class="col-lg-6">
                        <select class="form-control" id="statusd" name="status">
                            <option value="Arbeit">Arbeit</option>
                            <option value="Urlaub">Urlaub</option>
                            <option value="Krankenstand">Krankenstand</option>
                            <option value="">Other</option>
                        </select>
                    </div>
                </div>

                <h6>Mitarbeiter: </h6>
                <select class="form-control" id="uid" name="uid">
                    @foreach(App\Models\User::all() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>

                <div id="modalWhenAdd" style="margin-top:5px;"></div>
                <input type="hidden" id="startTime" />
                <input type="hidden" id="endTime" />
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="submitButton">Hinzufügen</button>
            </div>
        </div>
    </div>
</div>
