<aside class="navbar-aside" id="offcanvas_aside">
    <div class="aside-top">
        <a href="{{route('dashboard')}}" class="brand-wrap">
            <img src="{{asset('assets/imgs/theme/logo.png')}}" class="logo" alt="Nest Dashboard" />
        </a>
        <div>
            <button class="btn btn-icon btn-aside-minimize"><i class="text-muted material-icons md-menu_open"></i></button>
        </div>
    </div>
    <nav>
        <ul class="menu-aside">

            <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a class="menu-link" href="{{ route('dashboard') }}">
                    <i class="icon material-icons md-home"></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>

            <hr />

            <li class="menu-item {{ request()->routeIs('investments.*') ? 'active' : '' }}">
                <a class="menu-link" href="{{ route('investments.index') }}">
                    <i class="icon material-icons md-attach_money"></i>
                    <span class="text">Investissement</span>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('roulettes.*') ? 'active' : '' }}">
                <a class="menu-link" href="{{ route('roulettes.index') }}">
                    <i class="icon material-icons md-games"></i>
                    <span class="text">Roulette</span>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <a class="menu-link" href="{{ route('users.index') }}">
                    <i class="icon material-icons md-people"></i>
                    <span class="text">Utilisateurs</span>
                </a>
            </li>

            <hr />

            {{-- PRODUITS + SOUS-MENU --}}
            <li class="menu-item has-submenu {{ request()->routeIs('products.*','categories.*') ? 'active open' : '' }}">
                <a class="menu-link" href="#">
                    <i class="icon material-icons md-shopping_bag"></i>
                    <span class="text">Products</span>
                </a>

                <div class="submenu"
                     style="{{ request()->routeIs('products.*','categories.*') ? 'display:block' : '' }}">
                    <a class="{{ request()->routeIs('products.*') ? 'active' : '' }}"
                       href="{{ route('products.index') }}">
                        Product List
                    </a>

                    <a class="{{ request()->routeIs('categories.*') ? 'active' : '' }}"
                       href="{{ route('categories.index') }}">
                        Categories
                    </a>
                </div>
            </li>

            <li class="menu-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <a class="menu-link" href="{{ route('orders.index') }}">
                    <i class="icon material-icons md-shopping_cart"></i>
                    <span class="text">Orders</span>
                </a>
            </li>

        </ul>

        <hr />
        <ul class="menu-aside">
            <li class="menu-item has-submenu">
                <a class="menu-link" href="#">
                    <i class="icon material-icons md-settings"></i>
                    <span class="text">Settings</span>
                </a>
                <div class="submenu">
                    <a href="{{route('countries.index')}}">Countries</a>
                    <a href="{{route('operators.create')}}">Operators</a>
                </div>
            </li>

        </ul>
        <br />
        <br />
    </nav>
</aside>
