@extends('app', [
    'title' => 'Carica Eventi'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">Carica Eventi</h4>
                        <p class="category"></p>
                    </div>

                    <div class="content">

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="file" class="form-control" name="file" value="{{ app('request')->get('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary" type="submit">Carica</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
