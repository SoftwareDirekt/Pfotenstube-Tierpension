@php
  $permissions = [
    'dashboard' => true,
    'rooms' => true,
    'customers' => true,
    'reservations' => true,
    'payments' => true,
    'invoices' => true,
    'employees' => true,
    'report' => true,
    'tasks' => true,
    'plans' => true,
    'additional_costs' => true,
    'rankings' => true,
    'calendar' => true,
    'dog_calendar' => true,
    'vandv' => true,
  ];

  // PIN and page permission lock is intentionally disabled:
  // all menu entries stay visible for all users.

@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
      <a href="/" class="app-brand-link">
        @php
            $user = Auth::user();
            $userPicture = $user && $user->picture != 'no-user-picture.gif' ? asset('uploads/users/' . $user->picture) : asset('assets/img/avatars/1.png');
        @endphp
        @if($userPicture)
          <div class="app-brand-logo demo me-2" id="sidebarUserPicture">
              <img src="{{ $userPicture }}" alt="User" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;" />
          </div>
        @endif
        
        <span class="app-brand-text demo menu-text fw-bold ms-2">Tierpension</span>
      </a>

      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto" id="togglerMenuBy">
        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M11.4854 4.88844C11.0081 4.41121 10.2344 4.41121 9.75715 4.88844L4.51028 10.1353C4.03297 10.6126 4.03297 11.3865 4.51028 11.8638L9.75715 17.1107C10.2344 17.5879 11.0081 17.5879 11.4854 17.1107C11.9626 16.6334 11.9626 15.8597 11.4854 15.3824L7.96672 11.8638C7.48942 11.3865 7.48942 10.6126 7.96672 10.1353L11.4854 6.61667C11.9626 6.13943 11.9626 5.36568 11.4854 4.88844Z"
            fill="currentColor"
            fill-opacity="0.6" />
          <path
            d="M15.8683 4.88844L10.6214 10.1353C10.1441 10.6126 10.1441 11.3865 10.6214 11.8638L15.8683 17.1107C16.3455 17.5879 17.1192 17.5879 17.5965 17.1107C18.0737 16.6334 18.0737 15.8597 17.5965 15.3824L14.0778 11.8638C13.6005 11.3865 13.6005 10.6126 14.0778 10.1353L17.5965 6.61667C18.0737 6.13943 18.0737 5.36568 17.5965 4.88844C17.1192 4.41121 16.3455 4.41121 15.8683 4.88844Z"
            fill="currentColor"
            fill-opacity="0.38" />
        </svg>
      </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
      @if($permissions['dashboard'])
      <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active open' : '' }}">
        <a href="{{route('admin.dashboard')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-view-dashboard-outline"></i>
          <div data-i18n="Dashboard">Armaturenbrett</div>
        </a>
      </li>
      @endif

      <li class="menu-header fw-light mt-3">
        <span class="menu-header-text">Tierverwaltung</span>
      </li>

      @if($permissions['customers'])
      <li class="menu-item">
        <a href="{{route('admin.customers')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-dog"></i>
          <div>Kunde ({{$total_customers}}/{{$total_dogs}})</div>
        </a>
      </li>
      @endif

      @if($permissions['rooms'])
      <li class="menu-item">
        <a href="{{route('admin.rooms')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-home-outline"></i>
          <div>Zimmer</div>
        </a>
      </li>
      @endif

      @if($permissions['reservations'])
      <li class="menu-item">
        <a href="{{route('admin.dogs.in.rooms')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-paw"></i>
          <div>Übersicht Tiere</div>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{route('admin.reservation', ['sl' => 3])}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-swap-horizontal"></i>
          <div>Reservierung ({{$total_reservations_count}})</div>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{route('admin.reservation.homepage.pending')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-web"></i>
          <div>Pfotenstube-Anfragen</div>
        </a>
      </li>
      @endif

      @if($permissions['dog_calendar'])
      <li class="menu-item">
        <a href="{{route('admin.dog.calendar')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-calendar-heart"></i>
          <div>HundeKalender</div>
        </a>
      </li>
      @endif

      @if($permissions['vandv'])
      <li class="menu-item">
        <a href="{{route('admin.v_v')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-dog"></i>
          <div>V&amp;V</div>
        </a>
      </li>
      @endif

      <li class="menu-header fw-light mt-3">
        <span class="menu-header-text">Finanzen</span>
      </li>

      @if($permissions['payments'])
      <li class="menu-item">
        <a href="{{route('admin.payment')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-currency-eur"></i>
          <div>Zahlung</div>
        </a>
      </li>
      @endif

      @if($permissions['invoices'])
      <li class="menu-item">
        <a href="{{route('admin.invoices')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-file-document-outline"></i>
          <div>Rechnungen</div>
        </a>
      </li>
      @endif

      @if($permissions['plans'])
      <li class="menu-item">
        <a href="{{route('admin.plans')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-credit-card-outline"></i>
          <div>Preisplan</div>
        </a>
      </li>
      @endif

      @if($permissions['additional_costs'])
      <li class="menu-item">
        <a href="{{route('admin.additional-costs')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-tag-outline"></i>
          <div>Zusatzkosten</div>
        </a>
      </li>
      @endif

      <li class="menu-header fw-light mt-3">
        <span class="menu-header-text">Tägliche Aufgaben</span>
      </li>

      @if($permissions['tasks'])
      <li class="menu-item">
        <a href="{{route('admin.tasks')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-checkbox-marked-outline"></i>
          <div>Aufgaben hinzufugen</div>
        </a>
      </li>
      @endif

      @if($permissions['rankings'])
      <li class="menu-item">
        <a href="{{route('admin.dog.ranks')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-magnify"></i>
          <div>Hunderanking</div>
        </a>
      </li>
      @endif

      <li class="menu-header fw-light mt-3">
        <span class="menu-header-text">Personal</span>
      </li>

      @if(Auth::user()->permissions == null)
      <li class="menu-item">
        <a href="{{route('admin.employee.track')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-clock-outline"></i>
          <div>Arbeitszeiterfassung</div>
        </a>
      </li>
      @endif

      <li class="menu-item">
        <a href="{{route('admin.employee.track.monatsplan')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-calendar-month-outline"></i>
          <div>Monatsplan</div>
        </a>
      </li>

      @if($permissions['calendar'])
      <li class="menu-item">
        <a href="{{route('admin.calendar')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-calendar-outline"></i>
          <div>Kalender</div>
        </a>
      </li>
      @endif

      <li class="menu-header fw-light mt-3">
        <span class="menu-header-text">Verwaltung</span>
      </li>

      @if($permissions['employees'])
      <li class="menu-item">
        <a href="{{route('admin.employees')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-account-cog-outline"></i>
          <div>Mitarbeiter</div>
        </a>
      </li>
      @endif

      @if($permissions['report'])
      <li class="menu-item">
        <a href="{{route('admin.sales', ['year' => now()->year, 'month' => now()->month])}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-chart-bar"></i>
          <div>Verkaufsbericht</div>
        </a>
      </li>
      @endif

      <li class="menu-header fw-light mt-3">
        <span class="menu-header-text">Einstellungen</span>
      </li>

      <li class="menu-item">
        <a href="{{route('admin.settings')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-cog"></i>
          <div>Profil</div>
        </a>
      </li>

      @if(true)
      <li class="menu-item">
        <a href="{{route('admin.logout')}}" class="menu-link">
          <i class="menu-icon tf-icons mdi mdi-logout"></i>
          <div>Ausloggen</div>
        </a>
      </li>
      @endif
    </ul>
</aside>
