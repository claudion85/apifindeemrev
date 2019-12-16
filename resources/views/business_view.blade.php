@extends('app', [
    'title' => excerpt('Business ' . $business->name)
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-user">
                    <div class="image">
                        <img src="{{ $business->cover ?: 'https://www.findeem.com/images/user_background.jpg' }}" alt="..." />
                    </div>
                    <div class="content">
                        <div class="author">
                            <a href="#">
                                <img class="avatar border-gray" src="{{ $business->logo ?: 'https://findeem.ams3.digitaloceanspaces.com/images/avatar_placeholder.png' }}" alt="..." />

                                <h4 class="title">{{ $business->name }}<br />
                                    <small>{{ $business->slug }}</small><br />
                                    <small>{{ $business->email }}</small>
                                </h4>
                            </a>
                        </div>
                        <p class="description text-center">
                            {{ $business->description }}
                        </p>

                        <div>
                            <ul>
                                <li>Visualizzazioni: {{ $business->views->count() }}</li>
                                <li>Utente: <a href="/admin/users/{{ $business->owner->_id }}">{{ $business->owner->name }}</a></li>
                                <li>Eventi: <a href="/admin/events?business={{ $business->_id }}">{{ $business->events->count() }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <button id="deleteUser" href="#" class="btn btn-simple btn-danger"><i class="fa fa-trash"></i> Elimina business</button>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Modifica Business</h4>
                    </div>
                    <div class="content">
                        <form action="" method="POST">
                            <div class="row">
                                <div class="col-md-12">
                                    <img style="max-width: 100%; max-height: 200px" src="" alt="">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Nome</label>
                                        <p>{{ $business->name }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Slug</label>
                                        <p>{{ $business->slug }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <p>{{ $business->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Creato</label>
                                        <p>{{ $business->created_at }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Ultima Modifica</label>
                                        <p>{{ $business->updated_at }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Business Verificato?</label>
                                        <select class="form-control" name="verified">
                                            <option @if ($business->verified === true) selected @endif value="Y">Si</option>
                                            <option @if ($business->verified === false) selected @endif value="N">No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-info btn-fill pull-right">Salva Business</button>
                            <div class="clearfix"></div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('page_scripts')
<script>
    $('#deleteUser').on('click', function () {
        window.confirm('Sei sicuro di voler elimnare questo utente? Questa azione non è reversibile e rimuove tutti i dati creati da questo utente, inclusi eventi, gruppi, rating, interazioni (commenti, interested, likes ...), visualizzazioni ...')
    })
</script>
@endsection
