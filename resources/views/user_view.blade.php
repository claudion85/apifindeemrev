@extends('app', [
    'title' => excerpt('Utente ' . $user->name)
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-user">
                    <div class="image">
                        <img src="{{ $user->background ?: 'https://www.findeem.com/images/user_background.jpg' }}" alt="..." />
                    </div>
                    <div class="content">
                        <div class="author">
                            <a href="#">
                                <img class="avatar border-gray" src="{{ $user->avatar ?: 'https://findeem.ams3.digitaloceanspaces.com/images/avatar_placeholder.png' }}" alt="..." />

                                <h4 class="title">{{ $user->name }}<br />
                                    <small>{{ $user->username }}</small><br />
                                    <small>{{ $user->email }}</small>
                                </h4>
                            </a>
                        </div>
                        <p class="description text-center">
                            {{ $user->bio }}
                        </p>

                        <div>
                            <ul>
                                <li>Visualizzazioni profilo: {{ $user->views->count() }}</li>
                                <li>Pagine business: {{ $user->businesses->count() }}</li>
                                <li>Eventi: <a href="/admin/events?owner={{ $user->_id }}">{{ $user->events->count() }}</a></li>
                                <li>Gruppi: <a href="/admin/groups?owner={{ $user->_id }}">{{ $user->groups->count() }}</a></li>
                                <li>Interazioni eventi: {{ $user->events_interactions->count() }}</li>
                                <li>Interazioni gruppi: {{ $user->groups_interactions->count() }}</li>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <button id="deleteUser" href="#" class="btn btn-simple btn-danger"><i class="fa fa-trash"></i> Elimina utente</button>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Modifica Utente</h4>
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
                                        <p>{{ $user->name }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <p>{{ $user->username }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <p>{{ $user->email }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Email Verificata?</label>
                                        <p>{{ $user->email_verified === 'Y' ? 'Si' : 'No' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Registrato tramite</label>
                                        <p>{{ ucfirst($user->service) }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Profilo</label>
                                        <p>{{ $user->visibility === 'public' ? 'Pubblico' : 'Privato' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Creato</label>
                                        <p>{{ $user->created_at }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Ultima Modifica</label>
                                        <p>{{ $user->updated_at }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Stato</label>
                                        <select class="form-control" name="status">
                                            <option @if ($user->status == 1) selected @endif value="1">Attivo</option>
                                            <option @if ($user->status == 0) selected @endif value="0">Disabilitato</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-info btn-fill pull-right">Salva Utente</button>
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
