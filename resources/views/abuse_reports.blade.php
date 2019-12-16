@extends('app', [
    'title' => 'Segnalazioni'
])

@section('content')

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header">
                        <h4 class="title">{{ $reports->total() }} Segnalazioni</h4>
                        <p class="category"></p>
                    </div>

                    <div class="content table-responsive table-full-width">
                        <table class="table table-hover table-striped">
                            <thead>
                                <!-- <th>ID</th> -->
                                <th>Entità</th>
                                <th>Utente</th>
                                <th>Motivo</th>
                                <th>Creato</th>
                            </thead>
                            <tbody>
                                @foreach ($reports as $report)
                                <tr>
                                    <!-- <td><a href="/admin/reports/{{ $report->_id }}">{{ $report->_id }}</a></td> -->
                                    <td>
                                        @if ($report->entity_type === 'events')
                                        Evento <a href="/admin/events/{{ $report->entity->_id }}">{{ $report->entity->name }}</a>
                                        @elseif ($report->entity_type === 'groups')
                                        Gruppo <a href="/admin/groups/{{ $report->entity->_id }}">{{ $report->entity->name }}</a>
                                        @elseif ($report->entity_type === 'users')
                                        Utente <a href="/admin/users/{{ $report->entity->_id }}">{{ $report->entity->name }}</a>
                                        @elseif ($report->entity_type === 'groups')
                                        Business <a href="/admin/business/{{ $report->entity->_id }}">{{ $report->entity->name }}</a>
                                        @elseif ($report->entity_type === 'comments')
                                        Commento {{ $report->entity->_id }}
                                        @elseif ($report->entity_type === 'groups')
                                        Messaggio {{ $report->entity->_id }}
                                        @endif
                                    </td>
                                    <td>@if (isset($report->user->_id))<a href="/admin/users/{{ $report->user->_id }}">{{ $report->user->name }}</a>@endif</td>
                                    <td>{{ $report->reason }}</td>
                                    <td>{{ $report->created_at }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                    <div class="text-right" style="padding: 0 20px">
                        {{ $reports->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
