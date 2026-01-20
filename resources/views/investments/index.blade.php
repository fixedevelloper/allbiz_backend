@extends('layouts.app')

@section('content')
    <div class="content-header">
        <div>
            <h2 class="content-title card-title">Investissements</h2>
            <p></p>
        </div>
        <div>
            {{--            <a href="#" class="btn btn-light rounded font-md">Export</a>
                        <a href="#" class="btn btn-light rounded font-md">Import</a>--}}
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm rounded">Ajouter un product</a>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Montant</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($investments as $investment)
                    <tr>
                        <td width="40%">
                            <a href="#" class="itemside">
                                <div class="left">
                                    <img src="assets/imgs/people/avatar-1.png" class="img-sm img-avatar" alt="Userpic">
                                </div>
                                <div class="info pl-3">
                                    <h6 class="mb-0 title">{{ $investment->user->phone }}</h6>
                                    <small class="text-muted">Montant: {{ number_format($investment->amount) }} FCFA</small>
                                </div>
                            </a>
                        </td>
                        <td>eleanor2020@example.com</td>
                        <td><span class="badge rounded-pill alert-success">Active</span></td>
                        <td>{{ $investment->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <a href="#" class="btn btn-sm btn-brand rounded font-sm mt-15">View details</a>
                        </td>
                    </tr>

                @endforeach
                </tbody>
            </table>
        </div>

    </div>
@endsection
