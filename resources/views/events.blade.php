@extends('app', [
    'title' => 'Eventi'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">{{ $events->total() }} Eventi
                            <a href="/admin/events-export" class="pull-right btn btn-primary btn-fill">
                                <i class="fa fa-cloud-download"></i>
                                Esporta
                            </a>
                        </h4>
                        <p class="category"></p>
                    </div>

                    <div class="pull-right" style="padding: 0 20px">
                        {{ $events->links() }}
                    </div>

                    <div style="padding: 0 20px">
                        <form action="">
                            @if (app('request')->filled('owner'))
                            <input type="hidden" name="owner" value="{{ app('request')->get('owner') }}">
                            @endif
                            @if (app('request')->filled('main_category'))
                            <input type="hidden" name="main_category" value="{{ app('request')->get('main_category') }}">
                            @endif
                            @if (app('request')->filled('sub_category'))
                            <input type="hidden" name="sub_category" value="{{ app('request')->get('sub_category') }}">
                            @endif
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
                                <th>Titolo</th>
                                <th>Categoria</th>
                                <th>Luogo</th>
                                <th>Creato da</th>
                                <th>Date</th>
                                <th>Creato</th>
                                <th>Ultima modifica</th>
                            </thead>
                            <tbody>
                                @foreach ($events as $event)
                                <tr>
                                    <td>
                                        <a href="/admin/events/{{ $event->_id }}">{{ $event->name }}</a>
                                        -----
                                        <a target="_blank" href="https://findeemfront.ddns.net/events/{{ $event->slug }}">view</a>
                                    </td>
                                    <td>
                                        @if (isset($event->main_category->_id))
                                            <a href="/admin/categories/{{ $event->main_category->_id }}">{{ $event->main_category->name }}</a>
                                        @endif
                                        @if (isset($event->sub_category->_id))
                                            / <a href="/admin/categories/{{ $event->sub_category->_id }}">{{ $event->sub_category->name }}</a>
                                        @endif
                                    </td>
                                    <td title="{{ $event->address }}">{{ excerpt($event->address, 40) }}</td>
                                    <td><a href="/admin/users/{{ $event->owner->_id }}">{{ $event->owner->name }}</a></td>
                                    <!-- <td>{{ substr($event->description, 0, 100) }}...</td> -->
                                    <td>
                                        @if ($event->start_date && $event->end_date)
                                            {{ $event->start_date->format('d/m/y H:i') }}<br>{{ $event->end_date->format('d/m/y H:i') }}
                                            @if (\Carbon\Carbon::now()->gt($event->end_date))
                                            <br><span class="text-danger">Terminato</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $event->created_at->format('d/m/y H:i:s') }}</td>
                                    <td>{{ $event->updated_at->format('d/m/y H:i:s') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                    <div class="text-right" style="padding: 0 20px">
                        {{ $events->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
