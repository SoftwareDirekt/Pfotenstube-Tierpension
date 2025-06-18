{{-- resources/views/sales/report.blade.php --}}

@extends('admin.layouts.app')

@section('title')
    <title>Verkaufsbericht</title>
@endsection
@section('body')

    <div class="px-4 flex-grow-1 container-p-y">
        <div class="row gy-4">
            <div class="card">
                <div class="row">
                    <div class="col-md-4 my-2">
                        <h5 class="card-header">
                            <h1>{{ $pageName }}
                                @isset($year)
                                    @isset($month)
                                        ({{ \App\Http\Controllers\ReportController::monthNames($month) }}-{{ $year }})
                                    @else
                                        ({{ $year }})
                                    @endisset
                                @endisset
                            </h1>
                            <small>{{ $reportName }} @if($showWeekly) & Wochenumsätze @endif</small>
                        </h5>
                    </div>
                    <hr>
                    <form class="row">
                        <div class="col-md-8">
                            <div class="form-floating form-floating-outline my-3">
                                <select name="year" class="form-control form-control-lg">
                                    @for ($i = 2022; $i <= date("Y"); $i++)
                                        <option value="{{ $i }}" {{ $i == $year ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline my-3">
                                <input type="hidden" name="type" value="monthly">
                                <button class="btn btn-sm btn-primary w-100" style="padding: 15px 0" type="submit" name="search" value="search">Suche</button>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="">
                                <div class="card-body">
                                    <div class="container">
                                        <div class="row">
                                            <div class="form-group col-lg-8">

                                            </div>
                                        </div>
                                    </div>
                                    @if($showWeekly)
                                        <div class="row mb-3">
                                            <div class="col-lg-12  col-md-12 col-sm-12 ">
                                                <div class="card lobicard lobicard-custom-control" data-sortable="true">
                                                    <div class="card-header">
                                                        <div class="card-title custom_title">
                                                            <h4>Wochenumsätze</h4>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <canvas id="barChartSalesW" height="150"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-lg-12  col-md-12 col-sm-12 ">
                                            <div class="card lobicard lobicard-custom-control" data-sortable="true">
                                                <div class="card-header">
                                                    <div class="card-title custom_title">
                                                        <h4>{{ $reportName }}</h4>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <canvas id="barChartSales" height="150"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.1/chart.js"></script>
    <script>
        function sales() {
            var ctx2 = document.getElementById("barChartSales");
            var myChart2 = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($dataSetName) !!},
                    <?php if (isset($_GET['type']) && $_GET['type'] == "daily") { ?>
                    datasets: [{
                        label: "Verkäufe",
                        data: {!! json_encode($sales) !!},
                        borderColor: "rgba(0, 150, 136, 0.8)",
                        width: "1",
                        borderWidth: "0",
                        backgroundColor: "rgba(0, 150, 13, 0.8)",
                    },

                        {
                            label: "Kasse",
                            data: {!! json_encode($checkout) !!},
                            borderColor: "rgba(51, 51, 51, 0.55)",
                            width: "1",
                            borderWidth: "0",
                            backgroundColor: "rgba(51, 51, 51, 0.55)"
                        }]
                    <?php }else{ ?>
                    datasets: [{
                        label: "Verkäufe",
                        data: {!! json_encode($sales) !!},
                        borderColor: "rgba(0, 150, 136, 0.8)",
                        width: "1",
                        borderWidth: "0",
                        backgroundColor: "rgba(0, 150, 13, 0.8)",
                    }],
                    <?php } ?>
                },
                options: {
                    plugins: {
                        tooltip: {
                            intersect: false,
                            mode: 'index',
                            titleFont: {
                                size: 30
                            },
                            bodyFont: {
                                size: 20
                            },
                            footerFont: {
                                size: 20
                            }
                        }
                    },
                    onClick: (e, activeEls) => {
                        let datasetIndex = activeEls[0].datasetIndex;
                        let dataIndex = activeEls[0].index;
                        let datasetLabel = e.chart.data.datasets[datasetIndex].label;
                        let value = e.chart.data.datasets[datasetIndex].data[dataIndex];
                        let label = e.chart.data.labels[dataIndex];
                        if (typeof label === 'string') {
                            label = label.replace(/^"|"$/g, '')
                        }
                        @if(!empty($linkLine))
                            window.location.href = "<?php echo $linkLine?>";
                        @endif
                    },
                    tooltips: {
                        intersect: false,
                        mode: 'index',
                        callbacks: {
                            label: function(tooltipItem, data) {
                                return '$' + (data['datasets'][0]['data'][tooltipItem['index']]).toFixed(2);
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
        }

        @if($showWeekly)
        function salesWeekly() {
            var ctx2 = document.getElementById("barChartSalesW");
            var myChart2 = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($dataSetName2) !!},
                    datasets: [{
                        label: "Verkäufe",
                        data: {!! json_encode($weekly) !!},
                        borderColor: "rgba(0, 150, 136, 0.8)",
                        width: "1",
                        borderWidth: "0",
                        backgroundColor: "rgba(0, 150, 13, 0.8)",
                    }]
                },
                options: {
                    plugins: {
                        tooltip: {
                            titleFont: {
                                size: 30
                            },
                            bodyFont: {
                                size: 20
                            },
                            footerFont: {
                                size: 20 // there is no footer by default
                            }
                        }
                    },
                    tooltips: {
                        mode: 'label',
                        callbacks: {
                            label: function(tooltipItem, data) {
                                return '$' + (data['datasets'][0]['data'][tooltipItem['index']]).toFixed(2);
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
        }
        salesWeekly();
        @endif

        function getNumericMonth(monthAbbr) {
            return (String(['Jänner',
                'Februar',
                'März',
                'April',
                'Mai',
                'Juni',
                'Juli',
                'August',
                'September',
                'Oktober',
                'November',
                'Dezember'
            ].indexOf(monthAbbr) + 1).padStart(2, '0'))
        }

        sales();
    </script>
@endsection
