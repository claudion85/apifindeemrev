@extends('app', [
    'title' => 'Utenti'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title"><span>{{ $users->total() }}</span> Utenti
                            <a href="/admin/users-export" class="pull-right btn btn-primary btn-fill">
                                <i class="fa fa-cloud-download"></i>
                                Esporta
                            </a>
                        </h4>
                        <p class="category">
                        </p>
                    </div>

                    <div class="pull-right" style="padding: 0 20px">
                        {{ $users->links() }}
                    </div>

                    <div style="padding: 0 20px">
                        <form action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="search" value="{{ app('request')->get('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary" type="submit">Cerca</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="content table-responsive table-full-width">
                        <table class="table table-hover table-striped">
                            <thead>
                                <!-- <th>ID</th> -->
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Nome utente</th>
                                <th>Stato</th>
                                <th>Creato</th>
                                <th>Ultima modifica</th>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                <tr>
                                    <!-- <td>{{ $user->_id }}</td> -->
                                    <td><a href="/admin/users/{{ $user->_id }}">{{ $user->name }}</a></td>
                                    <td>{{ $user->email }}</td>
                                    <td><a target="_blank" href="https://www.findeem.com/users/{{ $user->username }}">{{ $user->username }}</a></td>
                                    <td>{{ $user->status === 1 ? 'Attivo' : 'Disabilitato' }}</td>
                                    <td>{{ $user->created_at }}</td>
                                    <td>{{ $user->updated_at }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                    <div class="text-right" style="padding: 0 20px">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
