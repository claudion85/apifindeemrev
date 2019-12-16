@extends('app', [
    'title' => 'Business'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">{{ $businesses->total() }} Business
                            <a href="/admin/business-export" class="pull-right btn btn-primary btn-fill">
                                <i class="fa fa-cloud-download"></i>
                                Esporta
                            </a>
                        </h4>
                        <p class="category"></p>
                    </div>

                    <div class="pull-right" style="padding: 0 20px">
                        {{ $businesses->links() }}
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
                                <th>Utente</th>
                                <th>Verificato</th>
                                <th>Creato</th>
                                <th>Ultima modifica</th>
                            </thead>
                            <tbody>
                                @foreach ($businesses as $business)
                                <tr>
                                    <!-- <td>{{ $business->_id }}</td> -->
                                    <td><a href="/admin/business/{{ $business->_id }}">{{ $business->name }}</a> ----- <a href="https://www.findeem.com/business/{{ $business->slug }}">view</a></td>
                                    <td>{{ $business->email }}</td>
                                    <td>@if (isset($business->owner->_id))<a href="/admin/users/{{ $business->owner->_id }}">{{ $business->owner->name }}</a>@endif</td>
                                    <td>{{ $business->verified === true ? 'Verificato' : 'Non verificato' }}</td>
                                    <td>{{ $business->created_at }}</td>
                                    <td>{{ $business->updated_at }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                    <div class="text-right" style="padding: 0 20px">
                        {{ $businesses->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
