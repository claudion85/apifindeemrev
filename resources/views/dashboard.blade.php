@extends('app', [
'title' => 'Dashboard'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Dashboard</h4>
                        <p class="category"></p>
                    </div>

                    <div class="content">
                        <h3>{{ $users }} Utenti</h3>
                        <h3>{{ $events }} Eventi</h3>
                        <h3>{{ $groups }} Gruppi</h3>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
