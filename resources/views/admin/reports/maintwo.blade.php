@extends('admin.layouts.app')

@section('title')
    <title>Verkaufsbericht</title>
@endsection

@section('extra_css')
<style>
     button {
      margin-top: 5px;
      padding: 5px 10px;
      background-color: #f4f4f4;
      color: #007bff;
      border: none;
      border-radius: 4px;
      cursor: pointer;

    }
    .month-navigation {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    font-family: Arial, sans-serif;
    align-items:center;
}

 .btn-arrow{
  margin:0;
 }


.month-display {
    font-size: 18px;
    font-weight: bold;
    margin: 0 50px;
}

.sales-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.ytd-box {
    min-width: 320px;
    max-width: 420px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    padding: 10px 12px;
}

.ytd-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 8px;
}

.ytd-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    padding: 2px 0;
}

.ytd-total {
    border-top: 1px solid #e5e7eb;
    margin-top: 8px;
    padding-top: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #198754;
}

</style>

@endsection
@section('body')


<div class="px-4 flex-grow-1 container-p-y">
    <div class="sales-header">
        <div class="month-navigation">
            <form action="{{route('admin.sales')}}" method="GET">
              <input type="hidden" name="year" value="{{ $currentYear }}">
              <input type="hidden" name="month" value="{{ $currentMonth }}">
              <input type="hidden" name="direction" value="prev">

              <button type="submit" class="btn-arrow"><i class="fas fa-arrow-left"></i></button>
            </form>
            <span id="monthDisplay "class="month-display text-danger">{{$deMonth}}</span>
            <form action="{{ route('admin.sales') }}" method="GET">
              <input type="hidden" name="year" value="{{ $currentYear }}">
              <input type="hidden" name="month" value="{{ $currentMonth }}">
              <input type="hidden" name="direction" value="next">
              <button type="submit" class="btn-arrow"><i class="fas fa-arrow-right"></i></button>
            </form>
        </div>

        <div class="ytd-box">
            <div class="ytd-title">Umsatz {{ $ytdYear }} (Jahresbeginn bis heute)</div>
            @foreach($ytdMonthlyTotals as $entry)
                <div class="ytd-row">
                    <span>{{ $entry['month'] }}</span>
                    <span>{{ number_format($entry['total'], 2, ',', '.') }} €</span>
                </div>
            @endforeach
            <div class="ytd-row ytd-total">
                <span>Gesamt</span>
                <span>{{ number_format($ytdTotal, 2, ',', '.') }} €</span>
            </div>
        </div>
    </div>

    <div class="container my-4"><!-- Sales Table -->
        <table class="table table-bordered text-center">
            <thead>
                <tr >
                    <th>KW</th>
                    <th>Datum</th>
                    <th>Betrag (€)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesData as $week => $days)
                    <tr>
                        <td rowspan="{{ count($days) + 1 }}">{{ $week }}</td>
                        @foreach($days as $index => $day)
                            @if($index === 0)
                                <td>{{ $day['date'] }}</td>
                                <td>{{ number_format($day['amount'], 2) }} €</td>
                    </tr>
                            @else
                    <tr>
                                <td>{{ $day['date'] }}</td>
                                <td>{{ number_format($day['amount'], 2) }} €</td>
                    </tr>
                            @endif
                        @endforeach
                    <tr class="bg-success">
                        <td colspan="2" class="weekly-summary text-white fw-bolder fs-5">Wochensumme</td>
                        <td class="weekly-summary fw-bolder text-white fs-5">{{ number_format($weeklyTotals[$week], 2) }} €</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>

                <tr >
                    <td></td>
                    <td colspan="2" class=" bg-danger monthly-summary text-center text-white fw-bolder fs-5">Monatssumme</td>
                    <td class="bg-danger monthly-summary text-center text-white fw-bolder fs-5">{{ number_format($monthlyTotal, 2) }} €</td>
                </tr>
            </tfoot>
        </table>
    </div>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
   </body>
</html>
</div>
@endsection
