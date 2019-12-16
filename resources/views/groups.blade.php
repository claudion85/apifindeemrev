@extends('app', [
    'title' => 'Gruppi'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">{{ $groups->total() }} Gruppi</h4>
                        <p class="category"></p>
                    </div>

                    <div class="pull-right" style="padding: 0 20px">
                        {{ $groups->links() }}
                    </div>

                    <div style="padding: 0 20px">
                        <form action="">
                            @if (app('request')->filled('owner'))
                            <input type="hidden" name="owner" value="{{ app('request')->get('owner') }}">
                            @endif
                            @if (app('request')->filled('type'))
                            <input type="hidden" name="type" value="{{ app('request')->get('type') }}">
                            @endif
                            @if (app('request')->filled('event'))
                            <input type="hidden" name="event" value="{{ app('request')->get('event') }}">
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
                                <!-- <th>ID</th> -->
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Visibilità</th>
                                <th>Creato da</th>
                                <th>Evento</th>
                                <th>Creato</th>
                                <th>Ultima modifica</th>
                            </thead>
                            <tbody>
                                @foreach ($groups as $group)
                                <tr>
                                    <!-- <td>{{ $group->_id }}</td> -->
                                    <td>
                                        {{-- <a href="/admin/groups/{{ $group->_id }}">{{ $group->name }}</a> --}}
                                        {{ $group->name }}
                                        -----
                                        <a target="_blank" href="https://www.findeem.com/events/{{ $group->event->slug }}/groups/{{ $group->_id }}">view</a>
                                    </td>
                                    <td>
                                        <a href="/admin/groups?type={{ $group->type }}">
                                            {{ $group->type }}
                                            <i class="fa fa-filter"></i>
                                        </a>
                                    </td>
                                    <td>{{ $group->visibility }}</td>
                                    <td><a href="/admin/users/{{ $group->owner->_id }}">{{ $group->owner->name }}</a></td>
                                    <td><a href="/admin/events/{{ $group->event->_id }}">{{ $group->event->name }}</a></td>
                                    <td>{{ $group->created_at->format('d/m/y H:i:s') }}</td>
                                    <td>{{ $group->updated_at->format('d/m/y H:i:s') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                    <div class="text-right" style="padding: 0 20px">
                        {{ $groups->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
