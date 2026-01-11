@extends('layouts.app')

@section('content')
    @php
        function statusBadge($status) {
            return match($status) {
                'pending'    => 'warning',
                'paid'       => 'info',
                'processing' => 'primary',
                'delivered'  => 'success',
                'canceled'   => 'danger',
                default      => 'secondary',
            };
        }
    @endphp

    <section class="content-main">
        <div class="content-header">
            <div>
                <h2 class="content-title card-title">Order List</h2>
                <p></p>
            </div>
            <div>
                <input type="text" placeholder="Search order ID" class="form-control bg-white">
            </div>
        </div>
        <div class="card mb-4">
            <header class="card-header">
                <div class="row gx-3">
                    <div class="col-lg-4 col-md-6 me-auto">
                        <input type="text" placeholder="Search..." class="form-control">
                    </div>
                    <div class="col-lg-2 col-6 col-md-3">
                        <select class="form-select">
                            <option>Status</option>
                            <option>Active</option>
                            <option>Disabled</option>
                            <option>Show all</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-6 col-md-3">
                        <select class="form-select">
                            <option>Show 20</option>
                            <option>Show 30</option>
                            <option>Show 40</option>
                        </select>
                    </div>
                </div>
            </header>
            <!-- card-header end// -->
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>#ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                            <th scope="col">Date</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>

                                <td>
                                    <strong>{{ data_get($order->meta, 'name', '—') }}</strong>
                                </td>

                                <td>
                                    {{ data_get($order->meta, 'email', '—') }}
                                </td>

                                <td>
                                    {{ number_format($order->amount) }} FCFA
                                </td>

                                <td>
        <span class="badge rounded-pill bg-{{ statusBadge($order->status) }}">
            {{ ucfirst($order->status) }}
        </span>
                                </td>

                                <td>
                                    {{ $order->created_at->format('d/m/Y') }}
                                </td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('orders.show', $order) }}"
                                           class="btn btn-sm btn-primary">
                                            Détail
                                        </a>

                                        <button class="btn btn-sm btn-light dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item"
                                                   href="{{ route('orders.show', $order) }}">
                                                    👁 Voir détail
                                                </a>
                                            </li>

                                            <li><hr class="dropdown-divider"></li>

                                            <li>
                                                <form method="POST"
                                                      action="{{ route('orders.destroy', $order) }}"
                                                      onsubmit="return confirm('Supprimer cette commande ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger">
                                                        🗑 Supprimer
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        </tbody>
                    </table>
                </div>
                <!-- table-responsive //end -->
            </div>
            <!-- card-body end// -->
        </div>
        <!-- card end// -->
        <div class="pagination-area mt-15 mb-50">
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-start">
                    <li class="page-item active"><a class="page-link" href="#">01</a></li>
                    <li class="page-item"><a class="page-link" href="#">02</a></li>
                    <li class="page-item"><a class="page-link" href="#">03</a></li>
                    <li class="page-item"><a class="page-link dot" href="#">...</a></li>
                    <li class="page-item"><a class="page-link" href="#">16</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#"><i class="material-icons md-chevron_right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    </section>

@endsection
